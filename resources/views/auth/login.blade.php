<x-guest-layout eyebrow="Iniciar sesión">
    <x-slot:title>
        Bienvenido<br><em class="font-display italic text-brand-700">de vuelta.</em>
    </x-slot:title>
    <x-slot:sub>
        Continuemos exactamente donde dejaste. Tus hábitos, calendario y reportes diarios siguen sincronizados.
    </x-slot:sub>
    <x-slot:topRight>
        ¿Primera vez? <a href="{{ route('register') }}" class="text-brand-700 uline font-medium">Crear cuenta</a>
    </x-slot:topRight>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="email" :value="__('Email')" />
                <span class="text-[11.5px] text-ink-400">requerido</span>
            </div>
            <x-text-input id="email" class="mt-1.5"
                          type="email" name="email"
                          :value="old('email')"
                          required autofocus autocomplete="username"
                          placeholder="vos@dominio.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-[12px] text-brand-700 uline">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1.5"
                          type="password" name="password"
                          required autocomplete="current-password"
                          placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Remember me --}}
        <label for="remember_me" class="inline-flex items-center gap-2.5 select-none cursor-pointer">
            <input id="remember_me" type="checkbox" name="remember"
                   class="w-4 h-4 rounded border-line-300 text-brand-700 focus:ring-brand-700">
            <span class="text-[13.5px] text-ink-700">{{ __('Mantener la sesión iniciada') }}</span>
        </label>

        <x-primary-button class="w-full justify-center !py-3.5 !text-[15px]">
            {{ __('Entrar a mi cuenta') }}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14m-6-6 6 6-6 6"/>
            </svg>
        </x-primary-button>

        <p class="pt-3 text-[13px] text-ink-500">
            ¿No tenés cuenta?
            <a href="{{ route('register') }}" class="text-brand-700 uline font-medium">Crear una ahora</a>
        </p>
    </form>
</x-guest-layout>
