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
    <div class="flex items-center justify-between mb-4 px-1">
        <div class="flex items-center gap-2">
            <x-filament::icon icon="heroicon-s-sparkles" class="w-5 h-5 text-amber-500 shadow-sm" />
            <span class="text-sm font-black bg-clip-text text-transparent bg-linear-to-r from-amber-500 to-primary-600 uppercase tracking-tighter">{{ __('Hasil Mirip') }}</span>
        </div>
        <x-filament::badge color="success" size="sm" icon="heroicon-m-check-badge">
            {{ count($packages) }} {{ __('layanan') }}
        </x-filament::badge>
    </div>

    {{-- Top Match Highlight --}}
    @if($topScore >= 0.7)
    <div class="mb-4">
        <x-filament::section compact>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-500/10 rounded-xl">
                    <x-filament::icon icon="heroicon-s-fire" class="w-5 h-5 text-amber-500" />
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-widest">{{ __('Rekomendasi Terbaik') }}</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ \App\Filament\User\Resources\PackageResource::formatSimilarityPct($topScore) }}% {{ __('akurasi visual terdeteksi') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
    @endif

    {{-- Package Cards --}}
    <div class="space-y-4">
        @foreach($packages as $package)
        @php
            $score = $package->similarity_score;
            $pct = \App\Filament\User\Resources\PackageResource::formatSimilarityPct($score);
            $badgeColor = $score >= 0.85 ? 'success' : ($score >= 0.65 ? 'warning' : 'gray');
            $wishlistUrl = route('filament.user.resources.wishlists.index', ['package_id' => $package->id]);
            $bookUrl = route('filament.user.resources.catalog.index', ['package_id' => $package->id]);
        @endphp

        <x-filament::section compact class="relative overflow-hidden group hover:ring-2 hover:ring-primary-500/50 transition-all duration-300">
            {{-- Accuracy Badge --}}
            <div class="absolute top-0 right-0 p-3">
                <x-filament::badge color="{{ $badgeColor }}" size="sm" class="font-bold">
                    {{ $pct }}%
                </x-filament::badge>
            </div>

            <div class="flex gap-4">
                {{-- Package Image --}}
                <div class="relative w-24 h-24 shrink-0 rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800 shadow-inner">
                    <img
                        src="{{ asset('storage/' . $package->image_url) }}"
                        alt="{{ $package->name }}"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                        onerror="this.src='{{ asset('images/placeholders/image-placeholder.svg') }}'"
                    />
                    {{-- Small Progress Bar --}}
                    <div class="absolute bottom-0 left-0 right-0 h-1.5 bg-gray-200/30 backdrop-blur-sm">
                        <div class="h-full bg-linear-to-r from-amber-500 to-primary-500 transition-all duration-1000 ease-out" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0 pr-8 flex flex-col justify-between">
                    <div>
                        @if($package->category)
                            <p class="text-[9px] font-black text-primary-600 dark:text-primary-400 uppercase tracking-widest mb-1">{{ $package->category->name }}</p>
                        @endif

                        <h4 class="text-base font-bold text-gray-900 dark:text-white leading-tight truncate">
                            {{ $package->name }}
                        </h4>

                        <div class="flex items-center gap-1.5 mt-1.5">
                            <x-filament::icon icon="heroicon-s-building-storefront" class="w-3.5 h-3.5 text-gray-400 group-hover:text-primary-500 transition-colors" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $package->weddingOrganizer?->name }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-2 flex items-baseline gap-2">
                        @if($package->discount_price > 0)
                            <span class="text-lg font-black text-primary-600 dark:text-primary-400">
                                Rp {{ number_format($package->discount_price, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-400 line-through decoration-red-500/50">
                                Rp {{ number_format($package->price, 0, ',', '.') }}
                            </span>
                        @else
                            <span class="text-lg font-black text-primary-600 dark:text-primary-400">
                                Rp {{ number_format($package->price, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Footer Actions --}}
            <x-slot name="footer">
                <div class="flex items-center gap-2">
                    <x-filament::button
                        href="{{ $wishlistUrl }}"
                        tag="a"
                        color="danger"
                        icon="heroicon-m-heart"
                        size="sm"
                        outlined
                        class="rounded-xl"
                    >
                        {{ __('Favorit') }}
                    </x-filament::button>

                    <x-filament::button
                        href="{{ $bookUrl }}"
                        tag="a"
                        color="primary"
                        icon="heroicon-m-shopping-bag"
                        size="sm"
                        class="flex-1 rounded-xl shadow-lg shadow-primary-500/20"
                    >
                        {{ __('Pesan Sekarang') }}
                    </x-filament::button>
                </div>
            </x-slot>
        </x-filament::section>
        @endforeach
    </div>

    {{-- Show All Button --}}
    <div class="mt-6">
        <x-filament::button
            wire:click="$dispatch('refresh_catalog')"
            color="primary"
            size="lg"
            icon="heroicon-m-squares-2x2"
            class="w-full rounded-2xl shadow-xl shadow-primary-500/30 font-bold"
        >
            {{ __('Tampilkan di Katalog Utama') }}
        </x-filament::button>
    </div>

</div>
@endif
