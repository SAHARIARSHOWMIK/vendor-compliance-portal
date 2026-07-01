<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accept Invitation — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-lg shadow p-8">
        <h1 class="text-xl font-semibold mb-1">Welcome to the Vendor Portal</h1>
        <p class="text-sm text-slate-500 mb-6">
            You have been invited to submit compliance documents for
            <strong>{{ $vendorUser->vendor->name }}</strong>.
            Please set your password to get started.
        </p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-50 text-red-700 text-sm px-4 py-3">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('vendor-portal.accept-invitation.store', $token) }}"
              class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Set Password</label>
                <input type="password" name="password" required autocomplete="new-password"
                    class="w-full rounded border-slate-300 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                    class="w-full rounded border-slate-300 text-sm">
            </div>

            <button type="submit"
                class="w-full rounded bg-slate-900 text-white py-2 text-sm font-medium hover:bg-slate-800">
                Accept Invitation & Continue
            </button>
        </form>
    </div>
</body>
</html>
