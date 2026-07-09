<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\AuditTrailService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private AuditTrailService $auditTrail,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Document::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['documentType.category', 'clinic', 'owner', 'currentVersion']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->string('clinic_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderByDesc('updated_at')->paginate($request->integer('per_page', 15));

        return response()->json($documents);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'document_type_id' => ['required', 'uuid', Rule::exists('document_types', 'id')->where('organization_id', $user->organization_id)],
            'clinic_id' => ['nullable', 'uuid', Rule::exists('clinics', 'id')->where('organization_id', $user->organization_id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'expiry_date' => ['nullable', 'date'],
            'change_summary' => ['nullable', 'string'],
        ]);

        $type = DocumentType::findOrFail($validated['document_type_id']);

        $document = Document::create([
            'organization_id' => $user->organization_id,
            'clinic_id' => $validated['clinic_id'] ?? $user->default_clinic_id,
            'document_type_id' => $type->id,
            'document_number' => $this->documentService->generateDocumentNumber($user, $type->code),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
            'expiry_date' => $validated['expiry_date'] ?? now()->addDays($type->review_cycle_days)->toDateString(),
            'owner_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->documentService->createVersion($document, $user, [
            'change_summary' => $validated['change_summary'] ?? 'Versi awal dokumen',
        ]);

        $this->auditTrail->log('document.created', $document->fresh());

        return response()->json([
            'message' => 'Dokumen berhasil dibuat.',
            'data' => $document->load(['documentType.category', 'versions', 'owner']),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $document = Document::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['documentType.category', 'clinic', 'owner', 'versions', 'qrCode'])
            ->findOrFail($id);

        return response()->json(['data' => $document]);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        $document = Document::query()
            ->forOrganization($request->user()->organization_id)
            ->findOrFail($id);

        if (! in_array($document->status, ['draft', 'review', 'approved'])) {
            return response()->json(['message' => 'Dokumen tidak dapat dipublikasikan dari status saat ini.'], 422);
        }

        $document = $this->documentService->publish($document, $request->user());

        return response()->json([
            'message' => 'Dokumen berhasil dipublikasikan.',
            'data' => $document->load(['documentType.category', 'qrCode', 'currentVersion']),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        return response()->json([
            'total' => Document::forOrganization($orgId)->count(),
            'draft' => Document::forOrganization($orgId)->where('status', 'draft')->count(),
            'published' => Document::forOrganization($orgId)->where('status', 'published')->count(),
            'expired' => Document::forOrganization($orgId)->where('status', 'expired')->count(),
            'expiring_soon' => Document::forOrganization($orgId)
                ->where('status', 'published')
                ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->count(),
        ]);
    }
}
