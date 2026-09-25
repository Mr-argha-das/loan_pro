@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <div class="row g-0 justify-content-center" style="max-width:960px;width:100%">
        <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-center text-white p-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="lp-sidebar__mark" style="width:46px;height:46px;font-size:1.1rem">LP</span>
                <div>
                    <div class="fw-bold fs-5">LoanPro</div>
                    <div class="text-uppercase" style="font-size:.7rem;letter-spacing:.12em;opacity:.7">Finance &amp; Insurance</div>
                </div>
            </div>

            <h2 class="text-white mb-3" style="font-size:1.7rem;line-height:1.3">
                One platform for your entire lending &amp; insurance book.
            </h2>
            <p style="opacity:.78" class="mb-4">
                Capture leads, verify customers, compare lender products, track approvals,
                disbursements, invoices and collections — all in a single workspace.
            </p>

            <div class="d-flex flex-column gap-2" style="opacity:.9">
                @foreach (['10-step lead onboarding with OTP verification', 'Live lender comparison with ROI, APR & EMI', 'Disbursement, invoicing and collection tracking', 'Role based access with a full audit trail'] as $feature)
                    <div class="d-flex align-items-center gap-2 small">
                        <i class="bi bi-check-circle-fill text-success"></i>{{ $feature }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-6">
            <div class="lp-card p-4 p-lg-5 h-100">
                <div class="d-lg-none d-flex align-items-center gap-2 mb-4">
                    <span class="lp-sidebar__mark">LP</span>
                    <div class="fw-bold">LoanPro</div>
                </div>

                <h1 class="h4 mb-1">Welcome back</h1>
                <p class="text-muted small mb-4">Sign in to continue to your workspace.</p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-octagon me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Work email<span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="email" name="email" value="{{ old('email', 'admin@loanpro.in') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="you@company.com" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password<span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" id="password" name="password" value="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="••••••••" required autocomplete="current-password">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 btn-lg" type="submit">
                        <i class="bi bi-box-arrow-in-right"></i> Sign in
                    </button>
                </form>

                <div class="lp-divider"></div>

                <div class="small text-muted">
                    <div class="fw-semibold text-body mb-1">Demo credentials</div>
                    Administrator &mdash; <code>admin@loanpro.in</code> / <code>password</code><br>
                    Employee &mdash; <code>neha.singh@loanpro.in</code> / <code>password</code>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-password')?.addEventListener('click', (event) => {
            const input = document.getElementById('password');
            const icon = event.currentTarget.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
@endpush
