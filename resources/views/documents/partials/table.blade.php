@php $items = $items ?? ($documents ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Document</th>
                <th scope="col">Attached to</th>
                <th scope="col">Type</th>
                <th scope="col">Size</th>
                <th scope="col">Status</th>
                <th scope="col">Uploaded by</th>
                <th scope="col">Uploaded</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $document)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="lp-file-icon {{ $document->isImage() ? 'is-image' : '' }}">
                                <i class="bi {{ $document->isImage() ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' }}"></i>
                            </span>
                            <div>
                                <div class="fw-semibold">{{ $document->original_name }}</div>
                                <div class="text-muted" style="font-size:.72rem">{{ $document->issued_number ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ class_basename($document->documentable_type) }} #{{ $document->documentable_id }}</td>
                    <td>{{ $document->documentType?->name ?? '—' }}</td>
                    <td>{{ $document->humanSize() }}</td>
                    <td><x-status-badge :status="$document->status" /></td>
                    <td>{{ $document->uploader?->name ?? '—' }}</td>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($document->created_at) }}</td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="Preview"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-light" title="Download"><i class="bi bi-download"></i></a>
                            @can('verify', $document)
                                <button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form method="POST" action="{{ route('documents.verify', $document) }}">
                                            @csrf
                                            <button class="dropdown-item text-success"><i class="bi bi-check2-circle me-2"></i>Mark verified</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('documents.reject', $document) }}" data-confirm="Reject this document?">
                                            @csrf
                                            <button class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i>Reject</button>
                                        </form>
                                    </li>
                                    @can('delete', $document)
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('documents.destroy', $document) }}" data-confirm="Delete this document permanently?">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                                            </form>
                                        </li>
                                    @endcan
                                </ul>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty-state icon="bi-folder2-open" title="No documents found" message="Uploads across leads, customers and applications appear here." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
