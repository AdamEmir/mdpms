<?php

use App\Livewire\Auth\LogoutButton;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('logs the user out and redirects to login', function () {
    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(LogoutButton::class)
        ->call('logout')
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});
