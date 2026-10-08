@php $existing = $lead?->documents?->keyBy('document_type_id') ?? collect(); @endphp

<x-section title="KYC & Documents" icon="bi-folder-check"
           description="Upload multiple documents together. You can add more later from the lead page.">
    <div class="alert alert-info d-flex gap-2 small mb-4">
        <i class="bi bi-shield-lock"></i>
        <div>Files are stored privately. Accepted formats: PDF, JPG, JPEG, PNG (max 5 MB each).</div>
    </div>

    <div id="lead-document-rows">
        <div class="row g-3 align-items-end lead-document-row mb-2" data-document-row>
            <div class="col-md-3">
                <label class="form-label">Document type</label>
                <select class="form-select" name="documents[0][document_type_id]">
                    <option value="">Select document type</option>
                    @foreach ($documentTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}{{ $type->is_required_default ? ' (required)' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4"><x-file-uploader name="documents[0][file]" label="Choose file" /></div>
            <div class="col-md-2"><x-input name="documents[0][issued_number]" label="Document number" placeholder="Optional" /></div>
            <div class="col-md-2"><x-input name="documents[0][expires_at]" label="Expiry date" type="date" /></div>
            <div class="col-md-1"><button type="button" class="btn btn-light text-danger d-none" data-remove-document title="Remove"><i class="bi bi-trash"></i></button></div>
        </div>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-add-document><i class="bi bi-plus-lg"></i> Add another document</button>

    <div class="lp-divider"></div>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-semibold">Uploaded documents</div>
        @if ($existing->isNotEmpty())
            <a href="{{ route('leads.documents.zip', $lead) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-zip"></i> Download all as ZIP</a>
        @endif
    </div>
    <div class="table-responsive"><table class="lp-table"><thead><tr><th>Document</th><th>Number</th><th>Status</th><th>Uploaded</th><th class="text-end">File</th></tr></thead><tbody>
        @forelse ($existing as $document)
            <tr><td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td><td>{{ $document->issued_number ?? '—' }}</td><td><x-status-badge :status="$document->status" /></td><td class="text-muted">{{ $document->created_at?->format('d M Y') }}</td><td class="text-end"><a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a><a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a></td></tr>
        @empty <tr><td colspan="5"><x-empty-state icon="bi-folder2-open" title="No documents uploaded yet" message="Add one or more documents above." /></td></tr>
        @endforelse
    </tbody></table></div>
</x-section>
<div class="d-flex justify-content-between"><button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button><div class="d-flex gap-2"><button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button><button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button></div></div>

@push('scripts')
<script type="module">
(() => {
 const wrap = document.getElementById('lead-document-rows'); const add = document.querySelector('[data-add-document]');
 if (!wrap || !add) return;
 let index = wrap.querySelectorAll('[data-document-row]').length;
 add.addEventListener('click', () => {
   const row = wrap.querySelector('[data-document-row]').cloneNode(true);
   row.querySelectorAll('input,select').forEach(el => { el.name = el.name.replace(/documents\[\d+\]/, `documents[${index}]`); el.value = ''; });
   row.querySelector('[data-file-name]')?.classList.add('d-none'); row.querySelector('[data-remove-document]').classList.remove('d-none');
   wrap.appendChild(row); index++;
 });
 wrap.addEventListener('click', e => { const btn = e.target.closest('[data-remove-document]'); if (btn) btn.closest('[data-document-row]').remove(); });
})();
</script>
@endpush
