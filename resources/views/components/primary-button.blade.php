<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-btn-primary border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-btn-primary-hover focus:bg-btn-primary-hover active:bg-btn-primary-hover focus:outline-none focus:ring-4 focus:ring-btn-primary/30 transition-colors']) }}>
    {{ $slot }}
</button>
