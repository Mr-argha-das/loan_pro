<x-section title="OTP Verification" icon="bi-shield-check" description="Verify the customer's mobile number before collecting KYC data.">
    @if ($lead?->is_otp_verified)
        <div class="alert alert-success d-flex align-items-center gap-3">
            <i class="bi bi-patch-check-fill fs-4"></i>
            <div>
                <div class="fw-semibold">Mobile number verified</div>
                <div class="small">Verified on {{ $lead->otp_verified_at?->format('d M Y, h:i A') }}. You can continue to personal information.</div>
            </div>
        </div>
    @else
        <div class="row g-3 align-items-center">
            <div class="col-lg-7">
                <p class="text-muted small mb-3">
                    An OTP will be sent to <strong class="text-body">{{ $lead?->customer?->mobile ?? 'the customer mobile' }}</strong>.
                    The code is valid for 2 minutes.
                </p>

                <div class="lp-otp-inputs mb-3" role="group" aria-label="One time password">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" inputmode="numeric" maxlength="1" data-otp aria-label="Digit {{ $i + 1 }}" autocomplete="one-time-code">
                    @endfor
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-primary btn-sm" data-otp-send>Send OTP</button>
                    <button type="button" class="btn btn-success btn-sm" data-otp-verify>Verify OTP</button>
                    <button type="button" class="btn btn-link btn-sm" data-otp-change-number>Change number</button>
                    <button type="button" class="btn btn-link btn-sm disabled" data-otp-resend>Resend</button>
                </div>

                <div class="text-muted small mt-3" data-otp-timer>Request an OTP to start the timer.</div>
            </div>

            <div class="col-lg-5">
                <div class="lp-card bg-primary-subtle bg-opacity-10 border-0 p-3">
                    <div class="fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i> Why we verify</div>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>Prevents fraudulent lead submissions</li>
                        <li>Confirms the customer can be reached</li>
                        <li>Unlocks lender comparison in step 8</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <a href="{{ $lead ? route('leads.wizard', ['lead' => $lead, 'step' => 4]) : '#' }}" class="btn btn-primary @unless ($lead?->is_otp_verified) disabled @endunless">
            Continue <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>
