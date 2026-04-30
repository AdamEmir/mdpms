<div>
    @include('partials.flash-messages')

    <h2 class="mb-6 text-xl font-semibold text-slate-900">Log in to your account</h2>

    <form wire:submit="submit" class="space-y-5" novalidate>
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <input id="email" type="email" wire:model="email" required autofocus autocomplete="email"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
            <input id="password" type="password" wire:model="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-md border-0 px-3 py-2 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-slate-900">
            @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center">
            <input id="remember" type="checkbox" wire:model="remember"
                   class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
            <label for="remember" class="ml-2 block text-sm text-slate-700">Remember me</label>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-slate-900">
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-slate-900 hover:underline">Register</a>
    </p>
</div>
