<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.show', [
            'user' => $user->load(['role', 'employee.department', 'employee.designation', 'extraPermissions']),
            'stats' => [
                'leads' => Lead::query()->ownedBy($user)->count(),
                'applications' => LoanApplication::query()->ownedBy($user)->count(),
                'payments' => (float) Payment::query()->where('created_by', $user->id)->sum('amount'),
                'documents' => Document::query()->where('uploaded_by', $user->id)->count(),
            ],
            'notifications' => app(NotificationService::class)->recentFor($user, 8),
            'activity' => AuditLog::query()->where('user_id', $user->id)->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'designation' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        return back()->with('success', 'Password changed successfully.');
    }

    public function notifications(Request $request): View
    {
        return view('profile.notifications', [
            'notifications' => app(NotificationService::class)->recentFor($request->user(), 30),
        ]);
    }
}
