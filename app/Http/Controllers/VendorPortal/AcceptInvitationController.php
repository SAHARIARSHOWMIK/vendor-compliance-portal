<?php

namespace App\Http\Controllers\VendorPortal;

use App\Http\Controllers\Controller;
use App\Models\VendorUser;
use App\Services\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcceptInvitationController extends Controller
{
    public function __construct(private readonly VendorService $vendorService) {}

    public function show(string $token): View|RedirectResponse
    {
        $vendorUser = VendorUser::with('vendor')
            ->where('invitation_token', $token)
            ->where('invitation_status', 'pending')
            ->first();

        if (! $vendorUser) {
            return redirect()->route('login')
                ->with('status', 'This invitation link is invalid or has already been used.');
        }

        return view('vendor-portal.accept-invitation', compact('vendorUser', 'token'));
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $vendorUser = VendorUser::with(['vendor', 'user'])
            ->where('invitation_token', $token)
            ->where('invitation_status', 'pending')
            ->first();

        if (! $vendorUser) {
            return redirect()->route('login')
                ->with('status', 'This invitation link is invalid or has already been used.');
        }

        $request->validate([
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        // Set the vendor user's password (they were created with a random one)
        $vendorUser->user->update([
            'password' => bcrypt($request->password),
        ]);

        // Mark invitation accepted and advance vendor status
        $this->vendorService->acceptInvitation(
            $vendorUser->vendor,
            $vendorUser,
            $vendorUser->user,
        );

        // Log them in automatically after accepting
        auth()->login($vendorUser->user);

        return redirect()->route('vendor-portal.dashboard')
            ->with('status', "Welcome! Your account is set up. Please upload your required documents.");
    }
}
