<?php

test('the application returns a successful response', function (): void {
    /** @var Tests\TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(200);
});

