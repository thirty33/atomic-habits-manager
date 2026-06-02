@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg bg-brand-50 border border-brand-100 p-4 text-[13px] text-brand-800']) }}>
        {{ $status }}
    </div>
@endif
