<?php

use Livewire\Component;
use App\Models\{Customer, Product, Order, OrderItem, Payment};
use Illuminate\Support\Facades\DB;

new class extends Component {
    public Order $order;

    // Cart & Products (No Customer Variables needed!)
    public $product_id = '';
    public $quantity = 1;
    public array $cart = [];
    public string $payment_method = 'cash';

    public function mount(Order $order)
    {
        // Load the order with its relationships
        $this->order = $order->load(['items.product', 'customer', 'payment']);

        // Populate Payment Method
        $this->payment_method = $this->order->payment->method ?? 'cash';

        // Populate Cart
        foreach ($this->order->items as $item) {
            $this->cart[] = [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown Product',
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => $item->quantity * $item->unit_price
            ];
        }
    }

    public function getCartTotalProperty()
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function addToCart()
    {
        $this->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($this->product_id);

        $found = false;
        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] == $product->id) {
                $this->cart[$key]['quantity'] += $this->quantity;
                $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $product->price;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->cart[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $this->quantity,
                'subtotal' => $product->price * $this->quantity
            ];
        }

        $this->reset('product_id');
        $this->quantity = 1;
        $this->resetErrorBag('cart');
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function updateOrder()
    {
        $this->validate([
            'payment_method' => 'required|in:cash,gcash,bank_transfer',
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Please add at least one product to the order.');
            return;
        }

        DB::transaction(function () {
            // 1. RESTOCK ORIGINAL ITEMS
            foreach ($this->order->items as $originalItem) {
                if ($originalItem->product) {
                    $originalItem->product->increment('stock_count', $originalItem->quantity);
                }
            }

            // 2. Clear old items from database
            $this->order->items()->delete();

            // 3. ADD NEW ITEMS & DEDUCT NEW STOCK
            foreach ($this->cart as $item) {
                OrderItem::create([
                    'order_id' => $this->order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->decrement('stock_count', $item['quantity']);
                }
            }

            // 4. Update the Payment Total
            if ($this->order->payment) {
                $this->order->payment->update([
                    'amount' => $this->cartTotal,
                    'method' => $this->payment_method
                ]);
            }
        });

        session()->flash('success', 'Order updated successfully and inventory recalculated!');
        return redirect()->route('orders.index');
    }
};
?>

<div class="mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Edit Order
                #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-sm text-gray-500 mt-1">Status: <span class="font-bold uppercase">{{ $order->status }}</span>
            </p>
        </div>
        <a href="{{ route('orders.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900 bg-white px-4 py-2 rounded-md border border-gray-200 shadow-sm transition-colors">
            &larr; Cancel Edit
        </a>
    </div>

    @if($order->status === 'delivered')
        <div class="mb-6 p-4 rounded-md bg-yellow-50 border border-yellow-200 flex items-start">
            <svg class="h-5 w-5 text-yellow-500 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h3 class="text-sm font-bold text-yellow-800">Editing a Delivered Order</h3>
                <div class="mt-1 text-sm text-yellow-700">
                    This order is already marked as delivered. Changing quantities here will act as a partial
                    return/correction and adjust your inventory.
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <form wire:submit="updateOrder" class="p-6 md:p-8 flex flex-col gap-8">

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-gray-700">Customer Details</label>
                <div
                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-md text-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white rounded shadow-sm border border-gray-100">
                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <span class="block font-semibold text-gray-900">{{ $order->customer->name }}</span>
                            <span class="block text-gray-500 mt-0.5">{{ $order->customer->phone }}</span>
                        </div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-400 uppercase tracking-wider bg-gray-200/50 px-2.5 py-1 rounded">Locked
                        for Edit</span>
                </div>
            </div>

            <hr class="border-gray-100">

            <div class="flex flex-col gap-4">
                <h3 class="text-lg font-medium text-gray-900">Order Items</h3>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-2">
                    <div class="md:col-span-7 flex flex-col gap-1.5">
                        <select wire:model="product_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 bg-white">
                            <option value="">Select a product to add...</option>
                            @foreach(App\Models\Product::orderBy('name')->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (₱{{ number_format($p->price, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <input type="number" wire:model="quantity" min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600" />
                    </div>
                    <div class="md:col-span-3">
                        <button type="button" wire:click="addToCart"
                            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-300 rounded-md hover:bg-gray-100 transition-colors">Add
                            Item</button>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-md overflow-hidden mt-2">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-medium">Item</th>
                                <th class="px-4 py-3 font-medium text-center">Qty</th>
                                <th class="px-4 py-3 font-medium text-right">Subtotal</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($cart as $index => $item)
                                <tr class="bg-white">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        ₱{{ number_format($item['subtotal'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" wire:click="removeFromCart({{ $index }})"
                                            class="text-red-500 hover:text-red-700 text-xs font-medium focus:outline-none">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @error('cart') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <hr class="border-gray-100">

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Payment:</label>
                    <select wire:model="payment_method"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm bg-white">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="text-right">
                    <span class="text-sm text-gray-500 mr-2">New Total:</span>
                    <span class="text-2xl font-bold text-gray-900">₱{{ number_format($this->cartTotal, 2) }}</span>
                </div>
            </div>

            <div class="pt-4 mt-2 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors flex items-center">
                    <span wire:loading.remove wire:target="updateOrder">Update Order</span>
                    <span wire:loading wire:target="updateOrder">Updating...</span>
                </button>
            </div>
        </form>
    </div>
</div>