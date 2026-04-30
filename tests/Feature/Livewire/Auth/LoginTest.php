<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('logs in with valid credentials and redirects to dashboard', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('secret123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'secret123')
        ->call('submit')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id);
});

it('rejects invalid credentials with an error', function () {
    User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('secret123')]);

    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'wrong')
        ->call('submit')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('throttles after 5 failed attempts', function () {
    User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('secret123')]);
    $key = strtolower('user@example.com').'|127.0.0.1';
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($key);
    }

    $component = Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'secret123')
        ->call('submit')
        ->assertHasErrors('email');

    expect($component->errors()->first('email'))->toContain('Too many login attempts');
});

it('validates required fields', function () {
    Livewire::test(Login::class)
        ->call('submit')
        ->assertHasErrors(['email' => 'required', 'password' => 'required']);
});
