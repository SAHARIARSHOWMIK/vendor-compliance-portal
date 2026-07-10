@php
$colors = [
    'required'             => 'badge-neutral',
    'uploaded'             => 'badge-info',
    'under_review'         => 'badge-brand',
    'approved'             => 'badge-success',
    'rejected'             => 'badge-danger',
    'correction_requested' => 'badge-warning',
    'reuploaded'           => 'badge-info',
    'expiring_soon'        => 'badge-warning',
    'expired'              => 'badge-danger',
    'archived'             => 'badge-neutral',
    'need_more_info'       => 'badge-info',
    'escalated'            => 'badge-danger',
];
$color = $colors[$status] ?? 'badge-neutral';
@endphp
<span class="{{ $color }}"><span class="badge-dot"></span>{{ ucwords(str_replace('_', ' ', $status)) }}</span>
