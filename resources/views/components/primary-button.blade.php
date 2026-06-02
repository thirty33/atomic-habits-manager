<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center gap-2 px-[18px] py-[11px] rounded-lg bg-brand-700 text-paper font-medium text-[14px] leading-none hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-700/30 active:bg-brand-800 transition-colors'
]) }}>
    {{ $slot }}
</button>
