
@php
    $isTerms = $active === 'terms';
@endphp

<style>
    /* Prevent horizontal overflow globally to fix the Windows scrollbar bug */
    body, html { overflow-x: hidden !important; position: relative; width: 100%; }
    main, .fi-main, .fi-main-ctn, .fi-content { overflow-x: hidden !important; }
    
    /* Modern scrollbar styling for vertical content */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { 
        background: #e2e8f0; 
        border-radius: 10px; 
    }
    .dark ::-webkit-scrollbar-thumb { background: #1e293b; }
</style>


{{-- ── SHOPEE-STYLE TOP NAVBAR ── --}}
<div class="-mx-4 sm:-mx-6 lg:-mx-8 -mt-12">
    <div @class([
        'sticky top-0 z-50 bg-white dark:bg-gray-950 transition-all duration-500 px-6 lg:px-12 pt-6 lg:pt-10',
    ])>
        <div class="max-w-7xl mx-auto flex items-center justify-between h-20">

            {{-- Left: Logo --}}
            <a href="{{ route('filament.user.auth.login') }}" class="flex items-center gap-4 group">
                <img src="{{ asset('images/logo.png') }}" alt="Devi Make Up"
                     class="h-10 w-auto object-contain transition-transform group-hover:scale-105"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                <span @class([
                    'hidden text-xl font-black tracking-tighter uppercase shrink-0',
                    'text-primary-600 dark:text-primary-400',
                ]) style="display:none">Devi Make Up</span>
                <span class="hidden md:block text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-[.2em] antialiased">
                    {{ __('Professional Center') }}
                </span>
            </a>

            {{-- Right: Nav links, Theme Switcher & Language Switcher --}}
            <div class="flex items-center gap-4">
                <nav class="flex items-center gap-6">
                    <a href="/terms"
                       @class([
                           'relative py-1 text-[12px] font-bold uppercase tracking-widest transition-all duration-300',
                           'text-primary-600 dark:text-primary-400' => $isTerms,
                           'text-slate-600 dark:text-slate-400 hover:text-gray-950 dark:hover:text-white' => !$isTerms,
                       ])>
                        {{ __('Terms') }}
                        @if($isTerms)
                            <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-primary-600 dark:bg-primary-400 rounded-full animate-in fade-in slide-in-from-left-1"></span>
                        @endif
                    </a>
                    <a href="/privacy"
                       @class([
                           'relative py-1 text-[12px] font-bold uppercase tracking-widest transition-all duration-300',
                           'text-primary-600 dark:text-primary-400' => !$isTerms,
                           'text-slate-600 dark:text-slate-400 hover:text-gray-950 dark:hover:text-white' => $isTerms,
                       ])>
                        {{ __('Privacy') }}
                        @if(!$isTerms)
                            <span class="absolute -bottom-1 left-0 w-full h-0.5 bg-primary-600 dark:bg-primary-400 rounded-full animate-in fade-in slide-in-from-right-1"></span>
                        @endif
                    </a>
                </nav>

                {{-- Language Switcher Beside Nav --}}
                @include('filament.filament-language-switcher.language-switcher')
            </div>
        </div>
    </div>
</div>
