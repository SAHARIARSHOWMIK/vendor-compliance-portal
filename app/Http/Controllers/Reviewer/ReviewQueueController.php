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

        $baseQuery = VendorDocument::query()
            ->whereIn('vendor_documents.status', ['uploaded', 'reuploaded', 'under_review']);

        $summaryQuery = clone $baseQuery;
        if ($request->user()->isReviewer() && ! $request->boolean('show_all')) {
            $summaryQuery->whereHas('vendor', fn ($q) => $q->where('assigned_reviewer_id', $request->user()->id));
        }

        $queueSummary = [
            'total' => (clone $summaryQuery)->count(),
            'high_risk' => (clone $summaryQuery)->whereHas('vendor', fn ($q) => $q->where('risk_level', 'high'))->count(),
            'overdue' => (clone $summaryQuery)->where('uploaded_at', '<=', now()->subDays(3))->count(),
            'expiring' => (clone $summaryQuery)->whereNotNull('expiry_date')->where('expiry_date', '<=', now()->addDays(30))->count(),
        ];

        $query = VendorDocument::with(['vendor', 'documentType', 'vendor.assignedReviewer'])
            ->whereIn('vendor_documents.status', ['uploaded', 'reuploaded', 'under_review'])
            ->join('vendors', 'vendor_documents.vendor_id', '=', 'vendors.id')
            ->select('vendor_documents.*')
            ->orderByRaw("CASE vendors.risk_level WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderByRaw('CASE WHEN vendor_documents.expiry_date IS NOT NULL AND vendor_documents.expiry_date <= ? THEN 0 ELSE 1 END', [now()->addDays(30)->toDateString()])
            ->orderBy('vendor_documents.uploaded_at');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($term): void {
                $builder->where('vendors.name', 'like', "%{$term}%")
                    ->orWhere('vendor_documents.original_filename', 'like', "%{$term}%");
            });
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_documents.vendor_id', $request->vendor_id);
        }
        if ($request->filled('risk_level')) {
            $query->where('vendors.risk_level', $request->risk_level);
        }
        if ($request->filled('priority')) {
            if ($request->priority === 'overdue') {
                $query->where('vendor_documents.uploaded_at', '<=', now()->subDays(3));
            } elseif ($request->priority === 'expiring') {
                $query->whereNotNull('vendor_documents.expiry_date')
                    ->where('vendor_documents.expiry_date', '<=', now()->addDays(30));
            }
        }
        if ($request->user()->isReviewer() && ! $request->boolean('show_all')) {
            $query->where('vendors.assigned_reviewer_id', $request->user()->id);
        }

        $documents = $query->paginate(25)->withQueryString();
        $vendors = Vendor::orderBy('name')->get(['id', 'name']);

        return view('reviewer.queue', compact('documents', 'vendors', 'queueSummary'));
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
