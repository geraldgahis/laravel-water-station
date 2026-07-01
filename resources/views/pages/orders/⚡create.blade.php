<?php

use Livewire\Component;
use App\Models\{Customer, Product, Order, OrderItem, Payment};
use Illuminate\Support\Facades\DB;

new class extends Component {
    // Customer Search
    public string $customerSearch = '';
    public $customer_id = '';
    public $selectedCustomer = null;

    // Cart & Products
    public $product_id = '';
    public $quantity = 1;
    public array $cart = [];
    public string $payment_method = 'cash';

    // Computed property for the live search dropdown
    public function getCustomerSearchResultsProperty()
    {
        if (strlen($this->customerSearch) < 2) {
            return [];
        }
        return Customer::where('name', 'like', '%' . $this->customerSearch . '%')
            ->orWhere('phone', 'like', '%' . $this->customerSearch . '%')
            ->take(5)
            ->get();
    }

    public function selectCustomer($id)
    {
        $this->selectedCustomer = Customer::find($id);
        $this->customer_id = $this->selectedCustomer->id;
        $this->customerSearch = '';
        $this->resetValidation('customer_id');
    }

    public function clearCustomer()
    {
        $this->selectedCustomer = null;
        $this->customer_id = '';
        $this->customerSearch = '';
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

        // 1. Check how many of this product are ALREADY in the cart
        $currentCartQty = 0;
        $foundKey = null;

        foreach ($this->cart as $key => $item) {
            if ($item['product_id'] == $product->id) {
                $currentCartQty = $item['quantity'];
                $foundKey = $key;
                break;
            }
        }

        // 2. Validate against available database stock
        if (($currentCartQty + $this->quantity) > $product->stock_count) {
            $this->addError('quantity', 'Not enough stock! Only ' . $product->stock_count . ' available.');
            return;
        }

        // 3. Add or update the cart
        if ($foundKey !== null) {
            $this->cart[$foundKey]['quantity'] += $this->quantity;
            $this->cart[$foundKey]['subtotal'] = $this->cart[$foundKey]['quantity'] * $product->price;
        } else {
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
        $this->resetErrorBag();
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function saveOrder()
    {
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_method' => 'required|in:cash,gcash,bank_transfer',
        ], [
            'customer_id.required' => 'Please search and select a customer.'
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Please add at least one product to the order.');
            return;
        }

        DB::transaction(function () {
            $order = Order::create([
                'customer_id' => $this->customer_id,
                'status' => 'pending'
            ]);

            foreach ($this->cart as $item) {
                // Attach item to the order
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ]);

                // NEW: Deduct the inventory immediately
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->decrement('stock_count', $item['quantity']);
                }
            }

            Payment::create([
                'order_id' => $order->id,
                'amount' => $this->cartTotal,
                'method' => $this->payment_method,
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        session()->flash('success', 'Order successfully created and inventory updated!');
        return redirect()->route('orders.index');
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Create Order</h1>
            <p class="text-sm text-gray-500 mt-1">Log a new transaction and schedule a delivery.</p>
        </div>

        <a href="{{ route('orders.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900 bg-white px-4 py-2 rounded-md border border-gray-200 shadow-sm transition-colors">
            &larr; Back to Orders
        </a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <div class="p-6 md:p-8 flex flex-col gap-8">

            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-100 pb-2">1. Customer Details
                </h3>

                <div class="flex flex-col gap-1.5 max-w-2xl">
                    <label class="text-sm font-medium text-gray-700">Search Customer <span
                            class="text-red-500">*</span></label>

                    @if($selectedCustomer)
                        <div
                            class="w-full px-3 py-2 border border-blue-300 bg-blue-50 rounded-md text-sm flex justify-between items-center transition-colors">
                            <div>
                                <span class="font-semibold text-blue-900">{{ $selectedCustomer->name }}</span>
                                <span class="text-blue-700 ml-2">({{ $selectedCustomer->phone }})</span>
                            </div>
                            <button type="button" wire:click="clearCustomer"
                                class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                Change
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="customerSearch"
                                placeholder="Type name or phone number..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
                                autocomplete="off" />

                            @if(!empty($customerSearch) && count($this->customerSearchResults) > 0)
                                <div
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg overflow-hidden">
                                    <ul class="max-h-60 overflow-auto divide-y divide-gray-100">
                                        @foreach($this->customerSearchResults as $result)
                                            <li>
                                                <button type="button" wire:click="selectCustomer({{ $result->id }})"
                                                    class="w-full text-left px-3 py-2 hover:bg-gray-50 transition-colors text-sm">
                                                    <span class="font-medium text-gray-900">{{ $result->name }}</span>
                                                    <span class="text-gray-500 ml-2">{{ $result->phone }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif(!empty($customerSearch))
                                <div
                                    class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg p-3 text-sm text-gray-500">
                                    No customers found. <a href="{{ route('customers.create') }}"
                                        class="text-blue-600 hover:underline">Add new customer</a>
                                </div>
                            @endif
                        </div>
                    @endif
                    @error('customer_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b border-gray-100 pb-2">2. Order Items</h3>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end mb-6">
                    <div class="md:col-span-7 flex flex-col gap-1.5">
                        <label for="product_id" class="text-sm font-medium text-gray-700">Product</label>
                        <select id="product_id" wire:model="product_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors bg-white">
                            <option value="">Select a product...</option>
                            @foreach(App\Models\Product::orderBy('name')->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (₱{{ number_format($p->price, 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1.5">
                        <label for="quantity" class="text-sm font-medium text-gray-700">Quantity</label>
                        <input type="number" id="quantity" wire:model="quantity" min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors" />
                    </div>

                    <div class="md:col-span-3">
                        <button type="button" wire:click="addToCart"
                            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Add to Order
                        </button>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-md overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 font-medium">Item</th>
                                <th class="px-4 py-3 font-medium">Price</th>
                                <th class="px-4 py-3 font-medium">Qty</th>
                                <th class="px-4 py-3 font-medium text-right">Subtotal</th>
                                <th class="px-4 py-3 font-medium text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($cart as $index => $item)
                                <tr class="bg-white">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">₱{{ number_format($item['price'], 2) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        ₱{{ number_format($item['subtotal'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" wire:click="removeFromCart({{ $index }})"
                                            class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No items added to this order
                                        yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($cart))
                            <tfoot class="bg-gray-50 border-t border-gray-200">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-semibold text-gray-900">Total Due:</td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-600 text-base">
                                        ₱{{ number_format($this->cartTotal, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                @error('cart') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
            </div>

            <div
                class="pt-4 mt-2 border-t border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">

                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Payment Method:</label>
                    <select wire:model="payment_method"
                        class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors bg-white">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <div class="flex gap-3 w-full md:w-auto">
                    <button type="button" wire:click="$refresh"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors w-full md:w-auto">
                        Clear
                    </button>

                    <button type="button" wire:click="saveOrder"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors disabled:opacity-50 flex items-center justify-center w-full md:w-auto">
                        <span wire:loading.remove wire:target="saveOrder">Confirm Order</span>
                        <span wire:loading wire:target="saveOrder">Saving...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>