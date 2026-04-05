<?php

namespace App\Filament\User\Pages;

use App\Models\PrivacyPolicy;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class Privacy extends Page
{
    protected static string $view = 'filament.user.pages.privacy';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $layout = 'filament-panels::components.layout.base';
    protected ?string $maxContentWidth = 'full';

    public ?PrivacyPolicy $pageRecord = null;

    public function mount(): void
    {
        $this->pageRecord = PrivacyPolicy::first();
    }

    public function getTitle(): string | Htmlable
    {
        return __($this->pageRecord?->title ?? 'Kebijakan Privasi');
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }

    public function getHeader(): ?View
    {
        return null;
    }
}
