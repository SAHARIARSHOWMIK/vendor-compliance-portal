@php
$colors = [
    'fully_compliant'     => 'bg-green-100 text-green-700',
    'partially_compliant' => 'bg-yellow-100 text-yellow-700',
    'documents_missing'   => 'bg-amber-100 text-amber-700',
    'under_review'        => 'bg-purple-100 text-purple-700',
    'correction_required' => 'bg-orange-100 text-orange-700',
    'expiring_soon'       => 'bg-amber-100 text-amber-800',
    'non_compliant'       => 'bg-red-100 text-red-700',
    'suspended'           => 'bg-red-100 text-red-800',
];
$color = $colors[$status] ?? 'bg-slate-100 text-slate-600';
@endphp
<span class="inline-block rounded-full px-2 py-0.5 text-xs {{ $color }}">
    {{ ucwords(str_replace('_', ' ', $status)) }}
</span>
