<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
	public function index(Request $request): View
	{
		$users = User::query()->orderBy('name')->paginate(20);
		$roles = Role::query()->orderBy('name')->pluck('name');
		return view('admin::users.index', compact('users', 'roles'));
	}

	public function update(Request $request, User $user): RedirectResponse
	{
		$validated = $request->validate([
			'role' => ['required', 'string', 'exists:roles,name'],
		]);

		$user->syncRoles([$validated['role']]);

		return back()->with('status', 'Role updated');
	}
}
