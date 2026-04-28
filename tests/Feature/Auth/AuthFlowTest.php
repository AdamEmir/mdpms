<?php

use App\Models\User;

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('lets a user register and lands them on the dashboard', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
    $this->assertAuthenticated();
});

it('lets an existing user log in', function () {
    User::factory()->create([
        'email' => 'admin@mdpms.test',
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => 'admin@mdpms.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

it('rejects invalid credentials', function () {
    User::factory()->create(['email' => 'admin@mdpms.test', 'password' => 'password']);

    $this->post(route('login.store'), [
        'email' => 'admin@mdpms.test',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs the user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});
