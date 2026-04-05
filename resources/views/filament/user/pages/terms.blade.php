<div class="overflow-x-hidden">
    {{-- Theme Sync Script --}}
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
    @php $sections = $pageRecord?->content ?? []; @endphp

    @include('filament.user.partials.legal-header', ['active' => 'terms'])

    <div x-data="{ active: '' }"
        class="flex gap-0 min-h-screen bg-white dark:bg-gray-950 transition-colors duration-500">

        {{-- ── STICKY SIDEBAR ── --}}
        <aside
            class="hidden xl:flex flex-col w-64 shrink-0 sticky top-0 self-start h-screen border-none overflow-y-auto px-4 py-8 bg-white dark:bg-gray-950">
            <p class="text-[9px] font-black uppercase tracking-[.4em] text-gray-300 dark:text-gray-700 mb-4 px-2">
                {{ __('Daftar Isi') }}
            </p>
            <nav class="space-y-0.5">
                @foreach ($sections as $i => $section)
                    <button
                        @click="document.getElementById('s{{ $i }}').scrollIntoView({ behavior: 'smooth' }); active = 's{{ $i }}'"
                        :class="active === 's{{ $i }}' ? 'text-primary-600 dark:text-primary-400 font-bold bg-primary-50 dark:bg-primary-500/10' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-white/5'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-xs transition-all duration-200">
                        <span class="font-mono text-[10px] w-5 shrink-0 opacity-40">{{ $i + 1 }}.</span>
                        <span class="uppercase tracking-wide truncate">{{ __($section['heading']) }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="mt-auto pt-6 border-none px-2">
                <x-filament::link icon="heroicon-m-arrow-left" href="{{ route('filament.user.auth.login') }}"
                    color="gray" size="xs">
                    {{ __('Kembali ke Login') }}
                </x-filament::link>
            </div>
        </aside>

        {{-- ── CONTENT ── --}}
        <div class="flex-1 min-w-0 bg-white dark:bg-gray-950">
            <div class="flex-1 max-w-7xl mx-auto px-6 lg:px-16 py-12 lg:py-20">

                {{-- Clean Header using Filament CSS --}}
                <header class="space-y-0 pb-2 border-none">
                    <h1
                        class="fi-header-heading text-xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-2xl text-left m-0">
                        {{ __($pageRecord?->title ?? 'Terms & Conditions') }}
                    </h1>

                    <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400 text-justify m-0">
                        {{ __('Baca seluruh dokumen ini sebelum menggunakan platform. Dengan melanjutkan, Anda menyetujui semua ketentuan di bawah.') }}
                    </p>
                </header>

                {{-- Articles with spacing between sections --}}
                <div class="flex flex-col space-y-2">
                    @foreach ($sections as $i => $item)
                        <article id="s{{ $i }}" x-intersect.margin="-20% 0px -60% 0px"="active = 's{{ $i }}'"
                            class="group scroll-mt-32 py-2">
                            <div class="space-y-0">
                                {{-- Heading with Numbering --}}
                                <h2
                                    class="fi-header-heading text-base font-bold text-gray-900 dark:text-gray-100 uppercase tracking-tight leading-tight flex items-center gap-3">
                                    <span>{{ $i + 1 }}.</span>
                                    {{ __($item['heading']) }}
                                </h2>

                                {{-- Body Text with soft slate color matching the image --}}
                                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400 text-justify">
                                    {!! nl2br(e(__($item['body']))) !!}
                                </p>
                            </div>
                        </article>
                    @endforeach

                    {{-- Huge Gap to separate Content from Action --}}
                    <div class="h-40"></div>

                    {{-- Responsive Action Button --}}
                    <div class="flex justify-center pb-60">
                        <x-filament::button tag="a" href="/privacy" icon="heroicon-m-arrow-right" icon-position="after"
                            size="xl" class="shadow-xl scale-110 rounded-xl">
                            {{ __('Lanjutkan ke Kebijakan Privasi') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>