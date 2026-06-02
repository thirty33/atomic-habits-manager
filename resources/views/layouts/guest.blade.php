<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Atomic Habits Manager') }}</title>

        {{-- Bunny Fonts: Inter (body), Instrument Serif (display), JetBrains Mono (eyebrows/metadata) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|instrument-serif:400,400i|jetbrains-mono:400,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body antialiased bg-paper text-ink-900 min-h-screen">

        <div class="min-h-screen grid grid-cols-1 lg:grid-cols-12">

            {{-- ─────────── BRAND PANEL (desktop only) ─────────── --}}
            <aside class="hidden lg:flex lg:col-span-5 relative overflow-hidden bg-brand-800 text-paper p-10 flex-col justify-between">
                {{-- Sutile dotted texture --}}
                <div class="absolute inset-0 opacity-[0.07] pointer-events-none"
                     style="background-image: radial-gradient(circle, rgb(var(--color-paper)) 1px, transparent 1px); background-size: 22px 22px;"></div>

                {{-- Logo --}}
                <a href="{{ url('/') }}" class="relative flex items-center gap-2.5 select-none">
                    <span class="logo-mark lg" style="background: rgb(var(--color-paper)); color: rgb(var(--color-brand-800));">A</span>
                    <span class="font-display text-[22px] leading-none">Átomo<span class="opacity-60">.</span></span>
                </a>

                {{-- Quote --}}
                <div class="relative max-w-[420px]">
                    <div class="font-mono text-[11px] tracking-[0.12em] opacity-60 mb-5">— PRINCIPIO 03</div>
                    <p class="display text-[44px] leading-[1.02]">
                        Los hábitos son <em class="font-display italic opacity-90">interés compuesto</em> aplicado a uno mismo.
                    </p>
                    <p class="mt-5 text-[14px] opacity-70 leading-relaxed">
                        Pequeñas mejoras del 1% no se sienten relevantes en el día. Pero a doce meses son la diferencia entre quien fuiste y quien decidiste ser.
                    </p>
                </div>

                {{-- Bottom: quote attribution for visual balance --}}
                <div class="relative font-mono text-[11px] tracking-[0.12em] opacity-50">
                    — JAMES CLEAR · HÁBITOS ATÓMICOS
                </div>
            </aside>

            {{-- ─────────── FORM PANEL ─────────── --}}
            <main class="col-span-1 lg:col-span-7 flex flex-col min-h-screen">

                {{-- Top bar --}}
                <div class="flex items-center justify-between px-6 lg:px-12 pt-6 lg:pt-10">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-[13px] text-ink-500 hover:text-ink-900 transition">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5m6-6-6 6 6 6"/>
                        </svg>
                        Volver al inicio
                    </a>
                    <div class="text-[12.5px] lg:text-[13px] text-ink-500">
                        {{ $topRight ?? '' }}
                    </div>
                </div>

                {{-- Centered form area --}}
                <div class="flex-1 flex items-center justify-center px-6 lg:px-12 py-12 lg:py-8">
                    <div class="w-full max-w-[440px]">
                        @if ($eyebrow)
                            <div class="font-mono text-[11px] tracking-[0.12em] text-brand-700 uppercase">{{ $eyebrow }}</div>
                        @endif

                        @isset($title)
                            <h1 class="display text-[44px] lg:text-[56px] mt-3 leading-none text-ink-900">
                                {{ $title }}
                            </h1>
                        @endisset

                        @isset($sub)
                            <p class="mt-4 text-[14px] lg:text-[14.5px] text-ink-500 leading-relaxed">
                                {{ $sub }}
                            </p>
                        @endisset

                        <div class="@isset($title) mt-9 @endisset">
                            {{ $slot }}
                        </div>
                    </div>
                </div>

                {{-- Bottom legal bar --}}
                <div class="px-6 lg:px-12 pb-6 lg:pb-8 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-2 text-[11px] lg:text-[12px] text-ink-400 font-mono tracking-wider">
                    <span class="lg:hidden">© 2026 ÁTOMO LABS</span>
                    <span class="hidden lg:inline">© 2026 ÁTOMO LABS SPA</span>
                    <div class="flex items-center gap-3 lg:gap-4">
                        <a href="#" class="hover:text-ink-700 transition">PRIVACIDAD</a>
                        <a href="#" class="hover:text-ink-700 transition">TRATAMIENTO DE DATOS</a>
                        <a href="#" class="hidden lg:inline hover:text-ink-700 transition">CONTACTO</a>
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
