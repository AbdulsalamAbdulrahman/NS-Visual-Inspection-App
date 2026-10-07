<?php

declare(strict_types=1);

test('pages render in the light theme by default', function () {
    $this->get(route('login'))->assertOk()->assertSee('data-theme="light"', false);
});

test('a chosen theme is rendered before first paint', function (string $choice, ?string $attribute) {
    $response = $this->withUnencryptedCookie('appearance', $choice)->get(route('login'))->assertOk();

    $attribute
        ? $response->assertSee("data-theme=\"{$attribute}\"", false)
        : $response->assertDontSee('<html lang="en" data-theme', false);
})->with([
    'dark' => ['dark', 'dark'],
    'light' => ['light', 'light'],
    'auto resolves in the browser' => ['system', null],
    'unknown values fall back to light' => ['purple', 'light'],
]);
