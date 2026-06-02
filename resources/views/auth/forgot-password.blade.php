<x-guest-layout eyebrow="Recuperar contraseña">
    <x-slot:title>
        Recuperá tu<br><em class="font-display italic text-brand-700">acceso</em>.
    </x-slot:title>
    <x-slot:sub>
        Pasa. Te enviaremos un enlace temporal para que elijas una nueva contraseña, sin perder ningún hábito ni reporte.
    </x-slot:sub>
    <x-slot:topRight>
        ¿No tenés cuenta? <a href="{{ route('register') }}" class="text-brand-700 uline font-medium">Crear una</a>
    </x-slot:topRight>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        {{-- Info card --}}
        <div class="rounded-lg bg-brand-50 border border-brand-100 p-4 flex gap-3">
            <span class="inline-grid place-items-center w-[22px] h-[22px] rounded-md bg-brand-700 text-paper font-display text-[13px] leading-none pb-[1px] shrink-0 mt-0.5">i</span>
            <p class="text-[13px] text-brand-800 leading-relaxed">
                Te enviaremos un enlace con vigencia de <span class="font-medium">60 minutos</span> al correo asociado a tu cuenta. Si no aparece, revisá la carpeta de promociones.
            </p>
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email asociado a tu cuenta')" />
            <x-text-input id="email" class="mt-1.5"
                          type="email" name="email"
                          :value="old('email')"
                          required autofocus
                          placeholder="vos@dominio.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center !py-3.5 !text-[15px]">
            {{ __('Enviar enlace de recuperación') }}
        </x-primary-button>

        <p class="text-[13px] text-ink-500 pt-2 text-center">
            <a href="{{ route('login') }}" class="text-brand-700 uline">← Volver a iniciar sesión</a>
        </p>
    </form>
</x-guest-layout>
