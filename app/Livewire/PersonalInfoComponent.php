<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Joaopaulolndev\FilamentEditProfile\Concerns\HasSort;
use Livewire\Component;

class PersonalInfoComponent extends Component implements HasForms
{
    use HasSort;
    use InteractsWithForms;

    public ?array $data = [];

    protected static int $sort = 1;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->form->fill([
                'avatar_url' => $user->avatar_url,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Profile Information')
                    ->aside()
                    ->description('Update your account profile information and email address.')
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Photo')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->columnSpanFull(),
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignorable: Auth::user()),
                        TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            /** @var User $user */
            $user = Auth::user();

            $user->update($data);

            Notification::make()
                ->title('Profile updated successfully!')
                ->success()
                ->send();

            $this->dispatch('profile-updated');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to update profile')
                ->danger()
                ->send();
        }
    }

    public function render(): View
    {
        return view('livewire.personal-info-component');
    }
}
