<div class="d-flex gap-3 mb-3" data-remark>
    <span class="lp-avatar">{{ $remark->creator?->initials() }}</span>
    <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold" style="font-size:.85rem">{{ $remark->creator?->name }}</span>
            <span class="text-muted" style="font-size:.72rem">{{ $remark->created_at?->diffForHumans() }}</span>
            <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">{{ ucfirst($remark->type) }}</span>
        </div>
        <div class="mt-1">{{ $remark->body }}</div>
    </div>
</div>
