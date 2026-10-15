@php
    // Latest uploaded file per document type, so each type shows its own status.
    $existing = $lead?->documents?->groupBy('document_type_id')->map(fn ($rows) => $rows->sortByDesc('created_at')->first()) ?? collect();
    $uploadRow = 0;
@endphp

<x-section title="KYC & Documents" icon="bi-folder-check"
           description="Every document type is listed below. Upload the file against its own type.">
    <div class="alert alert-info d-flex gap-2 small mb-4">
        <i class="bi bi-shield-lock"></i>
        <div>Files are stored privately and streamed only to authorised users. Accepted formats: PDF, JPG, JPEG, PNG (max 5 MB each).</div>
    </div>

    <div class="row g-3">
        @forelse ($documentTypes as $type)
            @php
                $document = $existing->get($type->id);
                $index = $uploadRow++;
            @endphp
            <div class="col-md-6">
                <div class="lp-card h-100 p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-semibold">
                                {{ $type->name }}
                                @if ($type->is_required_default)<span class="req" aria-hidden="true">*</span>@endif
                            </div>
                            <div class="text-muted small">{{ $type->is_required_default ? 'Required' : 'Optional' }}</div>
                        </div>
                        @if ($document)
                            <div class="d-flex align-items-center gap-1">
                                <x-status-badge :status="$document->status" />
                                <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" aria-label="View {{ $type->name }}"><i class="bi bi-eye"></i></a>
                            </div>
                        @else
                            <span class="lp-badge bg-secondary-subtle text-secondary bg-opacity-10">Not uploaded</span>
                        @endif
                    </div>

                    <input type="hidden" name="documents[{{ $index }}][document_type_id]" value="{{ $type->id }}">
                    <x-file-uploader name="documents[{{ $index }}][file]" label="Upload {{ $type->name }}" :required="false" />

                    <div class="row g-2">
                        <div class="col-6"><x-input name="documents[{{ $index }}][issued_number]" label="Document number" placeholder="Optional" /></div>
                        @if ($type->has_expiry)
                            <div class="col-6"><x-input name="documents[{{ $index }}][expires_at]" label="Expiry date" type="date" /></div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <x-empty-state icon="bi-folder2-open" title="No document types configured" message="Ask an administrator to add document types under Masters." />
            </div>
        @endforelse
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
                @forelse ($lead?->documents ?? [] as $document)
                    <tr>
                        <td class="fw-semibold">{{ $document->typeName() }}</td>
                        <td>{{ $document->issued_number ?? '—' }}</td>
                        <td><x-status-badge :status="$document->status" /></td>
                        <td class="text-muted">{{ $document->created_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light"><i class="bi bi-download"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="bi-folder2-open" title="No documents uploaded yet" message="Choose a document type above and upload its file." /></td></tr>
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
