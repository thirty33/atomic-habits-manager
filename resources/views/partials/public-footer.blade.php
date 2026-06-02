{{--
    Shared public footer for marketing/legal pages.
    Four columns on desktop (Producto, Empresa, Legal, Redes) collapsing to 2 on mobile.
    No SLA indicator — out of scope for this redesign pass.
--}}
@php
    $footerCols = [
        ['title' => 'Producto', 'links' => [
            ['label' => 'Características', 'href' => '#features'],
            ['label' => 'Método atómico',  'href' => '#method'],
            ['label' => 'Atomic IA',       'href' => '#'],
            ['label' => 'Calendario',      'href' => '#'],
        ]],
        ['title' => 'Empresa', 'links' => [
            ['label' => 'Acerca',    'href' => '#'],
            ['label' => 'Equipo',    'href' => '#'],
            ['label' => 'Cambios',   'href' => '#'],
            ['label' => 'Contacto',  'href' => '#'],
        ]],
        ['title' => 'Legal', 'links' => [
            ['label' => 'Política de privacidad', 'href' => '#'],
            ['label' => 'Tratamiento de datos',   'href' => '#'],
            ['label' => 'Cookies',                'href' => '#'],
            ['label' => 'Términos',               'href' => '#'],
        ]],
        ['title' => 'Redes', 'links' => [
            ['label' => 'X / Twitter', 'href' => '#'],
            ['label' => 'LinkedIn',    'href' => '#'],
            ['label' => 'GitHub',      'href' => '#'],
        ]],
    ];
@endphp

<footer class="px-6 lg:px-12 pt-12 lg:pt-16 pb-8 lg:pb-10 mt-12 lg:mt-20 border-t border-line-200">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">
        <div class="lg:col-span-4">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 select-none">
                <span class="logo-mark">A</span>
                <span class="font-display text-[19px] leading-none text-ink-900">Átomo<span class="text-brand-700">.</span></span>
            </a>
            <p class="mt-4 text-[13.5px] text-ink-500 leading-relaxed max-w-[300px]">
                Gestor de hábitos atómicos con asistente Atomic IA. Construido para profesionales que cuidan su tiempo y su atención.
            </p>
        </div>

        <div class="lg:col-span-8 grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-8">
            @foreach ($footerCols as $col)
                <div>
                    <div class="text-[11px] uppercase tracking-[0.08em] text-ink-400 font-mono mb-4">{{ $col['title'] }}</div>
                    <ul class="space-y-2.5">
                        @foreach ($col['links'] as $link)
                            <li>
                                <a href="{{ $link['href'] }}" class="text-[13.5px] text-ink-700 hover:text-brand-700 transition">{{ $link['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-12 lg:mt-14 pt-6 border-t border-line-200 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3 text-[12px] text-ink-400">
        <span>© {{ date('Y') }} Átomo Labs · Todos los derechos reservados</span>
        <div class="flex items-center gap-5 font-mono tracking-wider">
            <span>ESPAÑOL (CL)</span>
            <span>SANTIAGO, CL</span>
        </div>
    </div>
</footer>
