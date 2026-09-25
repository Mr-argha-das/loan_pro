@php $items = $items ?? ($disbursements ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Disbursement</th>
                <th scope="col">Customer</th>
                <th scope="col">Lender</th>
                <th scope="col">Date</th>
                <th scope="col">Approved</th>
                <th scope="col">Disbursed</th>
                <th scope="col">Net payable</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $disbursement)
                <tr>
                    <td>
                        <a href="{{ route('disbursements.show', $disbursement) }}" class="fw-semibold">{{ $disbursement->disbursement_code }}</a>
                        <div class="text-muted" style="font-size:.73rem">{{ strtoupper($disbursement->mode ?? '—') }} {{ $disbursement->utr_number ? '· '.$disbursement->utr_number : '' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $disbursement->customer?->name ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $disbursement->application?->application_code }}</div>
                    </td>
                    <td>{{ $disbursement->lender?->name ?? '—' }}</td>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($disbursement->disbursement_date) }}</td>
                    <td>{{ \App\Support\Format::money($disbursement->approved_amount) }}</td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($disbursement->disbursed_amount) }}</td>
                    <td class="fw-semibold text-primary">{{ \App\Support\Format::money($disbursement->net_amount) }}</td>
                    <td><x-status-badge :status="$disbursement->status" /></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('disbursements.show', $disbursement) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                            @can('update', $disbursement)
                                <a href="{{ route('disbursements.edit', $disbursement) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <x-empty-state icon="bi-cash-stack" title="No disbursements" message="Approved applications are disbursed from here.">
                            <x-slot:action>
                                @can('create', App\Models\Disbursement::class)
                                    <a href="{{ route('disbursements.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New disbursement</a>
                                @endcan
                            </x-slot:action>
                        </x-empty-state>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
