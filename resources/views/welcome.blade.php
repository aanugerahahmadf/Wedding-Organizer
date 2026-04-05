<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>{{ config('app.name') }} - {{ __('Wedding Organizer') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/anchor@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/tooltip@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <script>
        (function() {
            try {
                const theme = localStorage.getItem('theme') || localStorage.getItem('filament_theme') || 'system';
                if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                console.error('Theme sync failed:', e);
            }
        })();
    </script>
</head>

<body
    class="nativephp-safe-area bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col transition-colors duration-500">
    @livewireScripts
    <header class="relative z-50 w-full lg:max-w-4xl max-w-[335px] text-sm mb-6">
        <nav class="flex items-center justify-end gap-3 lg:gap-6">
            @auth
                <a href="{{ route('filament.user.resources.home.index', ['record' => 1]) }}"
                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal transition-all active:scale-95 whitespace-nowrap">
                    {{ __('Beranda') }}
                </a>
            @else
                <a href="{{ route('filament.user.auth.login') }}"
                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal transition-all active:scale-95 whitespace-nowrap">
                    {{ __('Log in') }}
                </a>
                <a href="{{ route('filament.user.auth.register') }}"
                    class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal transition-all active:scale-95 whitespace-nowrap">
                    {{ __('Register') }}
                </a>
            @endauth

            {{-- Theme Switcher Component --}}
            <div x-data="{
                theme: localStorage.getItem('theme') || 'system',
                updateTheme(val) {
                    this.theme = val;
                    localStorage.setItem('theme', val);
                    this.apply();
                },
                apply() {
                    if (this.theme === 'dark' || (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }" x-init="apply()" class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-inner">
                <button @click="updateTheme('light')" :class="theme === 'light' ? 'bg-white dark:bg-gray-700 shadow-sm text-yellow-500' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'" class="p-1 px-2 rounded-md flex items-center gap-1 text-[10px] uppercase font-bold transition-all">
                    <x-heroicon-m-sun class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('Light') }}</span>
                </button>
                <button @click="updateTheme('dark')" :class="theme === 'dark' ? 'bg-white dark:bg-gray-700 shadow-sm text-indigo-400' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'" class="p-1 px-2 rounded-md flex items-center gap-1 text-[10px] uppercase font-bold transition-all">
                    <x-heroicon-m-moon class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('Night') }}</span>
                </button>
                <button @click="updateTheme('system')" :class="theme === 'system' ? 'bg-white dark:bg-gray-700 shadow-sm text-gray-600 dark:text-gray-300' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'" class="p-1 px-2 rounded-md flex items-center gap-1 text-[10px] uppercase font-bold transition-all">
                    <x-heroicon-m-computer-desktop class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ __('System') }}</span>
                </button>
            </div>

            {{-- Language Switcher Sync --}}
            @include('filament.filament-language-switcher.language-switcher')
        </nav>
    </header>

    <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow">
        <main
            class="flex max-w-[335px] w-full flex-col-reverse lg:max-w-4xl lg:flex-row shadow-sm rounded-lg overflow-hidden border border-[#19140015] dark:border-[#ffffff10]">
            <div
                class="text-[13px] leading-[20px] flex-1 p-6 pb-12 lg:p-20 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                <h1 class="mb-1 font-semibold text-lg text-gray-950 dark:text-white">{{ __('Welcome To Devi Panel Make Up') }}</h1>
                <p class="mb-2 text-gray-600 dark:text-gray-400">
                    {{ __('Manage your wedding organizer needs efficiently with our comprehensive system.') }}
                </p>

                <ul class="flex flex-col mb-4 lg:mb-6 gap-2">
                    <li
                        class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:top-1/2 before:bottom-0 before:left-[0.4rem] before:absolute">
                        <span class="relative py-1 bg-white dark:bg-[#161615]">
                            <span
                                class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                <span class="rounded-full bg-[#E91E63] w-1.5 h-1.5"></span>
                            </span>
                        </span>
                        <span>{{ __('Explore Packages & Portfolio') }}</span>
                    </li>
                    <li
                        class="flex items-center gap-4 py-2 relative before:border-l before:border-[#e3e3e0] dark:before:border-[#3E3E3A] before:bottom-1/2 before:top-0 before:left-[0.4rem] before:absolute">
                        <span class="relative py-1 bg-white dark:bg-[#161615]">
                            <span
                                class="flex items-center justify-center rounded-full bg-[#FDFDFC] dark:bg-[#161615] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] w-3.5 h-3.5 border dark:border-[#3E3E3A] border-[#e3e3e0]">
                                <span class="rounded-full bg-[#E91E63] w-1.5 h-1.5"></span>
                            </span>
                        </span>
                        <span>{{ __('Track Orders & Booking Details') }}</span>
                    </li>
                </ul>

                <ul class="flex w-full mt-4 lg:mt-6">
                    <li class="w-full lg:w-auto">
                        <a href="{{ route('filament.user.resources.home.index', ['record' => 1]) }}"
                            class="inline-block dark:bg-white dark:text-gray-950 dark:hover:bg-gray-200 hover:bg-black hover:border-black px-5 py-1.5 bg-gray-900 rounded-sm border border-black text-white text-sm font-semibold leading-normal transition-all active:scale-95 shadow-sm">
                            {{ __('Buka Beranda') }}
                        </a>
                    </li>
                </ul>
            </div>

            <div
                class="bg-[#fff2f2] dark:bg-[#1D0002] relative lg:-ml-px -mb-px lg:mb-0 rounded-t-lg lg:rounded-t-none lg:rounded-r-lg aspect-[335/376] lg:aspect-auto w-full lg:w-[438px] shrink-0 overflow-hidden flex items-center justify-center border-b lg:border-b-0 lg:border-l border-[#19140015] dark:border-[#ffffff10]">
                <div class="z-10 text-center p-8">
                    <h2 class="text-3xl lg:text-4xl font-bold text-[#E91E63] dark:text-[#FF80AB] mb-2">
                        {{ __('Devi Make Up') }}</h2>
                    <p class="text-xs uppercase tracking-[0.3em] text-[#D81B60] dark:text-[#F48FB1] font-medium">
                        {{ __('Wedding Organizer') }}</p>
                </div>
            </div>
        </main>
    </div>

    <footer class="mt-8 text-[#706f6c] dark:text-[#A1A09A] text-[11px] uppercase tracking-widest">
        &copy; {{ date('Y') }} Devi Make up {{ __('All rights reserved') }}
    </footer>
</body>

</html>