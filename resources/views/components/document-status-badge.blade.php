@php
$colors = [
    'required'             => 'bg-slate-100 text-slate-500',
    'uploaded'             => 'bg-blue-100 text-blue-700',
    'under_review'         => 'bg-purple-100 text-purple-700',
    'approved'             => 'bg-green-100 text-green-700',
    'rejected'             => 'bg-red-100 text-red-700',
    'correction_requested' => 'bg-orange-100 text-orange-700',
    'reuploaded'           => 'bg-blue-100 text-blue-700',
    'expiring_soon'        => 'bg-amber-100 text-amber-800',
    'expired'              => 'bg-red-100 text-red-800',
    'archived'             => 'bg-slate-100 text-slate-400',
];
$color = $colors[$status] ?? 'bg-slate-100 text-slate-600';
@endphp
<span class="inline-block rounded-full px-2 py-0.5 text-xs {{ $color }}">
    {{ ucwords(str_replace('_', ' ', $status)) }}
</span>
