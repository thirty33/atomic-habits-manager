<x-guest-layout eyebrow="Crear cuenta">
    <x-slot:title>
        El primer<br><em class="font-display italic text-brand-700">hábito</em>.
    </x-slot:title>
    <x-slot:sub>
        Crear la cuenta es el primer hábito que registramos por vos. Tres campos y dos minutos.
    </x-slot:sub>
    <x-slot:topRight>
        ¿Ya tenés cuenta? <a href="{{ route('login') }}" class="text-brand-700 uline font-medium">Entrar</a>
    </x-slot:topRight>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        {{-- Name --}}
        <div>
            <x-input-label for="name" :value="__('Nombre completo')" />
            <x-text-input id="name" class="mt-1.5"
                          type="text" name="name"
                          :value="old('name')"
                          required autofocus autocomplete="name"
                          placeholder="Cómo querés que te llamemos" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5"
                          type="email" name="email"
                          :value="old('email')"
                          required autocomplete="username"
                          placeholder="vos@dominio.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password + Confirm side-by-side on desktop --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <div>
                <x-input-label for="password" :value="__('Contraseña')" />
                <x-text-input id="password" class="mt-1.5"
                              type="password" name="password"
                              required autocomplete="new-password"
                              placeholder="Mínimo 8 caracteres" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" :value="__('Repetir contraseña')" />
                <x-text-input id="password_confirmation" class="mt-1.5"
                              type="password" name="password_confirmation"
                              required autocomplete="new-password"
                              placeholder="Confirmar" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <x-primary-button class="w-full justify-center !py-3.5 !text-[15px] mt-3">
            {{ __('Crear mi cuenta') }}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14m-6-6 6 6-6 6"/>
            </svg>
        </x-primary-button>

        <p class="pt-2 text-[13px] text-ink-500">
            ¿Ya tenés cuenta?
            <a href="{{ route('login') }}" class="text-brand-700 uline font-medium">Iniciar sesión</a>
        </p>
    </form>
</x-guest-layout>
