<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $documents)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $documents = Document::query()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('uploaded_by', $request->user()->id))
            ->with(['documentType', 'documentable', 'uploader', 'verifier'])
            ->filter($request)
            ->when($request->filled('document_type_id'), fn ($q) => $q->where('document_type_id', $request->integer('document_type_id')))
            ->when($request->filled('documentable_type'), fn ($q) => $q->where('documentable_type', $request->string('documentable_type')))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($documents, 'documents.partials.table');
        }

        return view('documents.index', [
            'documents' => $documents,
            'documentTypes' => DocumentType::query()->active()->ordered()->get(),
            'stats' => [
                'total' => Document::query()->when(! $request->user()->isAdmin(), fn ($q) => $q->where('uploaded_by', $request->user()->id))->count(),
                'pending' => Document::query()->when(! $request->user()->isAdmin(), fn ($q) => $q->where('uploaded_by', $request->user()->id))->where('status', 'uploaded')->count(),
                'verified' => Document::query()->when(! $request->user()->isAdmin(), fn ($q) => $q->where('uploaded_by', $request->user()->id))->where('status', 'verified')->count(),
                'rejected' => Document::query()->when(! $request->user()->isAdmin(), fn ($q) => $q->where('uploaded_by', $request->user()->id))->where('status', 'rejected')->count(),
            ],
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Document::class);

        $data = $request->validate([
            'documentable_type' => ['required', 'in:lead,customer,loan_application,insurance_application,invoice,disbursement'],
            'documentable_id' => ['required', 'integer'],
            'document_type_id' => ['required', 'exists:document_types,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'is_required' => ['nullable', 'boolean'],
            'issued_number' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $owner = $this->resolveOwner($data['documentable_type'], (int) $data['documentable_id']);

        $document = $this->documents->store($owner, $request->file('file'), [
            'document_type_id' => $data['document_type_id'],
            'is_required' => (bool) ($data['is_required'] ?? false),
            'issued_number' => $data['issued_number'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ], $request->user());

        if ($request->expectsJson()) {
            return $this->ok('Document uploaded successfully.', ['document' => $this->payload($document)]);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function replace(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $document);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'issued_number' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $this->documents->replace($document, $request->file('file'), $request->user());
        $document->refresh()->forceFill(array_filter([
            'issued_number' => $request->input('issued_number'),
            'expires_at' => $request->input('expires_at'),
        ]))->save();

        if ($request->expectsJson()) {
            return $this->ok('Document replaced.', ['document' => $this->payload($document)]);
        }

        return back()->with('success', 'Document replaced successfully.');
    }

    public function verify(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('verify', $document);

        $this->documents->verify($document, $request->user());

        if ($request->expectsJson()) {
            return $this->ok('Document verified.', ['document' => $this->payload($document->refresh())]);
        }

        return back()->with('success', 'Document marked as verified.');
    }

    public function reject(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('verify', $document);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $this->documents->reject($document, $request->user(), $data['rejection_reason']);

        if ($request->expectsJson()) {
            return $this->ok('Document rejected.', ['document' => $this->payload($document->refresh())]);
        }

        return back()->with('success', 'Document rejected with remarks.');
    }

    public function destroy(Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $document);

        $this->documents->delete($document);

        if (request()->expectsJson()) {
            return $this->ok('Document deleted.');
        }

        return back()->with('success', 'Document deleted.');
    }

    /** Documents are streamed through the app - never publicly exposed. */
    public function preview(Document $document)
    {
        $this->authorize('view', $document);

        return $this->documents->stream($document);
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);

        return $this->documents->download($document);
    }

    protected function resolveOwner(string $type, int $id)
    {
        return match ($type) {
            'lead' => Lead::query()->findOrFail($id),
            'customer' => Customer::query()->findOrFail($id),
            'loan_application' => LoanApplication::query()->findOrFail($id),
            'insurance_application' => \App\Models\InsuranceApplication::query()->findOrFail($id),
            'invoice' => \App\Models\Invoice::query()->findOrFail($id),
            'disbursement' => \App\Models\Disbursement::query()->findOrFail($id),
        };
    }

    protected function payload(Document $document): array
    {
        $document->loadMissing(['documentType', 'verifier']);

        return [
            'id' => $document->id,
            'uuid' => $document->uuid,
            'type' => $document->documentType?->name,
            'status' => $document->status,
            'status_label' => \App\Support\StatusBadge::label($document->status),
            'status_color' => $document->statusColor(),
            'size' => $document->humanSize(),
            'is_image' => $document->isImage(),
            'preview_url' => route('documents.preview', $document),
            'download_url' => route('documents.download', $document),
            'verified_by' => $document->verifier?->name,
        ];
    }
}
