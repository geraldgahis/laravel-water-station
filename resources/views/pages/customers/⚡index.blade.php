<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;

new class extends Component {
    use WithPagination;

    // This binds to the search input field
    public string $search = '';

    // Reset pagination when searching
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // 1. Add this new delete method
    public function delete(Customer $customer)
    {
        // Optional: You might want to check if they have pending orders first before allowing deletion!
        $customer->delete();

        // Flash a message so the user knows it worked
        session()->flash('success', 'Customer deleted successfully.');
    }

    public function with(): array
    {
        return [
            'customers' => Customer::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('phone', 'like', '%' . $this->search . '%')
                ->latest()
                ->paginate(10)
        ];
    }
};
?>

<div>
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Customers</h1>
            <p class="text-sm text-gray-500 mt-1">Manage your water delivery clients and view their details.</p>
        </div>

        <a href="{{ route('customers.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Customer
        </a>
    </div>
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-md bg-red-50 border border-red-200 flex items-start">
            <svg class="h-5 w-5 text-red-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <div>
                <h3 class="text-sm font-medium text-red-800">Deleted</h3>
                <div class="mt-1 text-sm text-red-700">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">

        <div class="p-4 border-b border-gray-200 bg-gray-50/50">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search by name or phone number..."
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 sm:text-sm transition-colors" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Phone</th>
                        <th class="px-6 py-3 font-medium">Address</th>
                        <th class="px-6 py-3 font-medium">Registered</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{ $customer->name }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                {{ $customer->phone }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <span class="line-clamp-1">{{ $customer->address }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                {{ $customer->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('customers.edit', $customer->id) }}"
                                    class="text-blue-600 hover:text-blue-800 font-medium mr-3">Edit</a>

                                <button wire:click="delete({{ $customer->id }})"
                                    wire:confirm="Are you sure you want to delete {{ $customer->name }}? This action cannot be undone."
                                    class="text-red-600 hover:text-red-800 font-medium mr-3 focus:outline-none">
                                    Delete
                                </button>

                                <a href="#" class="text-gray-500 hover:text-gray-700 font-medium">Orders</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <p class="text-gray-500">No customers found. Try adjusting your search or add a new one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-white">
                {{ $customers->links() }}
            </div>
        @endif

    </div>
</div>