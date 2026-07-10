<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Secure access to VendorGuard compliance operations.">
    <title>Sign in · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950">
<div class="grid min-h-screen lg:grid-cols-[1.15fr_.85fr]">
    <section class="relative hidden overflow-hidden border-r border-white/10 lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(79,70,229,.38),_transparent_38%),radial-gradient(circle_at_70%_70%,_rgba(14,165,233,.20),_transparent_32%)]"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="brand-mark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-4A11.95 11.95 0 0112 3a11.95 11.95 0 01-8.6 3C3.14 7.16 3 8.37 3 9.6 3 15.1 6.84 19.7 12 21c5.16-1.3 9-5.9 9-11.4 0-1.23-.14-2.44-.4-3.6z"/></svg>
                </div>
                <div>
                    <div class="font-bold tracking-wide text-white">VendorGuard</div>
                    <div class="text-xs text-slate-500">Compliance operations platform</div>
                </div>
            </div>
        </div>

        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-400/10 px-3 py-1.5 text-xs font-semibold text-indigo-200">
                <span class="h-1.5 w-1.5 rounded-full bg-cyan-300"></span>
                Enterprise vendor assurance
            </div>
            <h1 class="mt-6 text-5xl font-bold leading-tight tracking-tight text-white">Make every vendor relationship audit-ready.</h1>
            <p class="mt-5 max-w-xl text-lg leading-8 text-slate-400">Centralize onboarding, evidence collection, approvals, expiry monitoring, risk visibility, and immutable audit history in one controlled workflow.</p>

            <div class="mt-10 grid max-w-2xl grid-cols-3 gap-3">
                @foreach ([
                    ['5 roles', 'Policy-controlled access'],
                    ['100%', 'Traceable decisions'],
                    ['24/7', 'Expiry monitoring'],
                ] as [$value, $label])
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                        <div class="text-2xl font-bold text-white">{{ $value }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative z-10 flex items-center gap-6 text-xs text-slate-600">
            <span>Private document storage</span><span>Human approvals</span><span>Immutable audit log</span>
        </div>
    </section>

    <main class="flex items-center justify-center bg-slate-50 px-5 py-12 sm:px-10">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="brand-mark">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.6-4A11.95 11.95 0 0112 3a11.95 11.95 0 01-8.6 3C3.14 7.16 3 8.37 3 9.6 3 15.1 6.84 19.7 12 21c5.16-1.3 9-5.9 9-11.4 0-1.23-.14-2.44-.4-3.6z"/></svg>
                </div>
                <div class="font-bold text-slate-950">VendorGuard</div>
            </div>

            <div class="panel p-7 sm:p-9">
                <div class="page-kicker">Secure workspace</div>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Welcome back</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Sign in to manage vendor onboarding, reviews, compliance evidence, and reports.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="field-label">Work email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                               class="field-control w-full" placeholder="you@company.com">
                    </div>
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="field-label">Password</label>
                            <span class="mb-1.5 text-xs text-slate-400">Demo: password</span>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password"
                               class="field-control w-full" placeholder="Enter your password">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Keep me signed in on this device
                    </label>
                    <button type="submit" class="btn-primary w-full">Sign in to workspace</button>
                </form>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-white px-5 py-4 text-xs leading-5 text-slate-500">
                <div class="font-semibold text-slate-700">Portfolio demo accounts</div>
                <div class="mt-1">Use <code class="rounded bg-slate-100 px-1.5 py-0.5">super.admin@demo.test</code> with password <code class="rounded bg-slate-100 px-1.5 py-0.5">password</code>. Other role accounts are listed in the README.</div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
