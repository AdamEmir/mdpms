<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Livewire\Livewire;

it('creates a user, logs in, and redirects to dashboard', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('submit')
        ->assertRedirect(route('dashboard'));

    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    expect(auth()->check())->toBeTrue();
});

it('rejects mismatched password confirmation', function () {
    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('submit')
        ->assertHasErrors(['password' => 'confirmed']);
});

it('rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'taken@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('submit')
        ->assertHasErrors(['email' => 'unique']);
});

it('rejects short password', function () {
    // Pinned to field, not rule key: Password::min(8) is a Rule object, so the
    // outer validator reports the failure under the rule's class — `min` won't match.
    Livewire::test(Register::class)
        ->set('name', 'New Person')
        ->set('email', 'new@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('submit')
        ->assertHasErrors(['password']);
});
