<?php

use Livewire\Component;
use App\Models\Customer;

new class extends Component {
    // We store the actual Customer model here
    public Customer $customer;

    // Form fields
    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $notes = '';

    // Mount runs once when the page loads. It grabs the customer from the URL.
    public function mount(Customer $customer)
    {
        $this->customer = $customer;
        $this->name = $customer->name;
        $this->phone = $customer->phone;
        $this->address = $customer->address;
        $this->notes = $customer->notes ?? '';
    }

    public function update()
    {
        // Notice the unique rule: we append the customer's ID so it ignores itself
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50|unique:customers,phone,' . $this->customer->id,
            'address' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Update the existing record
        $this->customer->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'address' => $this->address,
            'notes' => $this->notes,
        ]);

        session()->flash('success', 'Customer details successfully updated!');
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Edit Customer</h1>
            <p class="text-sm text-gray-500 mt-1">Updating details for <span
                    class="font-medium text-gray-900">{{ $customer->name }}</span>.</p>
        </div>

        <a href="{{ route('customers.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900 bg-white px-4 py-2 rounded-md border border-gray-200 shadow-sm transition-colors">
            &larr; Back to List
        </a>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-md bg-green-50 border border-green-200 flex items-start">
            <svg class="h-5 w-5 text-green-400 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h3 class="text-sm font-medium text-green-800">Success</h3>
                <div class="mt-1 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <form wire:submit="update" class="p-6 md:p-8 flex flex-col gap-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-sm font-medium text-gray-700">Full Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="name" wire:model="name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                        required />
                    @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="phone" class="text-sm font-medium text-gray-700">Phone Number <span
                            class="text-red-500">*</span></label>
                    <input type="tel" id="phone" wire:model="phone"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                        required />
                    @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="address" class="text-sm font-medium text-gray-700">Delivery Address <span
                        class="text-red-500">*</span></label>
                <textarea id="address" wire:model="address" rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                    required></textarea>
                @error('address') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="notes" class="text-sm font-medium text-gray-700">Delivery Notes (Optional)</label>
                <textarea id="notes" wire:model="notes" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"></textarea>
                @error('notes') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 mt-2 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors disabled:opacity-50 flex items-center">
                    <span wire:loading.remove wire:target="update">Update Customer</span>
                    <span wire:loading wire:target="update">Saving Changes...</span>
                </button>
            </div>

        </form>
    </div>
</div>