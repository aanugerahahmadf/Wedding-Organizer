@php
    $isAuth = str_contains(request()->route()?->getName() ?? '', 'auth');
@endphp

<footer class="fi-main-footer {{ $isAuth ? 'fi-auth-footer' : '' }}">
    <div class="footer-text">
        &copy; {{ date('Y') }} Devi Make up {{ __('All rights reserved') }}
    </div>
</footer>

<style>
    .fi-main-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 20;
        background-color: #ffffff;
        border-top: 1px solid rgb(229 231 235);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 4rem;
    }

    /* Override for Auth Page */
    .fi-auth-footer {
        position: static !important;
        background-color: transparent !important;
        border-top: none !important;
        height: auto !important;
        margin-top: 0.5rem;
        padding-bottom: 0;
    }

    /* Warna dark mode otomatis disinkronkan ke variable warna Gray 900 bawaan Filament */
    .dark .fi-main-footer {
        background-color: rgb(var(--gray-900));
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dark .fi-auth-footer {
        background-color: transparent !important;
    }

    /* Sinkronkan jenis Font & Warna tulisan */
    .footer-text {
        font-family: var(--font-family, inherit);
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(var(--gray-500));
        text-align: center;
        padding: 0 1rem;
    }

    .dark .footer-text {
        color: rgb(var(--gray-400));
    }

    /* Only add padding if footer is fixed */
    body:not(:has(.fi-auth-footer)) .fi-layout,
    body:not(:has(.fi-auth-footer)) .fi-main,
    body:not(:has(.fi-auth-footer)) .fi-content {
        padding-bottom: 4.5rem !important;
    }
</style>
