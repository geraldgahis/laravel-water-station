<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updateStatus(Order $order, $newStatus)
    {
        $order->update(['status' => $newStatus]);
        session()->flash('success', "Order #{$order->id} marked as " . ucfirst($newStatus));
    }

    // NEW: The Cancel Method with Auto-Restock
    public function cancelOrder(Order $order)
    {
        // Prevent cancelling orders that are already done
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return;
        }

        DB::transaction(function () use ($order) {
            // 1. Change the status
            $order->update(['status' => 'cancelled']);

            // 2. Return the stock back to the inventory
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_count', $item->quantity);
                }
            }

            // Note: If you want to handle refunds in the future, you would update the Payment status here too.
        });

        session()->flash('success', "Order #{$order->id} has been cancelled and stock has been returned.");
    }

    public function with(): array
    {
        // Eager load items as well so the cancel method can quickly access them
        $query = Order::with(['customer', 'payment', 'items'])->latest();

        if ($this->search) {
            $query->whereHas('customer', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return [
            'orders' => $query->paginate(10)
        ];
    }
};
?>

<div>
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Order Management</h1>
            <p class="text-sm text-gray-500 mt-1">Track deliveries, update statuses, and view transaction history.</p>
        </div>

        <a href="{{ route('orders.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-colors shadow-sm">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create New Order
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
        <div class="p-4 border-b border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row gap-4">

            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Search by customer name or phone..."
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 sm:text-sm transition-colors" />
            </div>

            <div class="w-full sm:w-48">
                <select wire:model.live="statusFilter"
                    class="block w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600 sm:text-sm transition-colors">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="dispatched">Dispatched</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">Order ID</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Amount & Method</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Date</th>
                        <th class="px-6 py-3 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $order->customer->name }}</div>
                                <div class="text-gray-500 text-xs mt-0.5">{{ $order->customer->phone }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">
                                    ₱{{ number_format(optional($order->payment)->amount ?? 0, 2) }}</div>
                                <div class="text-gray-500 text-xs mt-0.5 uppercase">
                                    {{ optional($order->payment)->method ?? 'Unpaid' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($order->status === 'pending')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                @elseif($order->status === 'dispatched')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Dispatched</span>
                                @elseif($order->status === 'delivered')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Delivered</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                {{ $order->created_at->format('M d, Y h:i A') }}
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                @if($order->status !== 'cancelled')
                                    <a href="{{ route('orders.edit', $order->id) }}"
                                        class="text-gray-600 hover:text-blue-800 font-medium text-xs bg-gray-50 px-2.5 py-1.5 rounded-md border border-gray-200 mr-2 transition-colors">
                                        Edit
                                    </a>
                                @endif

                                @if($order->status === 'pending')
                                    <button wire:click="updateStatus({{ $order->id }}, 'dispatched')"
                                        class="text-blue-600 hover:text-blue-800 font-medium text-xs bg-blue-50 px-2 py-1 rounded border border-blue-200 mr-1">
                                        Dispatch
                                    </button>
                                    <button wire:click="cancelOrder({{ $order->id }})"
                                        wire:confirm="Are you sure you want to cancel this order? The stock will be returned."
                                        class="text-red-600 hover:text-red-800 font-medium text-xs bg-red-50 px-2 py-1 rounded border border-red-200">
                                        Cancel
                                    </button>
                                @elseif($order->status === 'dispatched')
                                    <button wire:click="updateStatus({{ $order->id }}, 'delivered')"
                                        class="text-green-600 hover:text-green-800 font-medium text-xs bg-green-50 px-2 py-1 rounded border border-green-200 mr-1">
                                        Mark Delivered
                                    </button>
                                    <button wire:click="cancelOrder({{ $order->id }})"
                                        wire:confirm="Are you sure you want to cancel this order? The stock will be returned."
                                        class="text-red-600 hover:text-red-800 font-medium text-xs bg-red-50 px-2 py-1 rounded border border-red-200">
                                        Cancel
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs italic">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No orders found. Ready for the next delivery!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 bg-white">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>