@props([
    'title' => 'Nothing to display yet',
    'description' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'eb-empty-state']) }}>
    <div class="eb-empty-icon" aria-hidden="true">
        @if(isset($icon) && !$icon->isEmpty())
            {{ $icon }}
        @else
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        @endif
    </div>

    <div class="eb-empty-title">{{ $title }}</div>

    @if($description)
        <p class="eb-empty-desc">{{ $description }}</p>
    @endif

    @if(isset($actions) && !$actions->isEmpty())
        <div class="d-flex flex-wrap justify-content-center gap-2">
            {{ $actions }}
        </div>
    @elseif($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn btn-sm btn-primary">{{ $actionLabel }}</a>
    @endif
</div>

