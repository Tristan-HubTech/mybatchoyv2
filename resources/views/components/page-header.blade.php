@props([
    'section' => null,
    'breadcrumb' => null,
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'eb-page-header']) }}>
    <div class="eb-page-header-top">
        <div>
            @if($section || $breadcrumb)
                <div class="eb-breadcrumb">
                    @if($section)
                        <span class="eb-section-pill">{{ $section }}</span>
                    @endif
                    @if($breadcrumb)
                        <span class="eb-breadcrumb-sep">/</span>
                        <span>{{ $breadcrumb }}</span>
                    @endif
                </div>
            @endif

            <h1 class="eb-page-title">{{ $title }}</h1>

            @if($subtitle)
                <p class="eb-page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions) && !$actions->isEmpty())
            <div class="eb-header-actions">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if(isset($filters) && !$filters->isEmpty())
        <div class="eb-header-filters">
            {{ $filters }}
        </div>
    @endif
</div>

