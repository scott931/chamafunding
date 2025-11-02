<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignContribution;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReportsController extends Controller
{
    /**
     * Display main reports index page.
     */
    public function index()
    {
        return view('admin::reports.index');
    }

    /**
     * Platform Overview Dashboard Report
     */
    public function platformOverview(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Total Money Pledged (All Time)
        $totalPledgedAllTime = Campaign::sum('raised_amount') / 100;

        // Total Money Pledged (This Month)
        $totalPledgedThisMonth = Campaign::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('raised_amount') / 100;

        // Active Projects
        $activeProjects = Campaign::where('status', 'active')->count();

        // Successful Projects
        $successfulProjects = Campaign::where('status', 'successful')->count();

        // Platform Fees (All Time)
        $platformFeesAllTime = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->sum('amount') / 100;

        // Platform Fees (This Month)
        $platformFeesThisMonth = FinancialTransaction::where('transaction_type', 'fee')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount') / 100;

        // New User Registrations (This Week)
        $newUsersThisWeek = User::where('created_at', '>=', now()->startOfWeek())
            ->count();

        $data = [
            'total_pledged_all_time' => $totalPledgedAllTime,
            'total_pledged_this_month' => $totalPledgedThisMonth,
            'active_projects' => $activeProjects,
            'successful_projects' => $successfulProjects,
            'platform_fees_all_time' => $platformFeesAllTime,
            'platform_fees_this_month' => $platformFeesThisMonth,
            'new_users_this_week' => $newUsersThisWeek,
            'generated_at' => now(),
        ];

        if ($request->get('format') === 'print') {
            return $this->generatePdf('admin::reports.pdf.platform-overview', $data, 'Platform_Overview_' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->get('format') === 'csv') {
            return $this->generateCsv([
                ['Metric', 'Value'],
                ['Total Money Pledged (All Time)', '$' . number_format($totalPledgedAllTime, 2)],
                ['Total Money Pledged (This Month)', '$' . number_format($totalPledgedThisMonth, 2)],
                ['Active Projects', $activeProjects],
                ['Successful Projects', $successfulProjects],
                ['Platform Fees (All Time)', '$' . number_format($platformFeesAllTime, 2)],
                ['Platform Fees (This Month)', '$' . number_format($platformFeesThisMonth, 2)],
                ['New User Registrations (This Week)', $newUsersThisWeek],
                ['Generated At', $data['generated_at']->format('Y-m-d H:i:s')],
            ], 'platform_overview_' . now()->format('Y-m-d') . '.csv');
        }

        return view('admin::reports.platform-overview', compact('data'));
    }

    /**
     * All Projects Report
     */
    public function allProjects(Request $request)
    {
        $query = Campaign::with(['creator']);

        // Filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('success_filter')) {
            if ($request->success_filter === 'successful') {
                $query->where('status', 'successful');
            } elseif ($request->success_filter === 'failed') {
                $query->where('status', 'failed');
            }
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        $data = [
            'projects' => $projects,
            'filters' => $request->only(['status', 'start_date', 'end_date', 'success_filter']),
            'generated_at' => now(),
        ];

        if ($request->get('format') === 'print') {
            return $this->generatePdf('admin::reports.pdf.all-projects', $data, 'All_Projects_' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->get('format') === 'csv') {
            $rows = [['Project ID', 'Project Name', 'Creator Name', 'Creator Email', 'Creation Date', 'Deadline', 'Goal Amount', 'Amount Pledged', 'Status']];
            foreach ($projects as $project) {
                $rows[] = [
                    $project->id,
                    $project->title,
                    $project->creator->name ?? 'N/A',
                    $project->creator->email ?? 'N/A',
                    $project->created_at->format('Y-m-d'),
                    $project->deadline ? $project->deadline->format('Y-m-d') : 'N/A',
                    '$' . number_format($project->goal_amount / 100, 2),
                    '$' . number_format($project->raised_amount / 100, 2),
                    ucfirst($project->status),
                ];
            }
            return $this->generateCsv($rows, 'all_projects_' . now()->format('Y-m-d') . '.csv');
        }

        $statuses = Campaign::distinct()->pluck('status');
        return view('admin::reports.all-projects', compact('data', 'statuses'));
    }

    /**
     * Financial Summary Report
     */
    public function financialSummary(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $statusFilter = $request->get('status', null);

        $query = Campaign::with(['creator']);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $campaigns = $query->whereBetween('created_at', [$startDate, $endDate])
            ->orWhere(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('deadline', [$startDate, $endDate]);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $platformFeePercentage = PlatformSetting::getFloat('fee_structure.platform_fee_percentage', 5.0);
        $platformFeeFixed = PlatformSetting::getFloat('fee_structure.platform_fee_fixed', 0.0);

        $financialData = [];
        $totalGross = 0;
        $totalFees = 0;
        $totalPayouts = 0;

        foreach ($campaigns as $campaign) {
            $grossPledges = $campaign->raised_amount / 100;

            // Try to get actual fees from FinancialTransaction records
            $actualFees = FinancialTransaction::where('campaign_id', $campaign->id)
                ->where('transaction_type', 'fee')
                ->where('status', 'completed')
                ->sum('amount');

            // If we have actual fees, use them; otherwise calculate from settings
            if ($actualFees > 0) {
                $fees = $actualFees / 100;
            } else {
                // Calculate fees based on platform settings
                $fees = ($grossPledges * ($platformFeePercentage / 100)) + ($platformFeeFixed * $campaign->contributions()->count());
            }

            $payoutAmount = $grossPledges - $fees;

            $financialData[] = [
                'campaign' => $campaign,
                'gross_pledges' => $grossPledges,
                'platform_fees' => $fees,
                'payout_amount' => $payoutAmount,
            ];

            $totalGross += $grossPledges;
            $totalFees += $fees;
            $totalPayouts += $payoutAmount;
        }

        $data = [
            'financial_data' => $financialData,
            'total_gross' => $totalGross,
            'total_fees' => $totalFees,
            'total_payouts' => $totalPayouts,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status_filter' => $statusFilter,
            'platform_fee_percentage' => $platformFeePercentage,
            'platform_fee_fixed' => $platformFeeFixed,
            'generated_at' => now(),
        ];

        if ($request->get('format') === 'print') {
            return $this->generatePdf('admin::reports.pdf.financial-summary', $data, 'Financial_Summary_' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->get('format') === 'csv') {
            $rows = [['Project Name', 'Project ID', 'Funding Status', 'Gross Pledges', 'Platform Fees', 'Payout Amount']];
            foreach ($financialData as $item) {
                $rows[] = [
                    $item['campaign']->title,
                    $item['campaign']->id,
                    ucfirst($item['campaign']->status),
                    '$' . number_format($item['gross_pledges'], 2),
                    '$' . number_format($item['platform_fees'], 2),
                    '$' . number_format($item['payout_amount'], 2),
                ];
            }
            $rows[] = ['TOTAL', '', '', '$' . number_format($totalGross, 2), '$' . number_format($totalFees, 2), '$' . number_format($totalPayouts, 2)];
            return $this->generateCsv($rows, 'financial_summary_' . now()->format('Y-m-d') . '.csv');
        }

        $statuses = Campaign::distinct()->pluck('status');
        return view('admin::reports.financial-summary', compact('data', 'statuses'));
    }

    /**
     * Detailed Backer Report
     */
    public function backerReport(Request $request)
    {
        $query = CampaignContribution::with(['user', 'campaign', 'rewardTier']);

        // Filters
        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $contributions = $query->orderBy('created_at', 'desc')->get();

        // Determine payment status for each contribution
        $backerData = [];
        foreach ($contributions as $contribution) {
            $paymentStatus = 'Paid';
            if ($contribution->status === 'refunded') {
                $paymentStatus = 'Refunded';
            } elseif ($contribution->status === 'failed') {
                $paymentStatus = 'Failed';
            } elseif ($contribution->status === 'pending') {
                $paymentStatus = 'Pending';
            }

            $backerData[] = [
                'backer_name' => $contribution->user->name ?? 'N/A',
                'backer_email' => $contribution->user->email ?? 'N/A',
                'project_name' => $contribution->campaign->title ?? 'N/A',
                'project_id' => $contribution->campaign_id,
                'pledge_amount' => $contribution->amount / 100,
                'reward_tier' => $contribution->rewardTier->name ?? 'No Reward Selected',
                'pledge_date' => $contribution->created_at,
                'payment_status' => $paymentStatus,
            ];
        }

        $data = [
            'backer_data' => $backerData,
            'filters' => $request->only(['campaign_id', 'start_date', 'end_date']),
            'generated_at' => now(),
        ];

        if ($request->get('format') === 'print') {
            return $this->generatePdf('admin::reports.pdf.backer-report', $data, 'Backer_Report_' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->get('format') === 'csv') {
            $rows = [['Backer Name', 'Backer Email', 'Project Name', 'Project ID', 'Pledge Amount', 'Reward Tier', 'Pledge Date', 'Payment Status']];
            foreach ($backerData as $item) {
                $rows[] = [
                    $item['backer_name'],
                    $item['backer_email'],
                    $item['project_name'],
                    $item['project_id'],
                    '$' . number_format($item['pledge_amount'], 2),
                    $item['reward_tier'],
                    $item['pledge_date']->format('Y-m-d H:i:s'),
                    $item['payment_status'],
                ];
            }
            return $this->generateCsv($rows, 'backer_report_' . now()->format('Y-m-d') . '.csv');
        }

        $campaigns = Campaign::orderBy('title')->get();
        return view('admin::reports.backer-report', compact('data', 'campaigns'));
    }

    /**
     * User Management Report
     */
    public function userManagement(Request $request)
    {
        $query = User::withCount(['campaigns', 'contributions']);

        // Filters
        if ($request->has('user_type')) {
            if ($request->user_type === 'creator') {
                $query->has('campaigns');
            } elseif ($request->user_type === 'backer') {
                $query->has('contributions');
            } elseif ($request->user_type === 'both') {
                $query->has('campaigns')->has('contributions');
            }
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $userData = [];
        foreach ($users as $user) {
            $totalPledged = $user->contributions()->sum('amount') / 100;
            $totalRaised = $user->campaigns()->sum('raised_amount') / 100;

            // Determine user type
            $userType = 'Backer';
            if ($user->campaigns_count > 0 && $user->contributions_count > 0) {
                $userType = 'Both';
            } elseif ($user->campaigns_count > 0) {
                $userType = 'Creator';
            }

            // Account status (we'll assume active for now - can be enhanced with suspension logic)
            $accountStatus = 'Active';
            // TODO: Add suspension check when implemented

            $userData[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'date_registered' => $user->created_at,
                'user_type' => $userType,
                'projects_created' => $user->campaigns_count,
                'total_pledged' => $totalPledged,
                'account_status' => $accountStatus,
            ];
        }

        $data = [
            'user_data' => $userData,
            'filters' => $request->only(['user_type', 'start_date', 'end_date']),
            'generated_at' => now(),
        ];

        if ($request->get('format') === 'print') {
            return $this->generatePdf('admin::reports.pdf.user-management', $data, 'User_Management_' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->get('format') === 'csv') {
            $rows = [['User ID', 'Name', 'Email', 'Date Registered', 'User Type', 'Projects Created', 'Total Pledged', 'Account Status']];
            foreach ($userData as $item) {
                $rows[] = [
                    $item['user_id'],
                    $item['name'],
                    $item['email'],
                    $item['date_registered']->format('Y-m-d'),
                    $item['user_type'],
                    $item['projects_created'],
                    '$' . number_format($item['total_pledged'], 2),
                    $item['account_status'],
                ];
            }
            return $this->generateCsv($rows, 'user_management_' . now()->format('Y-m-d') . '.csv');
        }

        return view('admin::reports.user-management', compact('data'));
    }

    /**
     * Support & Moderation Report
     */
    public function supportModeration(Request $request)
    {
        // Projects Pending Review
        $pendingProjects = Campaign::whereIn('status', ['draft'])
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        // Flagged Projects (for now, we'll check for suspicious patterns)
        // TODO: Implement actual flagging system
        $flaggedProjects = Campaign::where('status', 'active')
            ->whereHas('contributions', function ($q) {
                $q->select('campaign_id', 'user_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('campaign_id', 'user_id')
                    ->havingRaw('COUNT(*) > 10'); // Suspicious: same user backing same campaign many times
            })
            ->with('creator')
            ->get();

        // Users with Failed Payouts (we'll check for campaigns with issues)
        // This is a placeholder - implement actual payout failure tracking
        $failedPayouts = [];

        // Projects with Suspicious Activity
        // Check for many tiny pledges from same IP (would need IP tracking in contributions)
        // For now, we'll flag campaigns with many small contributions
        $suspiciousCampaigns = Campaign::where('status', 'active')
            ->whereHas('contributions', function ($q) {
                $q->where('amount', '<', 1000) // Less than $10
                    ->select('campaign_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('campaign_id')
                    ->havingRaw('COUNT(*) > 50'); // More than 50 tiny pledges
            })
            ->with('creator')
            ->get();

        $data = [
            'pending_projects' => $pendingProjects,
            'flagged_projects' => $flaggedProjects,
            'failed_payouts' => $failedPayouts,
            'suspicious_campaigns' => $suspiciousCampaigns,
            'generated_at' => now(),
        ];

        if ($request->get('format') === 'print') {
            return $this->generatePdf('admin::reports.pdf.support-moderation', $data, 'Support_Moderation_' . now()->format('Y-m-d') . '.pdf');
        }

        if ($request->get('format') === 'csv') {
            $rows = [
                ['Type', 'Project ID', 'Project Name', 'Creator Name', 'Creator Email', 'Reason', 'Created At']
            ];

            foreach ($pendingProjects as $project) {
                $rows[] = [
                    'Pending Review',
                    $project->id,
                    $project->title,
                    $project->creator->name ?? 'N/A',
                    $project->creator->email ?? 'N/A',
                    'Project pending approval',
                    $project->created_at->format('Y-m-d H:i:s'),
                ];
            }

            foreach ($flaggedProjects as $project) {
                $rows[] = [
                    'Flagged',
                    $project->id,
                    $project->title,
                    $project->creator->name ?? 'N/A',
                    $project->creator->email ?? 'N/A',
                    'Multiple contributions from same user',
                    $project->created_at->format('Y-m-d H:i:s'),
                ];
            }

            foreach ($suspiciousCampaigns as $project) {
                $rows[] = [
                    'Suspicious Activity',
                    $project->id,
                    $project->title,
                    $project->creator->name ?? 'N/A',
                    $project->creator->email ?? 'N/A',
                    'Many small contributions detected',
                    $project->created_at->format('Y-m-d H:i:s'),
                ];
            }

            return $this->generateCsv($rows, 'support_moderation_' . now()->format('Y-m-d') . '.csv');
        }

        return view('admin::reports.support-moderation', compact('data'));
    }

    /**
     * Generate PDF from view
     */
    private function generatePdf($view, $data, $filename)
    {
        $html = view($view, compact('data'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream($filename);
    }

    /**
     * Generate CSV from data
     */
    private function generateCsv($rows, $filename)
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}

