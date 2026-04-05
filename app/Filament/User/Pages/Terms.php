<?php

namespace App\Filament\User\Pages;

use App\Models\TermsOfService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class Terms extends Page
{
    protected static string $view = 'filament.user.pages.terms';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $layout = 'filament-panels::components.layout.base';
    protected ?string $maxContentWidth = 'full';

    public ?TermsOfService $pageRecord = null;

    public function mount(): void
    {
        $this->pageRecord = TermsOfService::first();
    }

    public function getTitle(): string | Htmlable
    {
        return __($this->pageRecord?->title ?? 'Syarat & Ketentuan');
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
