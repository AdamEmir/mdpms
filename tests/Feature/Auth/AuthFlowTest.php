<?php

use App\Livewire\Auth\LogoutButton;
use App\Models\User;
use Livewire\Livewire;

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('logs the user out', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(LogoutButton::class)
        ->call('logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
