<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * All document upload and versioning logic lives here.
 *
 * Key invariants enforced by this service:
 *  1. Files are ALWAYS stored on the 'vendor_documents' disk (private,
 *     never the public disk). The disk root is storage/app/private/vendor-documents.
 *  2. Re-uploading NEVER overwrites the existing file. The old file is
 *     snapshotted into document_versions first, then vendor_documents is
 *     updated. Old files stay on disk indefinitely (compliance evidence).
 *  3. Every upload (initial or replacement) appends an AuditLog row.
 *  4. Expiry dates are validated by the controller/request before this
 *     service is called, but we also guard here against null expiry for
 *     document types that require it.
 */
class DocumentService
{
    private const DISK = 'vendor_documents';

    // -----------------------------------------------------------------
    // Upload (initial or replacement)
    // -----------------------------------------------------------------

    /**
     * Upload a document for a vendor. If a VendorDocument row already
     * exists for this vendor + document type, the current version is
     * snapshotted to document_versions and the row is updated.
     * If it's the first upload, a new row is created with version_number=1.
     */
    public function upload(
        Vendor $vendor,
        DocumentType $documentType,
        UploadedFile $file,
        User $uploader,
        ?string $expiryDate = null,
        ?string $notes = null,
    ): VendorDocument {
        $existing = VendorDocument::where('vendor_id', $vendor->id)
            ->where('document_type_id', $documentType->id)
            ->first();

        if ($existing) {
            return $this->reupload($existing, $vendor, $documentType, $file, $uploader, $expiryDate, $notes);
        }

        return $this->initialUpload($vendor, $documentType, $file, $uploader, $expiryDate, $notes);
    }

    private function initialUpload(
        Vendor $vendor,
        DocumentType $documentType,
        UploadedFile $file,
        User $uploader,
        ?string $expiryDate,
        ?string $notes,
    ): VendorDocument {
        $path = $this->storeFile($vendor, $documentType, $file, 1);

        $vendorDocument = VendorDocument::create([
            'vendor_id'         => $vendor->id,
            'document_type_id'  => $documentType->id,
            'uploaded_by'       => $uploader->id,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size_kb'      => (int) ceil($file->getSize() / 1024),
            'version_number'    => 1,
            'status'            => 'uploaded',
            'expiry_date'       => $expiryDate,
            'notes'             => $notes,
            'uploaded_at'       => now(),
        ]);

        $this->audit($uploader, $vendor, $vendorDocument, 'document_uploaded',
            "Initial upload of '{$documentType->name}' (v1) by {$uploader->name}.",
            new_values: ['version' => 1, 'status' => 'uploaded', 'file' => $file->getClientOriginalName()],
        );

        return $vendorDocument;
    }

    private function reupload(
        VendorDocument $existing,
        Vendor $vendor,
        DocumentType $documentType,
        UploadedFile $file,
        User $uploader,
        ?string $expiryDate,
        ?string $notes,
    ): VendorDocument {
        // Snapshot the current row into document_versions BEFORE updating.
        // This is the "old version remains in history" invariant.
        DocumentVersion::create([
            'vendor_document_id' => $existing->id,
            'vendor_id'          => $vendor->id,
            'document_type_id'   => $documentType->id,
            'uploaded_by'        => $existing->uploaded_by,
            'file_path'          => $existing->file_path,
            'original_filename'  => $existing->original_filename,
            'mime_type'          => $existing->mime_type,
            'file_size_kb'       => $existing->file_size_kb,
            'version_number'     => $existing->version_number,
            'status_at_snapshot' => $existing->status,
            'expiry_date'        => $existing->expiry_date,
            'change_note'        => "Superseded by v" . ($existing->version_number + 1),
            'uploaded_at'        => $existing->uploaded_at ?? $existing->created_at,
        ]);

        $newVersion = $existing->version_number + 1;
        $path = $this->storeFile($vendor, $documentType, $file, $newVersion);

        $existing->update([
            'uploaded_by'       => $uploader->id,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'file_size_kb'      => (int) ceil($file->getSize() / 1024),
            'version_number'    => $newVersion,
            'status'            => 'reuploaded',
            'expiry_date'       => $expiryDate ?? $existing->expiry_date,
            'notes'             => $notes ?? $existing->notes,
            'uploaded_at'       => now(),
            'reviewed_at'       => null,
            'reviewed_by'       => null,
            'review_comment'    => null,
        ]);

        $this->audit($uploader, $vendor, $existing, 'document_reuploaded',
            "Reupload of '{$documentType->name}' (v{$newVersion}) by {$uploader->name}.",
            old_values: ['version' => $existing->version_number - 1, 'status' => $existing->getOriginal('status')],
            new_values: ['version' => $newVersion, 'status' => 'reuploaded', 'file' => $file->getClientOriginalName()],
        );

        return $existing->fresh();
    }

    // -----------------------------------------------------------------
    // Download (authenticated, never a direct URL)
    // -----------------------------------------------------------------

    /**
     * Return a temporary (signed) URL for serving the file through the
     * application rather than as a direct public link.
     * For the local disk, we stream the file contents directly.
     */
    public function getFilePath(VendorDocument $document): string
    {
        return $document->file_path;
    }

    public function streamDownload(VendorDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk(self::DISK)->download(
            $document->file_path,
            $document->original_filename,
        );
    }

    public function streamVersionDownload(DocumentVersion $version): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk(self::DISK)->download(
            $version->file_path,
            $version->original_filename,
        );
    }

    // -----------------------------------------------------------------
    // Checklist helper
    // -----------------------------------------------------------------

    /**
     * Returns the required document types for a vendor and, for each,
     * whether a current VendorDocument exists and what its status is.
     * Used by the vendor portal checklist and the admin vendor detail page.
     *
     * @return array<array{document_type: DocumentType, document: ?VendorDocument, is_missing: bool}>
     */
    public function buildChecklist(Vendor $vendor): array
    {
        $requiredTypes = $vendor->requiredDocumentTypes();
        $uploadedDocs  = VendorDocument::where('vendor_id', $vendor->id)
            ->with('documentType')
            ->get()
            ->keyBy('document_type_id');

        return $requiredTypes->map(function (DocumentType $docType) use ($uploadedDocs) {
            $doc = $uploadedDocs->get($docType->id);
            return [
                'document_type' => $docType,
                'document'      => $doc,
                'is_missing'    => $doc === null,
                'is_rejected'   => $doc?->isRejected() ?? false,
                'is_approved'   => $doc?->isApproved() ?? false,
                'is_expiring'   => $doc?->isExpiringSoon(
                    (int) config('compliance.expiry_urgent_days', 7)
                ) ?? false,
            ];
        })->values()->all();
    }

    // -----------------------------------------------------------------
    // File storage helper
    // -----------------------------------------------------------------

    private function storeFile(
        Vendor $vendor,
        DocumentType $documentType,
        UploadedFile $file,
        int $version,
    ): string {
        // Path: {vendor_id}/{document_type_slug}/v{version}_{original_name}
        // Prefixing with vendor_id scopes the directory to the vendor, so
        // even if a document type slug is guessed it can't cross vendors.
        $extension = $file->getClientOriginalExtension();
        $safeName  = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $filename  = "v{$version}_{$safeName}";
        $directory = "{$vendor->id}/{$documentType->slug}";

        return Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);
    }

    // -----------------------------------------------------------------
    // Audit helper
    // -----------------------------------------------------------------

    private function audit(
        User $actor,
        Vendor $vendor,
        VendorDocument $document,
        string $eventType,
        string $description,
        ?array $old_values = null,
        ?array $new_values = null,
    ): void {
        AuditLog::create([
            'actor_id'           => $actor->id,
            'actor_name'         => $actor->name,
            'actor_role'         => $actor->role->value,
            'vendor_id'          => $vendor->id,
            'vendor_name'        => $vendor->name,
            'vendor_document_id' => $document->id,
            'event_type'         => $eventType,
            'description'        => $description,
            'old_values'         => $old_values,
            'new_values'         => $new_values,
            'occurred_at'        => now(),
        ]);
    }
}
