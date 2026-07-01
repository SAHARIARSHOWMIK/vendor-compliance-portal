<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen flex">
        @auth
            <aside class="w-64 shrink-0 bg-slate-900 text-slate-100 flex flex-col">
                <div class="px-5 py-4 text-lg font-semibold border-b border-slate-800">
                    Vendor Compliance Portal
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                    @php $user = auth()->user(); @endphp

                    @if ($user->isSuperAdmin() || $user->isComplianceAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Admin Dashboard</a>
                    @endif

                    @if ($user->isSuperAdmin() || $user->isComplianceAdmin() || $user->isReviewer())
                        <a href="{{ route('reviewer.queue') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Review Queue</a>
                    @endif

                    @if ($user->isVendorUser())
                        <a href="{{ route('vendor-portal.dashboard') }}" class="block rounded px-3 py-2 hover:bg-slate-800">My Vendor Portal</a>
                    @endif

                    @if ($user->isAuditor() || $user->isSuperAdmin())
                        <a href="{{ route('auditor.dashboard') }}" class="block rounded px-3 py-2 hover:bg-slate-800">Auditor View</a>
                    @endif
                </nav>
                <div class="px-3 py-4 border-t border-slate-800 text-sm">
                    <div class="px-3 pb-2">
                        <div class="font-medium">{{ $user->name }}</div>
                        <div class="text-slate-400 text-xs">{{ $user->role->label() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left rounded px-3 py-2 hover:bg-slate-800">Log out</button>
                    </form>
                </div>
            </aside>
        @endauth

        <main class="flex-1">
            <div class="max-w-6xl mx-auto px-6 py-8">
                @if (session('status'))
                    <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
