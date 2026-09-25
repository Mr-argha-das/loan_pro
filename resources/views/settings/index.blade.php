@extends('layouts.app')

@section('title', 'Settings')
@section('page-header', true)
@section('page-title', 'System Settings')
@section('page-subtitle', 'Company profile, invoicing defaults and notification behaviour.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Settings</li>
@endsection

@section('page-actions')
    @canPermission('settings.manage')
        <a href="{{ route('audit.index') }}" class="btn btn-light btn-sm"><i class="bi bi-shield-check"></i> Audit Log</a>
    @endcanPermission
@endsection

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" data-ajax data-reload="true">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8">
                @foreach ($groups as $group => $settings)
                    <x-section :title="\App\Support\Format::titleCase($group).' settings'" icon="bi-sliders"
                               description="Stored in the settings table and read at runtime — no code change required.">
                        <div class="row g-3">
                            @foreach ($settings as $setting)
                                <div class="col-md-6">
                                    <label class="form-label" for="setting-{{ $setting->id }}">
                                        {{ $setting->label ?? \App\Support\Format::titleCase($setting->key) }}
                                    </label>

                                    @if ($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" value="1"
                                                   id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]"
                                                   @checked((bool) $setting->value)>
                                            <label class="form-check-label small" for="setting-{{ $setting->id }}">Enabled</label>
                                        </div>
                                    @elseif ($setting->type === 'textarea')
                                        <textarea class="form-control" id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]" rows="3">{{ $setting->value }}</textarea>
                                    @elseif ($setting->type === 'select' && ! empty($setting->options))
                                        <select class="form-select" id="setting-{{ $setting->id }}" name="settings[{{ $setting->key }}]">
                                            @foreach ((array) $setting->options as $optionValue => $optionLabel)
                                                <option value="{{ is_int($optionValue) ? $optionLabel : $optionValue }}" @selected((string) $setting->value === (string) (is_int($optionValue) ? $optionLabel : $optionValue))>
                                                    {{ $optionLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text" class="form-control" id="setting-{{ $setting->id }}"
                                               name="settings[{{ $setting->key }}]" value="{{ $setting->value }}">
                                    @endif

                                    @if ($setting->description)
                                        <div class="form-text">{{ $setting->description }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-section>
                @endforeach
            </div>

            <div class="col-lg-4">
                <x-card title="Company profile" icon="bi-building" description="Shown on invoices, PDFs and printed reports.">
                    <div class="lp-kv"><span class="lp-kv__label">Name</span><span class="lp-kv__value">{{ $company['name'] ?? '—' }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Email</span><span class="lp-kv__value">{{ $company['email'] ?? '—' }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Phone</span><span class="lp-kv__value">{{ $company['phone'] ?? '—' }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">GSTIN</span><span class="lp-kv__value">{{ $company['gst'] ?? '—' }}</span></div>
                    <div class="lp-kv"><span class="lp-kv__label">Address</span><span class="lp-kv__value">{{ $company['address'] ?? '—' }}</span></div>
                </x-card>

                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save all settings</button>
                    <a href="{{ route('masters.index') }}" class="btn btn-light"><i class="bi bi-diagram-3"></i> Master Management</a>
                </div>
            </div>
        </div>
    </form>
@endsection
