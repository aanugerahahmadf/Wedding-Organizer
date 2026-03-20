<?php

namespace App\Livewire\Messages;

use App\Enums\Messages\MediaCollectionType;
use emmanpbarrameda\FilamentTakePictureField\Forms\Components\TakePicture;
use TangoDevIt\FilamentEmojiPicker\EmojiPickerAction;
use App\Livewire\Traits\CanMarkAsRead;
use App\Livewire\Traits\CanValidateFiles;
use App\Livewire\Traits\HasPollInterval;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @mixin \Livewire\Component
 */
class Messages extends Component implements HasForms
{
    use CanMarkAsRead, CanValidateFiles, HasPollInterval, InteractsWithForms, WithPagination;

    public $selectedConversation;

    public $currentPage = 1;

    public Collection $conversationMessages;

    public ?array $data = [];

    public bool $showUpload = false;

    public bool $showEmojiPicker = false;

    public bool $showCamera = false;

    public function mount(): void
    {
        $this->setPollInterval();
        $this->form->fill();
        if ($this->selectedConversation) {
            $this->conversationMessages = collect();
            $this->loadMessages();
            $this->markAsRead();
        }
    }

    public function pollMessages(): void
    {
        $latestId = $this->conversationMessages->pluck('id')->first();

        /** @var Builder $query */
        $query = $this->selectedConversation->messages();

        $polledMessages = $query->where('id', '>', $latestId ?? 0)->latest()->get(['*']);
        if ($polledMessages->isNotEmpty()) {
            $this->conversationMessages = collect([
                ...$polledMessages,
                ...$this->conversationMessages,
            ]);
        }
    }

    public function loadMessages(): void
    {
        $this->conversationMessages->push(...$this->paginator->items());
        $this->currentPage += 1;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')
                    ->hiddenLabel()
                    ->collection(MediaCollectionType::FILAMENT_MESSAGES->value)
                    ->multiple()
                    ->panelLayout('grid')
                    ->visible(fn () => $this->showUpload)
                    ->maxFiles(config('messages.attachments.max_files'))
                    ->minFiles(config('messages.attachments.min_files'))
                    ->maxSize(config('messages.attachments.max_file_size'))
                    ->minSize(config('messages.attachments.min_file_size'))
                    ->live(),
                Forms\Components\Split::make([
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('show_hide_upload')
                            ->hiddenLabel()
                            ->icon('heroicon-o-paper-clip')
                            ->color('gray')
                            ->tooltip(__('Attach Files'))
                            ->action(fn () => $this->showUpload = ! $this->showUpload),
                        Forms\Components\Actions\Action::make('toggle_camera')
                            ->hiddenLabel()
                            ->icon('heroicon-o-camera')
                            ->color('gray')
                            ->tooltip(__('Open Camera'))
                            ->action(fn () => $this->showCamera = ! $this->showCamera),
                    ])->grow(false),
                    Forms\Components\TextInput::make('message')
                        ->live()
                        ->hiddenLabel()
                        ->placeholder(__('Write a message...'))
                        ->suffixAction(EmojiPickerAction::make('emoji-message')),
                ])->verticallyAlignEnd(),
                TakePicture::make('camera_image')
                    ->hiddenLabel()
                    ->visible(fn () => $this->showCamera)
                    ->disk('public') // Bisa disesuaikan dengan disk aplikasi Anda
                    ->directory('messages-camera') // Folder penyimpanan sementara gambar
                    ->visibility('public')
                    ->showCameraSelector(true)
                    ->aspect('16:9')
                    ->imageQuality(80),
            ])->statePath('data');
    }

    public function sendMessage(): void
    {
        $data = $this->form->getState();
        $rawData = $this->form->getRawState();

        try {
            DB::transaction(function () use ($data, $rawData): void {
                $this->showUpload = false;

                $newMessage = $this->selectedConversation->messages()->create([
                    'message' => $data['message'] ?? null,
                    'user_id' => Auth::id(),
                    'read_by' => [Auth::id()],
                    'read_at' => [now()],
                    'notified' => [Auth::id()],
                ]);

                $this->conversationMessages->prepend($newMessage);
                collect($rawData['attachments'] ?? [])->each(function ($attachment) use ($newMessage): void {
                    $newMessage->addMedia($attachment)->usingFileName(Str::slug(config('messages.slug'), '_').'_'.Str::random(20).'.'.$attachment->extension())->toMediaCollection(MediaCollectionType::FILAMENT_MESSAGES->value);
                });

                if (!empty($data['camera_image'])) {
                    // Ambil config 'disk' aplikasi/gambar - jika pakai default public
                    $newMessage->addMediaFromDisk($data['camera_image'], 'public')
                         ->toMediaCollection(MediaCollectionType::FILAMENT_MESSAGES->value);
                }

                $this->showCamera = false;
                $this->form->fill();

                $this->selectedConversation->updated_at = now();

                $this->selectedConversation->save();

                $this->dispatch('refresh-inbox');
            });
        } catch (\Exception $exception) {
            Notification::make()
                ->title(__('Something went wrong'))
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    #[Computed()]
    public function paginator(): Paginator
    {
        /** @var Builder $query */
        $query = $this->selectedConversation->messages();

        return $query->latest()->paginate(10, ['*'], 'page', $this->currentPage);
    }

    public function downloadAttachment(string $filePath, string $fileName)
    {
        return response()->download($filePath, $fileName);
    }

    public function validateMessage(): bool
    {
        $rawData = $this->form->getRawState();
        if (empty($rawData['attachments']) && ! $rawData['message']) {
            return true;
        }

        return false;
    }

    public function render(): Application|Factory|View|\Illuminate\View\View
    {
        return view('livewire.messages.messages');
    }
}
