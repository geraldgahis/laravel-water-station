<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $email = '';
    public string $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();

            // Redirect to your POS dashboard route
            return redirect()->intended(route('dashboard'));
        }

        // Add an error if the credentials fail
        $this->addError('email', 'The provided credentials do not match our records.');
    }
};
?>

<div>
    <div class="min-h-screen flex flex-col justify-center items-center px-4 font-sans text-gray-900">

        <div class="w-full max-w-sm flex flex-col">

            <div class="mb-6">
                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-semibold tracking-tight mb-2">
                Log in to your account
            </h1>
            <p class="text-sm text-gray-500 mb-8">
                Welcome back! Please enter your details.
            </p>

            <form wire:submit="login" class="flex flex-col gap-5">

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" wire:model="email" placeholder="Enter your email"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                        required />

                    @error('email')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                        <label for="password" class="text-sm font-medium text-gray-700">Password</label>
                    </div>
                    <input type="password" id="password" wire:model="password" placeholder="••••••••"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                        required />
                </div>

                <button type="submit"
                    class="w-full mt-2 bg-blue-600 text-white py-2.5 rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors disabled:opacity-50">

                    <span wire:loading.remove>Sign In</span>
                    <span wire:loading>Authenticating...</span>

                </button>
            </form>
        </div>
    </div>
</div>