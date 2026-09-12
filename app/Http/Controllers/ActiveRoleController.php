<?php

namespace App\Http\Controllers;

use App\Support\ActiveRoleContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveRoleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $switched = ActiveRoleContext::set($request->user(), $validated['role']);

        if (! $switched) {
            return back()->with('error', 'Bạn không giữ vai trò này.');
        }

        return back()->with('success', 'Đã chuyển giao diện sang vai trò khác.');
    }
}
