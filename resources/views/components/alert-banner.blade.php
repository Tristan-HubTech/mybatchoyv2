@props([
    'showErrors' => true,
])

@if(session('success'))
    <div class="eb-alert eb-alert-success d-flex align-items-start gap-2" role="status">
        <span class="fw-bold" aria-hidden="true">&#10003;</span>
        <div class="flex-grow-1">{{ session('success') }}</div>
    </div>
@endif

@if(session('warning'))
    <div class="eb-alert eb-alert-warning d-flex align-items-start gap-2" role="alert">
        <span class="fw-bold" aria-hidden="true">&#9888;</span>
        <div class="flex-grow-1">{{ session('warning') }}</div>
    </div>
@endif

@if(session('error'))
    <div class="eb-alert eb-alert-danger d-flex align-items-start gap-2" role="alert">
        <span class="fw-bold" aria-hidden="true">&#9888;</span>
        <div class="flex-grow-1">{{ session('error') }}</div>
    </div>
@endif

@if(session('status'))
    <div class="eb-alert eb-alert-info d-flex align-items-start gap-2" role="status">
        <span class="fw-bold" aria-hidden="true">&#8505;</span>
        <div class="flex-grow-1">{{ session('status') }}</div>
    </div>
@endif

@if($showErrors && isset($errors) && $errors->any())
    <div class="eb-alert eb-alert-danger" role="alert">
        <div class="fw-bold mb-1">Please fix the following before continuing:</div>
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

