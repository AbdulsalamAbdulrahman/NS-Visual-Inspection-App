<?php

declare(strict_types=1);

test('requesting a reset link for an unknown email looks the same as a known one', function () {
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');
});
