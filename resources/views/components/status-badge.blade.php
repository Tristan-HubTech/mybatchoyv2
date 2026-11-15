@props([
    'variant' => 'neutral',
    'label' => null,
])

@php
    $badgeClass = match ($variant) {
        'success' => 'eb-badge eb-badge-success',
        'warning' => 'eb-badge eb-badge-warning',
        'danger' => 'eb-badge eb-badge-danger',
        'tx-in' => 'eb-tx-pill tx-in',
        'tx-out' => 'eb-tx-pill tx-out',
        'tx-adj' => 'eb-tx-pill tx-adj',
        'tx-spoil' => 'eb-tx-pill tx-spoil',
        default => 'eb-badge eb-badge-neutral',
    };
@endphp

<span {{ $attributes->merge(['class' => $badgeClass]) }}>
    {{ $label ?? $slot }}
</span>

