<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function store(UploadedFile $file, User $user, ?string $clinicId = null): Attachment
    {
        $path = $file->store(
            'documents/'.$user->organization_id.'/'.($clinicId ?? 'org'),
            'local'
        );

        return Attachment::create([
            'organization_id' => $user->organization_id,
            'clinic_id' => $clinicId,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $user->id,
        ]);
    }

    public function downloadUrl(Attachment $attachment): ?string
    {
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            return null;
        }

        return route('attachments.download', $attachment);
    }
}
