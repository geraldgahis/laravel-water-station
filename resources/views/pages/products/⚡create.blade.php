<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component {
    public string $name = '';
    public string $description = '';
    public string $price = ''; // Using string so the input starts blank instead of '0'
    public string $stock_count = '';

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_count' => 'required|integer|min:0',
        ]);

        Product::create([
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock_count' => (int) $this->stock_count,
        ]);

        $this->clearForm();
        session()->flash('success', 'Product successfully added to your inventory!');
    }

    public function clearForm()
    {
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->stock_count = '';
        $this->resetValidation();
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Add New Product</h1>
            <p class="text-sm text-gray-500 mt-1">Register a new item or refill type to your catalog.</p>
        </div>

        <a href="{{ route('products.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900 bg-white px-4 py-2 rounded-md border border-gray-200 shadow-sm transition-colors">
            &larr; Back to Inventory
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
        <form wire:submit="save" class="p-6 md:p-8 flex flex-col gap-6">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-sm font-medium text-gray-700">Product Name <span
                            class="text-red-500">*</span></label>
                    <input type="text" id="name" wire:model="name" placeholder="e.g. 5 Gallon Refill (Round)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                        required />
                    @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="price" class="text-sm font-medium text-gray-700">Selling Price (₱) <span
                            class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">₱</span>
                        </div>
                        <input type="number" id="price" wire:model="price" step="0.01" min="0" placeholder="0.00"
                            class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                            required />
                    </div>
                    @error('price') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col gap-1.5">
                    <label for="stock_count" class="text-sm font-medium text-gray-700">Initial Stock <span
                            class="text-red-500">*</span></label>
                    <input type="number" id="stock_count" wire:model="stock_count" min="0" placeholder="e.g. 100"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                        required />
                    <p class="text-xs text-gray-500">Track physical containers or use a high number (e.g., 9999) for
                        unlimited refills.</p>
                    @error('stock_count') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="description" class="text-sm font-medium text-gray-700">Description (Optional)</label>
                <textarea id="description" wire:model="description" rows="2"
                    placeholder="e.g. Purified drinking water refill..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"></textarea>
                @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 mt-2 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" wire:click="clearForm"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Clear Form
                </button>

                <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors disabled:opacity-50 flex items-center">
                    <span wire:loading.remove wire:target="save">Save Product</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>

        </form>
    </div>
</div>