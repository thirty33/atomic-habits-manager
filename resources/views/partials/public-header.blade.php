{{--
    Shared public header for marketing/legal pages.
    Variant: $variant = 'desktop' | 'mobile' (defaults to desktop, includes lg responsive nav).
--}}
<header class="flex items-center justify-between px-6 lg:px-12 pt-6 lg:pt-8">
    <div class="flex items-center gap-6 lg:gap-10">
        <a href="{{ url('/') }}" class="flex items-center gap-2.5 select-none">
            <span class="logo-mark">A</span>
            <span class="font-display text-[19px] leading-none text-ink-900">Átomo<span class="text-brand-700">.</span></span>
        </a>
        <nav class="hidden lg:flex items-center gap-7">
            <a href="#features" class="text-sm text-ink-500 hover:text-ink-900 transition">Características</a>
            <a href="#method" class="text-sm text-ink-500 hover:text-ink-900 transition">Método</a>
        </nav>
    </div>
    <div class="flex items-center gap-2">
        @auth
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 text-paper font-medium text-[13.5px] hover:bg-brand-800 transition">
                Ir al dashboard
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        @else
            <a href="{{ route('login') }}"
               class="hidden lg:inline-flex items-center px-4 py-2.5 text-[13.5px] text-ink-900 rounded-lg hover:bg-line-100 transition">
                Iniciar sesión
            </a>
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-brand-700 text-paper font-medium text-[13.5px] hover:bg-brand-800 transition">
                Empezar gratis
            </a>
        @endauth
    </div>
</header>
