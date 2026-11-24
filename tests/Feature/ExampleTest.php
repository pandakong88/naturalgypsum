<?php

use App\Models\User;

test('unauthenticated visitor to root is redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

test('authenticated visitor to root is redirected to dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('dashboard'));
});
