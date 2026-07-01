<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }
};
?>

<aside
    class="w-64 bg-white border-r border-gray-200 h-screen fixed top-0 left-0 flex flex-col justify-between font-sans text-gray-900 shadow-sm">

    <div>
        <div class="h-16 flex items-center px-6 border-b border-gray-100 mb-4">
            <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z">
                </path>
            </svg>
            <span class="text-xl font-bold tracking-tight text-gray-800">Daloy POS</span>
        </div>

        <nav class="flex flex-col gap-1 px-4">
            <a href="{{ route('dashboard') }}"
                class="flex items-center px-3 py-2.5 rounded-md text-sm font-medium transition-colors 
                {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                    </path>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('products.index') }}"
                class="flex items-center px-3 py-2.5 rounded-md text-sm font-medium transition-colors 
                {{ request()->routeIs('products.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">

                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('products.*') ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                    </path>

                </svg>
                Products
            </a>

            <a href="{{ route('orders.index') }}"
                class="flex items-center px-3 py-2.5 rounded-md text-sm font-medium transition-colors 
                {{ request()->routeIs('orders.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('orders.*') ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                    </path>
                </svg>
                Orders
            </a>

            <a href="{{ route('customers.index') }}"
                class="flex items-center px-3 py-2.5 rounded-md text-sm font-medium transition-colors 
                {{ request()->routeIs('customers.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('customers.*') ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
                Customers
            </a>

            <a href="{{ route('reports.index') }}"
                class="flex items-center px-3 py-2.5 rounded-md text-sm font-medium transition-colors 
                {{ request()->routeIs('reports.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('reports.*') ? 'text-blue-600' : 'text-gray-400' }}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                Reports
            </a>

            @can('manage-users')
                <div class="mt-4 mb-2 px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Management
                </div>
                <a href="#"
                    class="flex items-center px-3 py-2.5 rounded-md text-sm font-medium transition-colors 
                                                                {{ request()->routeIs('staff.*') ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('staff.*') ? 'text-blue-600' : 'text-gray-400' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                    Staff Access
                </a>
            @endcan
        </nav>
    </div>

    <div class="border-t border-gray-100 p-4">
        <div class="flex items-center mb-4 px-2">
            <div
                class="h-9 w-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm mr-3">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-gray-900 truncate w-36">{{ auth()->user()->name }}</span>
                <span class="text-xs text-gray-500 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
            </div>
        </div>

        <button wire:click="logout"
            class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
            <span wire:loading.remove>Sign Out</span>
            <span wire:loading>Signing Out...</span>
        </button>
    </div>
</aside>