<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\Organization;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@simmak.ppkp.local')->first();

        if (! $organization || ! $admin) {
            return;
        }

        $categories = [
            ['code' => 'KEB', 'name' => 'Kebijakan', 'doc_class' => 'kebijakan', 'retention_days' => 1095],
            ['code' => 'SOP', 'name' => 'SOP', 'doc_class' => 'sop', 'retention_days' => 730],
            ['code' => 'PED', 'name' => 'Pedoman', 'doc_class' => 'pedoman', 'retention_days' => 730],
            ['code' => 'FRM', 'name' => 'Formulir', 'doc_class' => 'formulir', 'retention_days' => 365],
            ['code' => 'IK', 'name' => 'Instruksi Kerja', 'doc_class' => 'ik', 'retention_days' => 365],
        ];

        $documentService = app(DocumentService::class);

        foreach ($categories as $cat) {
            $category = DocumentCategory::create([
                ...$cat,
                'organization_id' => $organization->id,
                'is_active' => true,
                'created_by' => $admin->id,
            ]);

            $type = DocumentType::create([
                'organization_id' => $organization->id,
                'category_id' => $category->id,
                'code' => $cat['code'],
                'name' => $cat['name'],
                'review_cycle_days' => $cat['retention_days'],
                'requires_acknowledgment' => in_array($cat['doc_class'], ['sop', 'ik']),
                'is_active' => true,
            ]);

            if ($cat['code'] === 'SOP') {
                $doc = Document::create([
                    'organization_id' => $organization->id,
                    'clinic_id' => $admin->default_clinic_id,
                    'document_type_id' => $type->id,
                    'document_number' => $documentService->generateDocumentNumber($admin, $type->code),
                    'title' => 'SOP Pelayanan Pasien Rawat Jalan',
                    'status' => 'published',
                    'effective_date' => now()->subMonths(2)->toDateString(),
                    'expiry_date' => now()->addMonths(10)->toDateString(),
                    'owner_id' => $admin->id,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]);

                $version = $documentService->createVersion($doc, $admin, [
                    'change_summary' => 'Publikasi versi 1.0',
                    'status' => 'published',
                ]);
                $version->update(['published_at' => now()->subMonths(2)]);
                $documentService->ensureQrCode($doc->fresh());
            }
        }
    }
}
