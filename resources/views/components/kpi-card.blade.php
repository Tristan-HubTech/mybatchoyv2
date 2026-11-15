@props([
    'label' => '',
    'value' => '0',
    'meta' => null,
    'metaTone' => 'up',
    'tone' => 'red',
    'href' => null,
    'counter' => null,
    'title' => null,
])

@php
    $circleClass = match ($tone) {
        'yellow' => 'kpi-yellow',
        'green' => 'kpi-green',
        default => 'kpi-red',
    };
    $metaClass = match ($metaTone) {
        'warn' => 'meta-warn',
        'danger' => 'meta-danger',
        default => 'meta-up',
    };
@endphp

@if($href)
    <a href="{{ $href }}" @if($title) title="{{ $title }}" @endif {{ $attributes->merge(['class' => 'eb-kpi-card']) }}>
@else
    <div @if($title) title="{{ $title }}" @endif {{ $attributes->merge(['class' => 'eb-kpi-card']) }} style="cursor: default;">
@endif
        @if(isset($icon) && !$icon->isEmpty())
            <div class="eb-kpi-circle {{ $circleClass }}">
                {{ $icon }}
            </div>
        @endif
        <div class="eb-kpi-body">
            <div class="eb-kpi-label">{{ $label }}</div>
            <div class="eb-kpi-value" @if($counter !== null) data-counter="{{ $counter }}" @endif>{{ $value }}</div>
            @if($meta)
                <div class="eb-kpi-meta {{ $metaClass }}">
                    <span>{{ $meta }}</span>
                </div>
            @endif
        </div>
        @if(isset($spark) && !$spark->isEmpty())
            {{ $spark }}
        @endif
@if($href)
    </a>
@else
    </div>
@endif

