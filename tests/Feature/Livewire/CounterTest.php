<?php

use Livewire\Volt\Volt;

it('can render', function (): void {
    $component = Volt::test('counter');

    $component->assertSee('');
});
