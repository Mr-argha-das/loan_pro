@php
    $existing = $lead?->documents?->keyBy('document_type_id') ?? collect();
@endphp

<x-section title="KYC & Documents" icon="bi-folder-check"
           description="Upload any documents you already have — you can add the rest later from the lead page.">
    <div class="alert alert-info d-flex gap-2 small mb-4">
        <i class="bi bi-shield-lock"></i>
        <div>Files are stored privately and streamed only to authorised users. Accepted formats: PDF, JPG, JPEG, PNG (max 5 MB each).</div>
    </div>

    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label" for="document_type_id">Document type</label>
            <select class="form-select" id="document_type_id" name="documents[0][document_type_id]">
                <option value="">Select document type</option>
                @foreach ($documentTypes as $type)
                    <option value="{{ $type->id }}" @selected(($type->is_required_default ?? false))>
                        {{ $type->name }}{{ $type->is_required_default ? ' (required)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <x-file-uploader name="documents[0][file]" label="Choose file" />
        </div>
        <div class="col-md-3"><x-input name="documents[0][issued_number]" label="Document number" placeholder="Optional" /></div>
        <div class="col-md-3"><x-input name="documents[0][expires_at]" label="Expiry date" type="date" /></div>
    </div>

    <div class="lp-divider"></div>

    <div class="fw-semibold mb-2">Uploaded documents</div>
    <div class="table-responsive">
        <table class="lp-table">
            <thead>
                <tr>
                    <th scope="col">Document</th>
                    <th scope="col">Number</th>
                    <th scope="col">Status</th>
                    <th scope="col">Uploaded</th>
                    <th scope="col" class="text-end">File</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($existing as $document)
                    <tr>
                        <td class="fw-semibold">{{ $document->documentType?->name ?? 'Document' }}</td>
                        <td>{{ $document->issued_number ?? '—' }}</td>
                        <td><x-status-badge :status="$document->status" /></td>
                        <td class="text-muted">{{ $document->created_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="bi-folder2-open" title="No documents uploaded yet" message="Use the form above to upload the first document." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
