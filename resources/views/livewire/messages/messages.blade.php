@php
    use Jeddsaliba\FilamentMessages\Enums\MediaCollectionType;
@endphp
@props(['selectedConversation'])
<!-- Right Section (Chat Box) -->
<div style="--col-span-default: span 3 / span 3;"
    class="col-[--col-span-default] bg-white shadow-sm rounded-xl ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10 overflow-hidden flex flex-col">
    @if ($selectedConversation)
        <!-- Chat Header : Start -->
        <div class="grid grid-cols-[--cols-default] lg:grid-cols-[--cols-lg] p-6"
            style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-lg: repeat(1, minmax(0, 1fr));">
            <div style="--col-span-default: 1 / -1;" class="col-[--col-span-default]">
                <div class="flex gap-6 items-center">
                    @php
                        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($selectedConversation->inbox_title);
                        $alt = urlencode($selectedConversation->inbox_title);
                    @endphp
                    <x-filament::avatar src="{{ $avatar }}" alt="{{ $alt }}" size="lg" />
                    <div class="overflow-hidden">
                        <p class="text-base font-bold truncate text-gray-900 dark:text-white">{{ $selectedConversation->inbox_title }}</p>
                        @if ($selectedConversation->title)
                            <p class="text-base truncate text-gray-600 dark:text-gray-400">
                                {{ $selectedConversation->other_users->pluck('name')->implode(', ') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- Chat Header : End -->
        <!-- Chat Box : Start -->
        <div wire:poll.visible.{{ $pollInterval }}="pollMessages" id="chatContainer"
            class="flex flex-col-reverse flex-1 p-5 overflow-y-auto border-t">
            @foreach ($conversationMessages as $index => $message)
                <div @class([
                    'flex mb-2 px-2 items-end gap-2',
                    'justify-end' => $message->user_id === auth()->id(),
                    'justify-start' => $message->user_id !== auth()->id(),
                ]) wire:key="{{ $message->id }}">
                    @if ($message->user_id !== auth()->id())
                        @php
                            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($message->sender->name);
                            $alt = urlencode($message->sender->name);
                        @endphp
                        <x-filament::avatar src="{{ $avatar }}" alt="{{ $alt }}" size="sm" />
                    @endif
                    <div>
                        @if ($message->user_id !== auth()->id())
                            <p class="text-xs mb-2 text-gray-500 dark:text-gray-400">{{ $message->sender->name }}</p>
                        @endif
                        <div @class([
                            'max-w-md p-2 rounded-xl mb-2',
                            'text-white bg-primary-600 dark:bg-primary-500' =>
                                $message->user_id === auth()->id(),
                            'text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-500' =>
                                $message->user_id !== auth()->id(),
                        ]) @style([
                            'border-bottom-right-radius: 0' => $message->user_id === auth()->id(),
                            'border-bottom-left-radius: 0' => $message->user_id !== auth()->id(),
                        ])>
                            <div class="px-1">
                                @if ($message->message)
                                    <p class="text-sm">{!! nl2br($message->message) !!}</p>
                                @endif
                                @if (
                                    $message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value) &&
                                        count($message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value)) > 0)
                                    @foreach ($message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value) as $index => $media)
                                        <div wire:click="downloadAttachment('{{ $media->getPath() }}', '{{ $media->file_name }}')"
                                            @class([
                                                'flex items-center gap-2 p-2 my-2 rounded-lg group cursor-pointer',
                                                'bg-gray-200 dark:bg-gray-600' => $message->user_id !== auth()->id(),
                                                'bg-primary-500 dark:bg-primary-400' => $message->user_id === auth()->id(),
                                            ])>
                                            <div @class([
                                                'p-2 rounded-full',
                                                'bg-gray-100 dark:bg-gray-500' => $message->user_id !== auth()->id(),
                                                'bg-primary-600 group-hover:bg-primary-700 group-hover:dark:bg-primary-900' =>
                                                    $message->user_id === auth()->id(),
                                            ])>
                                                @php
                                                    $icon = 'heroicon-o-x-circle';
                                                    if ($this->validateImage($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-photo';
                                                    }

                                                    if ($this->validateDocument($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-paper-clip';
                                                    }

                                                    if ($this->validateVideo($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-video-camera';
                                                    }

                                                    if ($this->validateAudio($media->getFullUrl())) {
                                                        $icon = 'heroicon-o-speaker-wave';
                                                    }
                                                @endphp
                                                <x-filament::icon icon="{{ $icon }}" class="w-4 h-4" />
                                            </div>
                                            <p class="text-sm">
                                                {{ $media->file_name }}
                                            </p>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <p @class([
                            'text-[10px] opacity-70',
                            'text-end text-white/80' => $message->user_id === auth()->id(),
                            'text-start text-gray-500 dark:text-gray-400' => $message->user_id !== auth()->id(),
                        ])>
                            @php
                                $createdAt = \Carbon\Carbon::parse($message->created_at)->setTimezone(
                                    config('messages.timezone', 'app.timezone'),
                                );

                                if ($createdAt->isToday()) {
                                    $date = $createdAt->format('g:i A');
                                } else {
                                    $date = $createdAt->format('M d, Y g:i A');
                                }
                            @endphp
                            {{ $date }}
                        </p>
                    </div>
                </div>
                @php
                    $nextMessage = $conversationMessages[$index + 1] ?? null;
                    $nextMessageDate = $nextMessage
                        ? \Carbon\Carbon::parse($nextMessage->created_at)
                            ->setTimezone(config('messages.timezone', 'app.timezone'))
                            ->format('Y-m-d')
                        : null;
                    $currentMessageDate = \Carbon\Carbon::parse($message->created_at)
                        ->setTimezone(config('messages.timezone', 'app.timezone'))
                        ->format('Y-m-d');
                    $showDateBadge = $currentMessageDate !== $nextMessageDate;
                @endphp
                @if ($showDateBadge)
                    <div class="flex justify-center my-4">
                        <x-filament::badge>
                            {{ \Carbon\Carbon::parse($message->created_at)->setTimezone(config('messages.timezone', 'app.timezone'))->format('F j, Y') }}
                        </x-filament::badge>
                    </div>
                @endif
            @endforeach
            @if ($this->paginator->hasMorePages())
                <div x-intersect="$wire.loadMessages">
                    <div class="w-full py-6 text-center text-gray-900 dark:text-gray-200">{{ __('Getting more messages...') }}</div>
                </div>
            @endif
        </div>
        <!-- Chat Box : End -->
        <!-- Chat Input : Start -->
        <div class="w-full p-4 border-t relative">
            <form wire:submit="sendMessage" class="flex items-end justify-between w-full gap-4">
                <div class="w-full max-h-96 overflow-y-auto p-1">
                    {{ $this->form }}
                </div>
                <div class="p-1">
                    <x-filament::button wire:click="sendMessage" icon="heroicon-o-paper-airplane"
                        wire:loading.attr="disabled">{{ __('Kirim') }}</x-filament::button>
                </div>
            </form>
            <x-filament-actions::modals />
        </div>
        <!-- Chat Input : End -->

        <!-- Camera Modal : Start -->
        <div id="camera-modal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" style="display:none;">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden w-full max-w-md mx-4">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-camera" class="w-5 h-5 text-primary-500" />
                        {{ __('Take a Photo') }}
                    </h3>
                    <button id="close-camera-btn" type="button"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>
                <!-- Video / Preview -->
                <div class="relative bg-black">
                    <video id="camera-video" autoplay playsinline
                        class="w-full" style="max-height: 320px; object-fit: cover;"></video>
                    <canvas id="camera-canvas" class="hidden w-full" style="max-height: 320px; object-fit: cover;"></canvas>
                    <!-- Switch camera overlay button -->
                    <button id="switch-camera-btn" type="button"
                        class="absolute top-3 right-3 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition">
                        <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5" />
                    </button>
                </div>
                <!-- Controls -->
                <div id="camera-controls-capture" class="flex items-center justify-center gap-4 p-5">
                    <button id="capture-btn" type="button"
                        class="w-16 h-16 bg-primary-600 hover:bg-primary-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105">
                        <x-filament::icon icon="heroicon-o-camera" class="w-7 h-7" />
                    </button>
                </div>
                <div id="camera-controls-preview" class="flex items-center justify-between gap-3 px-5 pb-5" style="display:none;">
                    <button id="retake-btn" type="button"
                        class="flex-1 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition">
                        {{ __('Retake') }}
                    </button>
                    <button id="send-photo-btn" type="button"
                        class="flex-1 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition">
                        {{ __('Send Photo') }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Camera Modal : End -->

    @else
        <div class="flex flex-col items-center justify-center h-full p-3">
            <div class="p-3 mb-4 bg-gray-100 rounded-full dark:bg-gray-500/20">
                <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6 text-gray-500 dark:text-gray-400" />
            </div>
            <p class="text-base text-center text-gray-600 dark:text-gray-400">
                {{ __('No selected conversation') }}
            </p>
        </div>
    @endif
</div>
@script
    <script>
        $wire.on('chat-box-scroll-to-bottom', () => {

            chatContainer = document.getElementById('chatContainer');
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: 'smooth',
            });

            setTimeout(() => {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 400);
        });




        let cameraStream = null;
        let facingMode = 'user'; // 'user' = front, 'environment' = back

        async function startCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
            }
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode },
                    audio: false
                });
                const video = document.getElementById('camera-video');
                video.srcObject = cameraStream;

                // Reset to live view
                video.classList.remove('hidden');
                document.getElementById('camera-canvas').classList.add('hidden');
                document.getElementById('camera-controls-capture').classList.remove('hidden');
                document.getElementById('camera-controls-preview').style.display = 'none';
            } catch (err) {
                alert('{{ __('Cannot access camera. Please allow camera permission.') }}');
                closeCameraModal();
            }
        }

        function closeCameraModal() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
            document.getElementById('camera-modal').style.display = 'none';
        }


        window.addEventListener('open-camera', () => {
            document.getElementById('camera-modal').style.display = 'flex';
            facingMode = 'environment';
            startCamera();
        });

        document.getElementById('close-camera-btn').addEventListener('click', closeCameraModal);

        document.getElementById('switch-camera-btn').addEventListener('click', () => {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            startCamera();
        });

        document.getElementById('capture-btn').addEventListener('click', () => {
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            video.classList.add('hidden');
            canvas.classList.remove('hidden');
            document.getElementById('camera-controls-capture').classList.add('hidden');
            document.getElementById('camera-controls-preview').style.display = 'flex';
        });

        document.getElementById('retake-btn').addEventListener('click', () => {
            startCamera();
        });

        document.getElementById('send-photo-btn').addEventListener('click', async () => {
            const canvas = document.getElementById('camera-canvas');
            canvas.toBlob(async (blob) => {
                const fileName = 'camera_' + Date.now() + '.jpg';
                const file = new File([blob], fileName, { type: 'image/jpeg' });

                // Use FilePond or native input — inject into a hidden file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);

                // Find the Livewire file input for attachments
                const fileInputs = document.querySelectorAll('input[type="file"]');
                if (fileInputs.length > 0) {
                    const fileInput = fileInputs[0];
                    fileInput.files = dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                    closeCameraModal();
                } else {
                    // Fallback: download the image
                    const a = document.createElement('a');
                    a.href = canvas.toDataURL('image/jpeg');
                    a.download = fileName;
                    a.click();
                    closeCameraModal();
                }
            }, 'image/jpeg', 0.92);
        });
    </script>
@endscript
