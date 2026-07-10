@php
$colors = [
    'fully_compliant'     => 'badge-success',
    'partially_compliant' => 'badge-warning',
    'documents_missing'   => 'badge-warning',
    'under_review'        => 'badge-brand',
    'correction_required' => 'badge-warning',
    'expiring_soon'       => 'badge-warning',
    'non_compliant'       => 'badge-danger',
    'suspended'           => 'badge-danger',
];
$color = $colors[$status] ?? 'badge-neutral';
@endphp
<span class="{{ $color }}"><span class="badge-dot"></span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
