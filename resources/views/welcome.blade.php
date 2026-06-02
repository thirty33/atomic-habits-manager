<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Atomic Habits Manager') }} — Hábitos que se vuelven tú</title>

    {{-- Bunny Fonts: Inter (body), Instrument Serif (display), JetBrains Mono (eyebrows/metadata) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|instrument-serif:400,400i|jetbrains-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body antialiased bg-paper text-ink-900">

    @include('partials.public-header')

    {{-- ─────────────────────────────────────────────────────────────────
         HERO — text + product placeholder card
         ───────────────────────────────────────────────────────────────── --}}
    <section class="px-6 lg:px-12 pt-12 lg:pt-20 pb-12 lg:pb-16 grid grid-cols-1 lg:grid-cols-12 gap-10">
        <div class="lg:col-span-7 lg:pt-4">
            <span class="eyebrow"><span class="dot"></span>Atomic Habits manager</span>
            <h1 class="display text-[58px] lg:text-[112px] mt-5 lg:mt-7 text-ink-900">
                Hábitos<br>que se vuelven <em class="font-display italic text-brand-700">tú</em>.
            </h1>
            <p class="mt-5 lg:mt-7 max-w-[520px] text-[15px] lg:text-[17px] text-ink-700 leading-[1.55]">
                Diseñá rutinas atómicas, registralas en segundos, y dejá que
                <span class="text-ink-900 font-medium">Atomic IA</span> te ayude a ajustarlas cuando la vida cambia.
                Sin gamificación. Sin notificaciones invasivas. Solo constancia.
            </p>
            <div class="mt-7 lg:mt-9 flex flex-col lg:flex-row lg:items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-lg bg-brand-700 text-paper font-medium text-[15px] hover:bg-brand-800 transition">
                        Ir al dashboard
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-lg bg-brand-700 text-paper font-medium text-[15px] hover:bg-brand-800 transition">
                        Crear mi cuenta
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center px-5 py-3.5 rounded-lg text-ink-900 text-[15px] border border-line-300 hover:bg-line-100 transition">
                        Ya tengo cuenta
                    </a>
                    <div class="hidden lg:flex items-center gap-2 ml-3 text-[12.5px] text-ink-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-success-2"></span>
                        Gratis hasta 5 hábitos
                    </div>
                @endauth
            </div>
        </div>

        {{-- Product placeholder card (static visual preview, not real data) --}}
        <div class="lg:col-span-5 relative">
            <div class="absolute -top-3 -left-3 right-0 bottom-0 rounded-2xl bg-brand-100"></div>
            <div class="relative rounded-2xl border border-line-200 bg-card overflow-hidden shadow-[0_24px_60px_-24px_rgba(20,40,32,0.18)]">
                <div class="flex items-center gap-1.5 px-4 py-2.5 border-b border-line-100 bg-page-cream">
                    <span class="w-2.5 h-2.5 rounded-full bg-line-300"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-line-300"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-line-300"></span>
                    <span class="ml-3 text-[11px] font-mono text-ink-400">app.atomohabits.com / hoy</span>
                </div>
                <div class="p-6">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <div class="text-[11px] uppercase tracking-[0.1em] font-mono text-ink-400">Martes 12 mayo</div>
                            <div class="font-display text-[28px] mt-1">Hoy</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[11px] uppercase tracking-[0.1em] font-mono text-ink-400">Adherencia</div>
                            <div class="font-display text-[28px] mt-1 text-brand-700">73<span class="text-[16px] text-ink-500">%</span></div>
                        </div>
                    </div>
                    <div class="mt-5 h-1 rounded-full bg-line-100 overflow-hidden">
                        <div class="h-full bg-brand-700" style="width:73%"></div>
                    </div>

                    @php
                        $previewRows = [
                            ['h' => '06:30', 't' => 'Meditar',                     's' => 'done',    'd' => '20 min · sala'],
                            ['h' => '08:00', 't' => 'Arquitectura de Software',    's' => 'done',    'd' => '3h 30m · estudio'],
                            ['h' => '12:30', 't' => 'Inglés',                      's' => 'partial', 'd' => '45 min · shadowing'],
                            ['h' => '15:00', 't' => 'Bloque de trabajo profundo',  's' => 'now',     'd' => '4h · sin notif.'],
                            ['h' => '19:30', 't' => 'Gimnasio',                    's' => 'pending', 'd' => '1h 30m · pull'],
                        ];
                        $dotClass = [
                            'done'    => 'bg-success-2',
                            'partial' => 'bg-warning',
                            'now'     => 'bg-brand-700 ring-4 ring-brand-100',
                            'pending' => 'bg-line-300',
                        ];
                        $statusLabel = [
                            'done'    => ['label' => 'Hecho',     'color' => 'text-success-2'],
                            'partial' => ['label' => 'Parcial',   'color' => 'text-warning'],
                            'now'     => ['label' => 'En curso',  'color' => 'text-brand-700'],
                            'pending' => ['label' => 'Pendiente', 'color' => 'text-ink-400'],
                        ];
                    @endphp
                    <ul class="mt-6 space-y-2.5">
                        @foreach ($previewRows as $r)
                            <li class="flex items-center gap-4 px-4 py-3 rounded-lg bg-paper border border-line-100">
                                <span class="font-mono text-[12.5px] text-ink-500 w-12">{{ $r['h'] }}</span>
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $dotClass[$r['s']] }}"></span>
                                <span class="flex-1 min-w-0">
                                    <div class="text-[14px] text-ink-900 truncate">{{ $r['t'] }}</div>
                                    <div class="text-[12px] text-ink-500 truncate">{{ $r['d'] }}</div>
                                </span>
                                <span class="text-[11px] font-mono uppercase tracking-wider {{ $statusLabel[$r['s']]['color'] }}">{{ $statusLabel[$r['s']]['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            {{-- Floating Atomic IA sticker --}}
            <div class="absolute -right-2 lg:-right-4 -bottom-4 px-4 py-3 rounded-xl bg-ink-900 text-paper shadow-lg max-w-[220px]">
                <div class="font-mono text-[10px] uppercase tracking-widest opacity-60 mb-1">Atomic IA</div>
                <div class="text-[13px] leading-snug">«Veo que dormís menos los miércoles. ¿Querés que mueva el gimnasio al jueves?»</div>
            </div>
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────────────────
         DIVIDER
         ───────────────────────────────────────────────────────────────── --}}
    <section class="px-6 lg:px-12">
        <div class="divider-dot"></div>
    </section>

    {{-- ─────────────────────────────────────────────────────────────────
         FEATURES — 5 cards (no placeholder images on habits/calendar)
         ───────────────────────────────────────────────────────────────── --}}
    <section id="features" class="px-6 lg:px-12 pt-16 lg:pt-20 pb-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-10 lg:items-end mb-10 lg:mb-12">
            <h2 class="display text-[40px] lg:text-[64px] lg:col-span-7">
                Cuatro herramientas. <em class="font-display italic text-brand-700">Un solo</em> sistema.
            </h2>
            <p class="lg:col-span-4 lg:col-start-9 text-[15px] text-ink-500 leading-[1.6]">
                Cada módulo está pensado para hacer el trabajo invisible del cambio: que registres sin pensar, que mires sin ansiedad, que ajustes sin culpa.
            </p>
        </div>

        @php
            $features = [
                ['n' => '01', 't' => 'Hábitos',           'tag' => '/habits',        'd' => 'Definí naturaleza, importancia, intención de implementación y motivación positiva. Programación por días, rango horario o frecuencia. Edición sin perder histórico.', 'span' => 6],
                ['n' => '02', 't' => 'Calendario',        'tag' => '/calendar',      'd' => 'Vista mes, semana y día con FullCalendar. Bloques coloreados según adherencia. Atajos para reprogramar arrastrando.', 'span' => 6],
                ['n' => '03', 't' => 'Reporte diario',    'tag' => '/daily-reports', 'd' => 'Cinco estados por bloque: completado, parcial, no cumplido, omitido, pendiente. Reflexión libre + estado de ánimo.', 'span' => 4],
                ['n' => '04', 't' => 'Atomic IA',         'tag' => '/atomic-ia',     'd' => 'Asistente entrenado en el método. Consulta hábitos, sugiere ajustes, conserva contexto entre sesiones.', 'span' => 4],
                ['n' => '05', 't' => 'Sin notificaciones','tag' => 'Principio',      'd' => 'Apertura activa. No te perseguimos por mail ni por push. Vos abrís la app y la app responde.', 'span' => 4],
            ];
            $spanClass = [4 => 'lg:col-span-4', 6 => 'lg:col-span-6'];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            @foreach ($features as $f)
                <article class="{{ $spanClass[$f['span']] }} rounded-xl border border-line-200 bg-card p-7 flex flex-col min-h-[220px]">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[11px] tracking-[0.12em] text-ink-400">{{ $f['n'] }}</span>
                        <span class="font-mono text-[10.5px] tracking-[0.08em] uppercase text-ink-400 bg-line-100 px-2 py-1 rounded">{{ $f['tag'] }}</span>
                    </div>
                    <h3 class="font-display text-[28px] lg:text-[34px] mt-6 text-ink-900">{{ $f['t'] }}</h3>
                    <p class="mt-3 text-sm text-ink-500 leading-[1.6] max-w-[420px]">{{ $f['d'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────────────────
         METHOD STRIP — dark green section with the 4 laws
         ───────────────────────────────────────────────────────────────── --}}
    <section id="method" class="mx-6 lg:mx-12 px-7 lg:px-12 py-12 lg:py-20 mt-8 bg-brand-800 text-paper rounded-2xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
            <div class="lg:col-span-5">
                <div class="font-mono text-[11px] tracking-[0.12em] opacity-60 uppercase">— El método</div>
                <h2 class="display text-[40px] lg:text-[56px] mt-4 lg:mt-5 text-paper">
                    Cuatro leyes. <em class="font-display italic opacity-90">Una rutina.</em>
                </h2>
                <p class="mt-5 lg:mt-6 text-[14px] lg:text-[15px] opacity-70 leading-[1.6] max-w-[380px]">
                    Adaptamos el modelo de las cuatro leyes del cambio de comportamiento a una interfaz operativa. Cada hábito que creás se etiqueta y se ordena por estas dimensiones, sin que tengas que pensarlo.
                </p>
            </div>
            <div class="lg:col-span-7 grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6 lg:self-end">
                @php
                    $laws = [
                        ['Hacerlo obvio',         'Señal explícita + ubicación física.'],
                        ['Hacerlo atractivo',     'Motivación positiva escrita en primera persona.'],
                        ['Hacerlo fácil',         'Intención de implementación de máximo dos líneas.'],
                        ['Hacerlo satisfactorio', 'Registro inmediato + adherencia visible.'],
                    ];
                @endphp
                @foreach ($laws as $i => $law)
                    <div class="border-t border-paper/10 pt-4 lg:pt-5">
                        <div class="font-mono text-[10.5px] tracking-[0.12em] opacity-50 uppercase">Ley {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="font-display text-[22px] lg:text-[26px] mt-2">{{ $law[0] }}</div>
                        <p class="mt-2 text-[13px] opacity-65 leading-relaxed">{{ $law[1] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─────────────────────────────────────────────────────────────────
         FINAL CTA
         ───────────────────────────────────────────────────────────────── --}}
    <section class="px-6 lg:px-12 py-16 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 lg:items-center">
            <div class="lg:col-span-7">
                <h2 class="display text-[44px] lg:text-[72px]">
                    Empezá con <em class="font-display italic text-brand-700">un</em> hábito.
                </h2>
                <p class="mt-5 text-base text-ink-500 max-w-[460px]">
                    Cinco minutos para crear el primero. Cero tarjeta. Cancelás cuando quieras — o nunca, porque no hay nada que cancelar mientras uses el plan libre.
                </p>
            </div>
            <div class="lg:col-span-5 lg:flex lg:justify-end">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 px-6 py-4 rounded-lg bg-brand-700 text-paper font-medium text-[15px] hover:bg-brand-800 transition">
                        Ir al dashboard
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 px-6 py-4 rounded-lg bg-brand-700 text-paper font-medium text-[15px] hover:bg-brand-800 transition">
                        Crear mi cuenta
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                @endauth
            </div>
        </div>
    </section>

    @include('partials.public-footer')

</body>
</html>
