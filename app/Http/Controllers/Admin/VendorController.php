<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\CreateVendorRequest;
use App\Http\Requests\Vendor\InviteVendorUserRequest;
use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(private readonly VendorService $vendorService)
    {
    }

    // -----------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Vendor::class);

        $query = Vendor::query()
            ->with(['assignedReviewer', 'latestComplianceCheck'])
            ->orderByRaw("FIELD(status, 'suspended','correction_required','non_compliant','expiring_soon','under_review','documents_pending') DESC")
            ->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }
        if ($request->filled('compliance_status')) {
            $query->where('compliance_status', $request->compliance_status);
        }
        if ($request->filled('reviewer')) {
            $query->where('assigned_reviewer_id', $request->reviewer);
        }
        if ($request->boolean('expiring_soon')) {
            $query->where('compliance_status', 'expiring_soon');
        }

        $vendors   = $query->paginate(25)->withQueryString();
        $reviewers = User::where('role', 'reviewer')->orderBy('name')->get();

        return view('admin.vendors.index', compact('vendors', 'reviewers'));
    }

    // -----------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------

    public function create(): View
    {
        $this->authorize('create', Vendor::class);
        $reviewers = User::where('role', 'reviewer')->orderBy('name')->get();
        return view('admin.vendors.create', compact('reviewers'));
    }

    public function store(CreateVendorRequest $request): RedirectResponse
    {
        $vendor = $this->vendorService->create($request->validated(), $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', "Vendor '{$vendor->name}' created successfully.");
    }

    // -----------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------

    public function show(Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        $vendor->load([
            'assignedReviewer',
            'vendorUsers.user',
            'documents.documentType',
            'documents.reviewer',
            'latestComplianceCheck',
            'auditLogs' => fn ($q) => $q->orderBy('occurred_at', 'desc')->limit(30),
        ]);

        $requiredDocTypes = $vendor->requiredDocumentTypes();
        $reviewers        = User::where('role', 'reviewer')->orderBy('name')->get();

        return view('admin.vendors.show', compact('vendor', 'requiredDocTypes', 'reviewers'));
    }

    // -----------------------------------------------------------------
    // Edit / Update
    // -----------------------------------------------------------------

    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);
        $reviewers = User::where('role', 'reviewer')->orderBy('name')->get();
        return view('admin.vendors.edit', compact('vendor', 'reviewers'));
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendorService->update($vendor, $request->validated(), $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', 'Vendor profile updated.');
    }

    // -----------------------------------------------------------------
    // Invitation
    // -----------------------------------------------------------------

    public function inviteForm(Vendor $vendor): View
    {
        $this->authorize('invite', $vendor);
        return view('admin.vendors.invite', compact('vendor'));
    }

    public function invite(InviteVendorUserRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->vendorService->invite(
            $vendor,
            $request->email,
            $request->contact_name,
            $request->user(),
        );

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', "Invitation sent to {$request->email}.");
    }

    // -----------------------------------------------------------------
    // Lifecycle actions
    // -----------------------------------------------------------------

    public function suspend(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('suspend', $vendor);
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->vendorService->suspend($vendor, $request->reason, $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', "Vendor '{$vendor->name}' suspended.");
    }

    public function reinstate(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('reinstate', $vendor);
        $this->vendorService->reinstate($vendor, $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', "Vendor '{$vendor->name}' reinstated.");
    }

    public function archive(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('archive', $vendor);
        $this->vendorService->archive($vendor, $request->user());

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', "Vendor '{$vendor->name}' archived.");
    }

    public function assignReviewer(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorize('assignReviewer', $vendor);
        $request->validate(['reviewer_id' => ['nullable', 'exists:users,id']]);

        $this->vendorService->assignReviewer($vendor, $request->reviewer_id, $request->user());

        return redirect()
            ->route('admin.vendors.show', $vendor)
            ->with('status', 'Reviewer assignment updated.');
    }
}
