<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;


class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || !$user->isAdmin()) {
                abort(403, 'Access denied. Admin only.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $pendingUsers = User::where('status', User::STATUS_PENDING)->paginate(15);
        $approvedUsers = User::where('status', User::STATUS_APPROVED)->paginate(15);

        return view('admin.index', compact('pendingUsers', 'approvedUsers'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|in:' . implode(',', User::getAvailableRoles()),
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'status' => User::STATUS_APPROVED,
            'role' => $request->role,
        ]);

        return back()->with('success', 'User approved successfully.');
    }

    public function reject($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => User::STATUS_REJECTED]);

        return back()->with('success', 'User rejected successfully.');
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|in:' . implode(',', User::getAvailableRoles()),
        ]);

        $user = User::findOrFail($id);
        $user->update(['role' => $request->role]);

        return back()->with('success', 'User role updated successfully.');
    }
}
