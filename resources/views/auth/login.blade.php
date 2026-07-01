<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-white rounded-lg shadow p-8">
        <h1 class="text-xl font-semibold mb-1">Vendor Compliance Portal</h1>
        <p class="text-sm text-slate-500 mb-6">Sign in to continue</p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-50 text-red-700 text-sm px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium mb-1">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium mb-1">Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full rounded border-slate-300 focus:border-slate-500 focus:ring-slate-500 text-sm">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300">
                Remember me
            </label>

            <button type="submit"
                class="w-full bg-slate-900 text-white rounded py-2 text-sm font-medium hover:bg-slate-800">
                Sign in
            </button>
        </form>

        <p class="mt-6 text-xs text-slate-400">
            Demo accounts are seeded by <code>php artisan db:seed --class=DemoSeeder</code> -
            see the README for credentials covering all 5 roles.
        </p>
    </div>
</body>
</html>
