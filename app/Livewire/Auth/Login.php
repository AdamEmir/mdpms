<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Log in')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function submit(): mixed
    {
        $this->validate();

        $key = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many login attempts. Try again in {$seconds} seconds.");

            return null;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('email', 'These credentials do not match our records.');

            return null;
        }

        RateLimiter::clear($key);
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'), navigate: true);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
