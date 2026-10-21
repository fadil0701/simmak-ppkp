<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentQrCode;
use App\Models\DocumentReview;
use App\Models\DocumentType;
use App\Services\AttachmentService;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DmsController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private AttachmentService $attachmentService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orgId = $user->organization_id;

        $query = Document::query()
            ->forOrganization($orgId)
            ->with(['documentType.category'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Document::forOrganization($orgId)->count(),
            'draft' => Document::forOrganization($orgId)->where('status', 'draft')->count(),
            'published' => Document::forOrganization($orgId)->where('status', 'published')->count(),
            'expired' => Document::forOrganization($orgId)->where('status', 'expired')->count(),
            'expiring_soon' => Document::forOrganization($orgId)
                ->where('status', 'published')
                ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
        ];

        return view('dms.index', compact('documents', 'stats'));
    }

    public function show(Request $request, Document $document): View
    {
        abort_unless($request->user()->belongsToOrganization($document->organization_id), 403);

        $document->load(['documentType.category', 'owner', 'versions.attachment', 'qrCode', 'clinic', 'reviews.reviewer']);

        return view('dms.show', compact('document'));
    }

    public function create(Request $request): View
    {
        $types = DocumentType::query()
            ->forOrganization($request->user()->organization_id)
            ->with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('dms.create', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'document_type_id' => ['required', 'uuid', Rule::exists('document_types', 'id')->where('organization_id', $user->organization_id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'change_summary' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx'],
        ]);

        $type = DocumentType::findOrFail($validated['document_type_id']);

        $document = Document::create([
            'organization_id' => $user->organization_id,
            'clinic_id' => $user->default_clinic_id,
            'document_type_id' => $type->id,
            'document_number' => $this->documentService->generateDocumentNumber($user, $type->code),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
            'expiry_date' => now()->addDays($type->review_cycle_days)->toDateString(),
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $versionData = ['change_summary' => $validated['change_summary'] ?? 'Versi awal dokumen'];

        if ($request->hasFile('file')) {
            $attachment = $this->attachmentService->store(
                $request->file('file'),
                $user,
                $user->default_clinic_id
            );
            $versionData['attachment_id'] = $attachment->id;
            $versionData['file_name'] = $attachment->original_name;
        }

        $this->documentService->createVersion($document, $user, $versionData);

        return redirect()->route('dms.show', $document)->with('status', 'Dokumen berhasil dibuat.');
    }

    public function publish(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($document->organization_id), 403);

        if (! in_array($document->status, ['draft', 'review', 'approved'])) {
            return back()->withErrors(['document' => 'Dokumen tidak dapat dipublikasikan dari status saat ini.']);
        }

        $this->documentService->publish($document, $request->user());

        return back()->with('status', 'Dokumen berhasil dipublikasikan.');
    }

    public function submitReview(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($document->organization_id), 403);

        if ($document->status !== 'draft') {
            return back()->withErrors(['document' => 'Hanya dokumen berstatus draft yang dapat diajukan untuk review.']);
        }

        $document->update([
            'status' => 'review',
            'updated_by' => $request->user()->id,
        ]);

        DocumentReview::create([
            'organization_id' => $document->organization_id,
            'document_id' => $document->id,
            'reviewer_id' => $request->user()->id,
            'step_order' => $document->reviews()->count() + 1,
            'status' => 'pending',
            'comments' => $request->input('comments'),
        ]);

        return back()->with('status', 'Dokumen berhasil diajukan untuk review.');
    }

    public function approveReview(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($document->organization_id), 403);

        if ($document->status !== 'review') {
            return back()->withErrors(['document' => 'Hanya dokumen berstatus review yang dapat disetujui.']);
        }

        $review = $document->reviews()->where('status', 'pending')->orderBy('step_order')->first();

        if ($review) {
            $review->update([
                'status' => 'approved',
                'comments' => $request->input('comments') ?? $review->comments,
                'reviewed_at' => now(),
            ]);
        } else {
            DocumentReview::create([
                'organization_id' => $document->organization_id,
                'document_id' => $document->id,
                'reviewer_id' => $request->user()->id,
                'step_order' => $document->reviews()->count() + 1,
                'status' => 'approved',
                'comments' => $request->input('comments'),
                'reviewed_at' => now(),
            ]);
        }

        $document->update([
            'status' => 'approved',
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Review dokumen disetujui.');
    }

    public function verify(string $token): View
    {
        $qrCode = DocumentQrCode::query()
            ->where('qr_token', $token)
            ->where('is_active', true)
            ->with([
                'document.documentType.category',
                'document.clinic',
                'document.owner',
                'document.currentVersion',
            ])
            ->firstOrFail();

        if ($qrCode->expires_at?->isPast()) {
            abort(410, 'QR code kedaluwarsa.');
        }

        $document = $qrCode->document;

        abort_unless($document && $document->status === 'published', 404);

        return view('dms.verify', compact('qrCode', 'document'));
    }
}
