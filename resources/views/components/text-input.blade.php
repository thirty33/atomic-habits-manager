@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge([
    'class' => 'block w-full bg-card px-3.5 py-[11px] rounded-lg text-[14.5px] text-ink-900 placeholder:text-ink-400 shadow-[inset_0_0_0_1px_rgb(var(--color-line-200))] focus:shadow-[inset_0_0_0_1.5px_rgb(var(--color-brand-700))] focus:outline-none transition-shadow disabled:opacity-60 disabled:cursor-not-allowed'
]) }}>
