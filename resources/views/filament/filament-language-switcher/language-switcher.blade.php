@php
    $currentLocale = app()->getLocale();
    $locals = config('filament-language-switcher.locals');
    $currentFlag = $locals[$currentLocale]['flag'] ?? ($locals[config('app.fallback_locale')]['flag'] ?? 'us');
    $isFilament = str_contains(request()->url(), config('filament.path', 'admin')) || request()->routeIs('filament.*');
@endphp

<style>
    /* Global scrollbar hide for Filament Panels */
    html, body, .fi-main, .fi-sidebar, .fi-topbar, .ffi-dropdown-panel, .ffi-dropdown-panel * {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    
    /* Chrome, Safari and Opera target */
    html::-webkit-scrollbar, 
    body::-webkit-scrollbar,
    .fi-main::-webkit-scrollbar,
    .fi-sidebar::-webkit-scrollbar,
    .fi-topbar::-webkit-scrollbar,
    .ffi-dropdown-panel::-webkit-scrollbar,
    .ffi-dropdown-panel *::-webkit-scrollbar {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        background: transparent !important;
    }
</style>

<div x-data="{
    open: false,
    toggle: function() {
        this.open = !this.open
    },
    close: function() {
        this.open = false
    },
}" class="relative">
    {{-- Trigger Button --}}
    <button type="button" id="filament-language-switcher" x-on:click="toggle" @class([
        'flex items-center justify-center gap-2 rounded-lg px-2 transition hover:bg-gray-500/5 focus:bg-gray-500/5 dark:hover:bg-white/5 dark:focus:bg-white/5',
        'h-10 min-w-10',
    ])
        x-tooltip="{
            content: '{{ __('Change Language') }}',
            theme: (typeof $store !== 'undefined' && $store.theme) ? $store.theme : 'light',
        }">
        <div class="w-6 h-6 bg-cover bg-center rounded-full shadow-sm border border-gray-200 dark:border-gray-700 shrink-0"
            style="background-image: url('https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/{{ $currentFlag }}.svg')">
        </div>
        <span @class([
            'text-xs font-bold uppercase',
            'text-gray-700 dark:text-gray-200' => $isFilament,
            'text-[#1b1b18] dark:text-[#EDEDEC]' => !$isFilament,
        ])>
            {{ $currentLocale === 'en_US' ? 'US' : ($currentLocale === 'en' ? 'UK' : strtoupper($currentLocale)) }}
        </span>
    </button>

    {{-- Dropdown Panel --}}
    <div x-ref="panel" x-show="open" x-on:click.away="close" x-transition:enter-start="opacity-0 scale-95"
        x-transition:leave-end="opacity-0 scale-95"
        @class([
            'ffi-dropdown-panel absolute right-0 top-full mt-2 min-w-[200px] divide-y rounded-lg shadow-2xl ring-1 transition',
            $isFilament
                ? 'bg-white divide-gray-100 ring-gray-950/10 dark:divide-white/5 dark:bg-gray-900 dark:ring-white/20'
                : 'bg-[#FDFDFC] divide-[#19140015] ring-[#19140015] dark:bg-[#0a0a0a] dark:divide-[#ffffff10] dark:ring-[#ffffff10]',
        ]) :style="(typeof $store !== 'undefined' && $store.theme === 'dark') || document.documentElement.classList.contains('dark') 
            ? 'z-index: 2000; max-height: 250px !important; overflow-y: auto !important; background-color: #030712 !important;' 
            : 'z-index: 2000; max-height: 250px !important; overflow-y: auto !important; background-color: white !important;'" x-cloak>
        <div class="filament-dropdown-list p-1 w-full scrollbar-thin">
            @foreach ($locals as $key => $language)
                @php $isCurrent = $currentLocale === $key; @endphp
                <a @if (!$isCurrent) href="{{ route('language.switch', ['locale' => $key]) }}"
                    @else
                        href="javascript:void(0)" @endif
                    @class([
                        'filament-dropdown-list-item filament-dropdown-item group flex items-center w-full justify-between gap-3 whitespace-nowrap rounded-md p-2 text-sm outline-none',
                        // Hover & Colors for non-current
                        ($isFilament
                            ? 'text-gray-500 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5 focus:bg-gray-50 dark:focus:bg-white/5'
                            : 'text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#19140005] dark:hover:bg-[#ffffff05] focus:bg-[#19140005] dark:focus:bg-[#ffffff05]') => !$isCurrent,
                        // Colors for current
                        ($isFilament
                            ? 'bg-gray-50 dark:bg-white/5 text-primary-600 dark:text-primary-400 font-semibold cursor-default'
                            : 'bg-[#19140005] dark:bg-[#ffffff05] text-[#1b1b18] dark:text-[#EDEDEC] font-semibold cursor-default') => $isCurrent,
                    ])>
                    {{-- Label --}}
                    <span class="truncate flex-1 text-start">
                        {{ str_replace('.', '', __($language['label'])) }}
                    </span>

                    {{-- Flag --}}
                    <div class="w-6 h-6 shrink-0 bg-cover bg-center rounded-full border border-gray-200 dark:border-gray-700 shadow-sm"
                        style="background-image: url('https://cdn.jsdelivr.net/gh/hampusborgos/country-flags@main/svg/{{ $language['flag'] }}.svg'); background-repeat: no-repeat">
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>