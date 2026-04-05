<?php

require __DIR__.'/../vendor/autoload.php';

// Fix for PHP 8.4 tempnam() notice in Laravel AliasLoader
// This handles the error at the earliest possible stage for tests
if (PHP_VERSION_ID >= 80400) {
    set_error_handler(function ($errno, $errstr) {
        if ($errno === E_NOTICE && str_contains($errstr, 'tempnam()')) {
            return true;
        }
        return false;
    }, E_NOTICE);
}
