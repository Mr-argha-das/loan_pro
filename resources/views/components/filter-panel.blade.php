@props(['action' => null, 'resetUrl' => null])

<div class="lp-filter-panel no-print">
    <form method="GET" action="{{ $action ?? url()->current() }}" class="row g-3 align-items-end">
        {{ $slot }}

        <div class="col-auto ms-auto d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ $resetUrl ?? url()->current() }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
    </form>
</div>
