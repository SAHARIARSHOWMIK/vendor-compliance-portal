<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Models\DocumentType;
use App\Models\VendorDocument;
use App\Models\Vendor;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;

/**
 * Lets Compliance Admins and Super Admins upload documents on behalf of a
 * vendor — useful for onboarding situations where the vendor sends docs by
 * email and an admin digitises them, or for testing/demo purposes.
 *
 * Vendor portal users use VendorDocumentController instead, which is
 * scoped to the vendor-portal route group (role:vendor_user middleware).
 */
class AdminDocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    public function store(UploadDocumentRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('upload', [VendorDocument::class, $vendor]);

        $documentType = DocumentType::findOrFail($request->document_type_id);

        $this->documentService->upload(
            vendor:       $vendor,
            documentType: $documentType,
            file:         $request->file('file'),
            uploader:     $request->user(),
            expiryDate:   $request->expiry_date,
            notes:        $request->notes,
        );

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', "'{$documentType->name}' uploaded successfully.");
    }
}
