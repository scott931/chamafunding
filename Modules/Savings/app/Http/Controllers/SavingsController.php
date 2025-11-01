<?php

namespace Modules\Savings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SavingsController extends Controller
{
    /**
     * Display a listing of savings accounts
     */
    public function index(Request $request)
    {
        // If API request, return JSON
        if ($request->wantsJson() || $request->expectsJson()) {
            $query = SavingsAccount::with(['user', 'transactions'])
                ->where('user_id', Auth::id());

            // Filter by account type
            if ($request->has('account_type')) {
                $query->where('account_type', $request->account_type);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $accounts = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $accounts,
                'message' => 'Savings accounts retrieved successfully'
            ]);
        }

        // For web requests, return view
        $accounts = SavingsAccount::with(['user', 'transactions'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('savings::index', compact('accounts'));
    }

    /**
     * Create a new savings account
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_type' => 'required|string|in:regular,fixed_deposit,goal_savings',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|string|size:3',
            'minimum_balance' => 'required|numeric|min:0',
            'maximum_balance' => 'nullable|numeric|min:0',
            'maturity_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $account = SavingsAccount::create([
                'user_id' => Auth::id(),
                'account_number' => 'SAV' . time() . Str::random(6),
                'account_type' => $request->account_type,
                'interest_rate' => $request->interest_rate,
                'currency' => strtoupper($request->currency),
                'minimum_balance' => $request->minimum_balance,
                'maximum_balance' => $request->maximum_balance,
                'maturity_date' => $request->maturity_date,
                'notes' => $request->notes,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $account->load('user'),
                'message' => 'Savings account created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create savings account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified savings account
     */
    public function show($id): JsonResponse
    {
        $account = SavingsAccount::with(['user', 'transactions'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $account,
            'message' => 'Savings account retrieved successfully'
        ]);
    }

    /**
     * Update the specified savings account
     */
    public function update(Request $request, $id): JsonResponse
    {
        $account = SavingsAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$account->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update inactive account'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'interest_rate' => 'sometimes|numeric|min:0|max:100',
            'minimum_balance' => 'sometimes|numeric|min:0',
            'maximum_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $account->update($request->only([
                'interest_rate', 'minimum_balance', 'maximum_balance', 'notes'
            ]));

            return response()->json([
                'success' => true,
                'data' => $account->load('user'),
                'message' => 'Savings account updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update savings account',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make a deposit
     */
    public function deposit(Request $request, $id): JsonResponse
    {
        $account = SavingsAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$account->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;

        if (!$account->canDeposit($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Deposit amount exceeds maximum balance limit'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $newBalance = $account->balance + $amount;

            $transaction = SavingsTransaction::create([
                'savings_account_id' => $account->id,
                'user_id' => Auth::id(),
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'currency' => $account->currency,
                'reference' => 'DEP_' . time() . '_' . Str::random(6),
                'description' => $request->description ?? 'Deposit to savings account',
                'status' => 'completed',
            ]);

            $account->update(['balance' => $newBalance]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaction->load('savingsAccount'),
                'message' => 'Deposit successful'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process deposit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Make a withdrawal
     */
    public function withdraw(Request $request, $id): JsonResponse
    {
        $account = SavingsAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$account->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01|max:' . $account->balance,
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;

        if (!$account->canWithdraw($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance or withdrawal would violate minimum balance requirement'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $newBalance = $account->balance - $amount;

            $transaction = SavingsTransaction::create([
                'savings_account_id' => $account->id,
                'user_id' => Auth::id(),
                'transaction_type' => 'withdrawal',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'currency' => $account->currency,
                'reference' => 'WTH_' . time() . '_' . Str::random(6),
                'description' => $request->description ?? 'Withdrawal from savings account',
                'status' => 'completed',
            ]);

            $account->update(['balance' => $newBalance]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $transaction->load('savingsAccount'),
                'message' => 'Withdrawal successful'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to process withdrawal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate interest for an account
     */
    public function calculateInterest($id): JsonResponse
    {
        $account = SavingsAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$account->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active'
            ], 400);
        }

        $interest = $account->calculateInterest();

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'account_number' => $account->account_number,
                'current_balance' => $account->balance,
                'interest_rate' => $account->interest_rate,
                'calculated_interest' => $interest,
                'currency' => $account->currency,
            ],
            'message' => 'Interest calculated successfully'
        ]);
    }

    /**
     * Get savings history
     */
    public function history(Request $request, $id): JsonResponse
    {
        $account = SavingsAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        $query = $account->transactions();

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'message' => 'Savings history retrieved successfully'
        ]);
    }

    /**
     * Get savings goals
     */
    public function goals(Request $request): JsonResponse
    {
        $goals = SavingsAccount::where('user_id', Auth::id())
            ->where('account_type', 'goal_savings')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $goals,
            'message' => 'Savings goals retrieved successfully'
        ]);
    }

    /**
     * Close a savings account
     */
    public function close($id): JsonResponse
    {
        $account = SavingsAccount::where('user_id', Auth::id())
            ->findOrFail($id);

        if (!$account->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is already closed or inactive'
            ], 400);
        }

        if ($account->balance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot close account with remaining balance. Please withdraw all funds first.'
            ], 400);
        }

        try {
            $account->update(['status' => 'closed']);

            return response()->json([
                'success' => true,
                'message' => 'Savings account closed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close savings account',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
