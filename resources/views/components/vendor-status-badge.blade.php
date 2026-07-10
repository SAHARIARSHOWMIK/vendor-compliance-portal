@php
$colors = [
    'draft'               => 'badge-neutral',
    'invited'             => 'badge-info',
    'registered'          => 'badge-info',
    'documents_pending'   => 'badge-warning',
    'under_review'        => 'badge-brand',
    'correction_required' => 'badge-warning',
    'partially_approved'  => 'badge-warning',
    'fully_compliant'     => 'badge-success',
    'expiring_soon'       => 'badge-warning',
    'non_compliant'       => 'badge-danger',
    'suspended'           => 'badge-danger',
    'archived'            => 'badge-neutral',
];
$color = $colors[$status] ?? 'badge-neutral';
@endphp
<span class="{{ $color }}"><span class="badge-dot"></span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
