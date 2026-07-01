<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\MakeReviewDecisionRequest;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewQueueController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewQueue', VendorDocument::class);

        $query = VendorDocument::with(['vendor', 'documentType', 'vendor.assignedReviewer'])
            ->whereIn('status', ['uploaded', 'reuploaded', 'under_review'])
            ->join('vendors', 'vendor_documents.vendor_id', '=', 'vendors.id')
            ->select('vendor_documents.*')
            ->orderByRaw("CASE vendors.risk_level WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN vendor_documents.expiry_date IS NOT NULL AND vendor_documents.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 0 ELSE 1 END")
            ->orderBy('vendor_documents.uploaded_at', 'asc');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_documents.vendor_id', $request->vendor_id);
        }
        if ($request->filled('risk_level')) {
            $query->where('vendors.risk_level', $request->risk_level);
        }
        if ($request->user()->isReviewer() && ! $request->boolean('show_all')) {
            $query->where('vendors.assigned_reviewer_id', $request->user()->id);
        }

        $documents = $query->paginate(25)->withQueryString();
        $vendors   = Vendor::orderBy('name')->get(['id', 'name']);

        return view('reviewer.queue', compact('documents', 'vendors'));
    }

    public function show(VendorDocument $document): View
    {
        $this->authorize('decide', $document);

        $document->load([
            'vendor',
            'documentType',
            'uploader',
            'reviewer',
            'versions' => fn ($q) => $q->with('uploader')->orderBy('version_number', 'desc'),
            'reviews'  => fn ($q) => $q->with('reviewer')->orderBy('reviewed_at', 'desc'),
        ]);

        return view('reviewer.document-review', compact('document'));
    }

    public function decide(MakeReviewDecisionRequest $request, VendorDocument $document): RedirectResponse
    {
        $comment = $request->comment ?? '';

        match ($request->decision) {
            'approved'             => $this->reviewService->approve($document, $comment, $request->user()),
            'rejected'             => $this->reviewService->reject($document, $comment, $request->user()),
            'correction_requested' => $this->reviewService->requestCorrection($document, $comment, $request->user()),
            'need_more_info'       => $this->reviewService->requestMoreInfo($document, $comment, $request->user()),
            'escalated'            => $this->reviewService->escalate($document, $comment, $request->user()),
        };

        $label = ucwords(str_replace('_', ' ', $request->decision));

        return redirect()
            ->route('reviewer.queue')
            ->with('status', "Decision recorded: {$label} — {$document->documentType->name} for {$document->vendor->name}.");
    }
}
