@props([
    'title' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'eb-table-card']) }}>
    @if($title || (isset($headerActions) && !$headerActions->isEmpty()) || ($actionUrl && $actionLabel))
        <div class="eb-table-header">
            <div class="eb-table-title">
                @if(isset($icon) && !$icon->isEmpty())
                    {{ $icon }}
                @endif
                @if($title)
                    <span>{{ $title }}</span>
                @endif
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(isset($headerActions) && !$headerActions->isEmpty())
                    {{ $headerActions }}
                @endif
                @if($actionUrl && $actionLabel)
                    <a href="{{ $actionUrl }}" class="eb-panel-action">{{ $actionLabel }}</a>
                @endif
            </div>
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer) && !$footer->isEmpty())
        <div class="eb-table-footer">
            {{ $footer }}
        </div>
    @endif
</div>

