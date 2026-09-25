<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('settings.manage'), 403);

        $settings = Setting::query()->orderBy('group')->orderBy('sort_order')->get()->groupBy('group');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'settings' => $settings]);
        }

        return view('settings.index', [
            'groups' => $settings,
            'company' => [
                'name' => Setting::get('company_name'),
                'address' => Setting::get('company_address'),
                'email' => Setting::get('company_email'),
                'phone' => Setting::get('company_phone'),
                'gst' => Setting::get('gst_number'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('settings.manage'), 403);

        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            $setting = Setting::query()->where('key', $key)->first();

            if (! $setting) {
                continue;
            }

            if ($setting->type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            Setting::set($key, $value);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            Setting::set('company_logo', $path);
        }

        if ($request->expectsJson()) {
            return $this->ok('Settings saved.');
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    public function auditLog(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('audit.view'), 403);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(fn ($sub) => $sub->where('module', 'like', "%{$term}%")
                    ->orWhere('action', 'like', "%{$term}%")
                    ->orWhere('record_label', 'like', "%{$term}%"));
            })
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('to_date')))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($logs, 'settings.partials.audit-table');
        }

        return view('settings.audit', [
            'logs' => $logs,
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'filters' => $request->all(),
        ]);
    }
}
