@php
    $results = session('cbir_results_with_scores', []);
    $ids = array_keys($results);
    $packages = \App\Models\Package::whereIn('id', $ids)->with(['category', 'weddingOrganizer'])->get()->map(function($pkg) use ($results) {
        $pkg->similarity_score = $results[$pkg->id] ?? 0;
        return $pkg;
    })->sortByDesc('similarity_score')->take(8);
    
    $topScore = $packages->first()?->similarity_score ?? 0;
@endphp

@if(count($packages) > 0)
<div class="mt-4 animate-in fade-in slide-in-from-bottom-4 duration-500">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-3 px-1">
        <div class="flex items-center gap-2">
            <x-filament::icon icon="heroicon-s-sparkles" class="w-4 h-4 text-amber-500" />
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Hasil Mirip</span>
        </div>
        <div class="flex items-center gap-2">
            <x-filament::badge color="success" size="sm">
                {{ count($packages) }} paket ditemukan
            </x-filament::badge>
        </div>
    </div>

    {{-- Top Match Highlight --}}
    @if($topScore >= 0.7)
    <div class="mb-3 px-3 py-2 rounded-2xl bg-linear-to-r from-amber-500/10 to-primary-500/10 border border-amber-500/20 flex items-center gap-2">
        <x-filament::icon icon="heroicon-s-fire" class="w-4 h-4 text-amber-500 shrink-0" />
        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400">
            Kecocokan terbaik: <strong>{{ round($topScore * 100) }}%</strong> akurasi visual
        </span>
    </div>
    @endif

    {{-- Package Cards --}}
    <div class="space-y-3">
        @foreach($packages as $package)
        @php
            $score = $package->similarity_score;
            $pct = round($score * 100);
            $badgeColor = $score >= 0.85 ? 'success' : ($score >= 0.65 ? 'warning' : 'gray');
            $barColor = $score >= 0.85 ? 'bg-emerald-500' : ($score >= 0.65 ? 'bg-amber-500' : 'bg-gray-400');
            $wishlistUrl = '/user/wishlists/create?package_id=' . $package->id;
            $bookUrl = '/user/my-orders/create?package_id=' . $package->id;
            $payUrl = '/user/payments/create';
        @endphp

        <div class="group relative bg-white dark:bg-gray-900 rounded-2xl shadow-md border border-gray-100 dark:border-gray-800 overflow-hidden hover:shadow-lg hover:border-primary-300 dark:hover:border-primary-700 transition-all duration-300">

            {{-- Accuracy Badge Top Right --}}
            <div class="absolute top-2 right-2 z-10 flex flex-col items-end gap-1">
                <x-filament::badge color="{{ $badgeColor }}" size="sm" class="font-bold shadow-sm text-[10px]">
                    {{ $pct }}% MIRIP
                </x-filament::badge>
                @if($package->is_featured)
                <span class="text-[9px] font-extrabold bg-red-500 text-white px-2 py-0.5 rounded-full animate-pulse">🔥 TOP</span>
                @endif
            </div>

            <div class="flex gap-3 p-3">

                {{-- Package Image --}}
                <div class="relative w-20 h-20 shrink-0 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800">
                    @if($package->image_url)
                        <img
                            src="{{ asset('storage/' . $package->image_url) }}"
                            alt="{{ $package->name }}"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            onerror="this.src='{{ asset('images/placeholder.png') }}'"
                        />
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <x-filament::icon icon="heroicon-o-photo" class="w-8 h-8 text-gray-300" />
                        </div>
                    @endif

                    {{-- Accuracy progress bar at bottom of image --}}
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200/50">
                        <div class="{{ $barColor }} h-full transition-all duration-700" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0 flex flex-col justify-between">
                    <div class="pr-14">
                        {{-- Category --}}
                        @if($package->category)
                        <span class="text-[9px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-widest">{{ $package->category->name }}</span>
                        @endif

                        {{-- Name --}}
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-tight truncate mt-0.5">
                            {{ $package->name }}
                        </h4>

                        {{-- WO Name --}}
                        @if($package->weddingOrganizer)
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5 flex items-center gap-1">
                            <x-filament::icon icon="heroicon-s-building-storefront" class="w-3 h-3 shrink-0" />
                            {{ $package->weddingOrganizer->name }}
                        </p>
                        @endif

                        {{-- Price --}}
                        <p class="text-sm font-extrabold text-primary-600 dark:text-primary-400 mt-1">
                            Rp {{ number_format($package->price, 0, ',', '.') }}
                        </p>
                    </div>

                    {{-- Action Buttons (Shopee-style) --}}
                    <div class="flex items-center gap-1.5 mt-2">

                        {{-- Wishlist / Keranjang --}}
                        <a href="{{ $wishlistUrl }}"
                            class="flex items-center justify-center w-8 h-8 rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 dark:hover:bg-red-800/30 transition-colors shrink-0"
                            title="{{ __('Simpan ke Favorit') }}"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </a>

                        {{-- Book/Order (Keranjang) --}}
                        <a href="{{ $bookUrl }}"
                            class="flex-1 flex items-center justify-center gap-1.5 h-8 rounded-xl border border-primary-400 dark:border-primary-600 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 text-[11px] font-bold hover:bg-primary-100 dark:hover:bg-primary-800/30 transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Keranjang
                        </a>

                        {{-- Sewa Langsung --}}
                        <a href="{{ $bookUrl }}"
                            class="flex-1 flex items-center justify-center gap-1.5 h-8 rounded-xl bg-linear-to-r from-amber-500 to-primary-500 text-white text-[11px] font-bold hover:from-amber-600 hover:to-primary-600 transition-all shadow-sm shadow-amber-500/20"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            Sewa
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Show All Button --}}
    <div class="mt-4">
        <button
            wire:click="$dispatch('refresh_catalog')"
            class="w-full py-3 rounded-2xl bg-linear-to-r from-amber-500 to-primary-500 text-white font-bold text-sm shadow-lg shadow-primary-500/20 hover:from-amber-600 hover:to-primary-600 transition-all active:scale-95 flex items-center justify-center gap-2"
        >
            <x-filament::icon icon="heroicon-m-squares-2x2" class="w-4 h-4" />
            Tampilkan Semua di Katalog
        </button>
    </div>

</div>
@endif
