<?php

/** @return array<string, mixed> */

return [
    /*
     |--------------------------------------------------------------------------
     | Locals
     |--------------------------------------------------------------------------
     |
     | add the locals that will be show on the languages selector
     |
     */
    'locals' => [
        'id' => ['flag' => 'id', 'label' => 'Indonesian'],
        'en' => ['flag' => 'gb', 'label' => 'English'],
        'en_US' => ['flag' => 'us', 'label' => 'English US'],
        'ar' => ['flag' => 'sa', 'label' => 'Arabic'],
        'de' => ['flag' => 'de', 'label' => 'German'],
        'es' => ['flag' => 'es', 'label' => 'Spanish'],
        'fr' => ['flag' => 'fr', 'label' => 'French'],
        'it' => ['flag' => 'it', 'label' => 'Italian'],
        'ja' => ['flag' => 'jp', 'label' => 'Japanese'],
        'ko' => ['flag' => 'kr', 'label' => 'Korean'],
        'zh' => ['flag' => 'cn', 'label' => 'Chinese'],
        'ru' => ['flag' => 'ru', 'label' => 'Russian'],
    ],

    /*
     |--------------------------------------------------------------------------
     | Show Flags
     |--------------------------------------------------------------------------
     |
     | Show flags on the language selector
     |
     */
    'show_flags' => true,

    /*
    |--------------------------------------------------------------------------
    |
    | Determines the render hook for the language switcher.
    | Available render hooks: https://filamentphp.com/docs/3.x/support/render-hooks#available-render-hooks
    |
    */

    'language_switcher_render_hook' => 'panels::user-menu.before',

    /*
     |--------------------------------------------------------------------------
     |
     | Language Switch Middlewares
     |
     */
    'language_switcher_middlewares' => [
        'web', 'mobile', 
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    |
    | set the redirect path when change the language between selected path or next request
    |
    */
    'redirect' => 'next',

    /*
    |--------------------------------------------------------------------------
    | User Language Table
    |--------------------------------------------------------------------------
    |
    | set the user language table to store the user language, if your model don't have lang field
    |
    */
    'allow_user_lang_table' => true,
];
