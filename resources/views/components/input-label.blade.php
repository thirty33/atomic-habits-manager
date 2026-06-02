@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[13px] font-medium text-ink-700']) }}>
    {{ $value ?? $slot }}
</label>
