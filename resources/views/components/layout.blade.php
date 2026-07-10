<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Enterprise vendor onboarding, compliance document review, expiry monitoring, reporting, and audit management.">
    <title>{{ $title ?? config('app.name') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @auth
        @php
            $user = auth()->user();
            $isAdmin = $user->isSuperAdmin() || $user->isComplianceAdmin();
            $isReviewer = $isAdmin || $user->isReviewer();
            $isAuditor = $user->isAuditor() || $user->isSuperAdmin();
        @endphp

        <div class="overlay" data-sidebar-overlay></div>
        <aside class="sidebar" data-sidebar>
            <div class="sidebar-brand">
                <div class="brand-mark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-4A11.95 11.95 0 0112 3a11.95 11.95 0 01-8.6 3C3.14 7.16 3 8.37 3 9.6 3 15.1 6.84 19.7 12 21c5.16-1.3 9-5.9 9-11.4 0-1.23-.14-2.44-.4-3.6z"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-bold tracking-wide text-white">VendorGuard</div>
                    <div class="truncate text-[11px] text-slate-500">Compliance operations</div>
                </div>
                <button type="button" class="ml-auto rounded-lg p-2 text-slate-500 hover:bg-white/5 hover:text-white lg:hidden" data-sidebar-close aria-label="Close navigation">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto pb-6">
                <div class="nav-section">Workspace</div>
                @if ($isAdmin)
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 13h6V4H4v9zm10 7h6v-9h-6v9zM4 20h6v-3H4v3zm10-13h6V4h-6v3z"/></svg>
                        Command center
                    </a>
                    <a href="{{ route('admin.vendors.index') }}" class="nav-link {{ request()->routeIs('admin.vendors.*') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M9 14h.01M9 18h.01M15 10h.01M15 14h.01M15 18h.01"/></svg>
                        Vendor portfolio
                    </a>
                @endif

                @if ($isReviewer)
                    <a href="{{ route('reviewer.queue') }}" class="nav-link {{ request()->routeIs('reviewer.*') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
                        Review queue
                        @if ($pendingReviewCount > 0)<span class="nav-badge">{{ $pendingReviewCount }}</span>@endif
                    </a>
                @endif

                @if ($user->isVendorUser())
                    <a href="{{ route('vendor-portal.dashboard') }}" class="nav-link {{ request()->routeIs('vendor-portal.*') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m-9 4h12a2 2 0 002-2V7l-8-4-8 4v12a2 2 0 002 2z"/></svg>
                        My compliance
                    </a>
                @endif

                @if ($isAuditor)
                    <a href="{{ route('auditor.dashboard') }}" class="nav-link {{ request()->routeIs('auditor.dashboard') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-4.4 0-8 4-8 4s3.6 4 8 4 8-4 8-4-3.6-4-8-4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                        Assurance view
                    </a>
                    <a href="{{ route('auditor.vendors') }}" class="nav-link {{ request()->routeIs('auditor.vendors*') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1m-5 6H2v-2a4 4 0 014-4h6a4 4 0 014 4v2zm0-10a4 4 0 100-8 4 4 0 000 8zm6 1a3 3 0 100-6"/></svg>
                        Read-only vendors
                    </a>
                @endif

                <div class="nav-section">Operations</div>
                @if ($isAdmin)
                    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V9m5 10V5m5 14v-7m5 7V3"/></svg>
                        Reports & evidence
                        @if ($expiringDocumentCount > 0)<span class="nav-badge">{{ $expiringDocumentCount }}</span>@endif
                    </a>
                @elseif ($user->isAuditor())
                    <a href="{{ route('auditor.audit-log') }}" class="nav-link {{ request()->routeIs('auditor.audit-log*') ? 'is-active' : '' }}">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14H5V6a2 2 0 012-2z"/></svg>
                        Audit evidence
                    </a>
                @endif

                <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'is-active' : '' }}">
                    <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0h6z"/></svg>
                    Notifications
                    @if ($unreadNotificationCount > 0)<span class="nav-badge">{{ $unreadNotificationCount }}</span>@endif
                </a>
            </div>

            <div class="border-t border-white/10 p-4">
                <div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
                    <div class="flex items-center gap-3">
                        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-500/20 text-sm font-bold text-indigo-200">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-white">{{ $user->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $user->role->label() }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg p-2 text-slate-500 transition hover:bg-white/5 hover:text-white" title="Log out">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H8m5 4v1a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h5a3 3 0 013 3v1"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="topbar">
                <button type="button" class="mr-3 rounded-xl border border-slate-200 p-2 text-slate-600 lg:hidden" data-sidebar-open aria-label="Open navigation">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="relative hidden max-w-xl flex-1 md:block">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.4-4.4m2.4-5.1a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg>
                    <input data-command-search type="search" placeholder="Search vendors, documents, reports…  Ctrl K" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm focus:bg-white">
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <div class="hidden items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 sm:flex">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Operations healthy
                    </div>
                    <a href="{{ route('notifications.index') }}" class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0h6z"/></svg>
                        @if ($unreadNotificationCount > 0)<span class="absolute -right-1 -top-1 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ min($unreadNotificationCount, 99) }}</span>@endif
                    </a>
                    @if ($isAdmin)
                        <a href="{{ route('admin.vendors.create') }}" class="btn-primary hidden sm:inline-flex">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                            Add vendor
                        </a>
                    @endif
                </div>
            </header>

            <main class="page-wrap">
                @if (session('status'))
                    <div class="toast transition duration-200" data-toast>
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-100"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></div>
                            <div><div class="font-semibold">Action completed</div><div class="mt-0.5 text-emerald-700/80">{{ session('status') }}</div></div>
                        </div>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    @else
        <main>{{ $slot }}</main>
    @endauth
</div>
</body>
</html>
