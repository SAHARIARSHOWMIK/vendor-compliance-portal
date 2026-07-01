<?php

namespace App\Http\Controllers\VendorPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Document\UploadDocumentRequest;
use App\Models\DocumentType;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorDocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService) {}

    /**
     * Document checklist: shows what's required, what's uploaded, what's missing.
     * This is the main vendor portal landing page after login.
     */
    public function checklist(Request $request): View
    {
        $vendor = $request->user()->vendor();

        if (! $vendor) {
            return view('vendor-portal.no-vendor');
        }

        $checklist     = $this->documentService->buildChecklist($vendor);
        $recentUpdates = VendorDocument::where('vendor_id', $vendor->id)
            ->with('documentType')
            ->latest('uploaded_at')
            ->limit(10)
            ->get();

        return view('vendor-portal.checklist', compact('vendor', 'checklist', 'recentUpdates'));
    }

    /**
     * Upload form: pre-selects the document type if ?type_id= is in the query.
     */
    public function uploadForm(Request $request): View
    {
        $vendor = $request->user()->vendor();

        $this->authorize('upload', [VendorDocument::class, $vendor]);

        $documentTypes   = DocumentType::where('is_active', true)->orderBy('sort_order')->get();
        $preselectedType = $request->filled('type_id')
            ? $documentTypes->firstWhere('id', $request->integer('type_id'))
            : null;

        // If re-uploading an existing document, grab its current state
        // to pre-populate expiry date and notes fields.
        $existingDocument = $preselectedType
            ? VendorDocument::where('vendor_id', $vendor->id)
                ->where('document_type_id', $preselectedType->id)
                ->first()
            : null;

        return view('vendor-portal.upload', compact(
            'vendor', 'documentTypes', 'preselectedType', 'existingDocument'
        ));
    }

    /**
     * Handle the upload POST. Delegates to DocumentService which enforces
     * versioning and private-disk storage invariants.
     */
    public function store(UploadDocumentRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('upload', [$vendor]);

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
            ->route('vendor-portal.checklist')
            ->with('status', "'{$documentType->name}' uploaded successfully and is now pending review.");
    }

    /**
     * Authenticated download — never serves a direct file URL.
     * Checks view permission before streaming.
     */
    public function download(VendorDocument $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $document);

        return $this->documentService->streamDownload($document);
    }

    /**
     * Download a specific historical version.
     */
    public function downloadVersion(\App\Models\DocumentVersion $version): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $document = $version->vendorDocument;
        $this->authorize('downloadVersion', $document);

        return $this->documentService->streamVersionDownload($version);
    }
}
