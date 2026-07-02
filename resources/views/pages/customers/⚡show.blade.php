<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;
use Illuminate\Support\Carbon;

new class extends Component {
    use WithPagination;

    public Customer $customer;

    // Filter orders
    public string $dateRange = 'all';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(Customer $customer)
    {
        $this->customer = $customer;
    }

    // Automatically set dates when the dropdown changes
    public function updatedDateRange()
    {
        if ($this->dateRange === 'today') {
            $this->startDate = today()->format('Y-m-d');
            $this->endDate = today()->format('Y-m-d');
        } elseif ($this->dateRange === 'week') {
            $this->startDate = now()->startOfWeek()->format('Y-m-d');
            $this->endDate = now()->endOfWeek()->format('Y-m-d');
        } elseif ($this->dateRange === 'month') {
            $this->startDate = now()->startOfMonth()->format('Y-m-d');
            $this->endDate = now()->endOfMonth()->format('Y-m-d');
        } elseif ($this->dateRange === 'all') {
            $this->startDate = '';
            $this->endDate = '';
        }
        $this->resetPage(); // Reset pagination when filter changes

    }

    public function with(): array
    {
        // 1. Build the filtered order query
        $query = $this->customer->orders()->with(['payment', 'items']);

        if ($this->dateRange !== 'all' && $this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // 2. Calculate All-Time Lifetime Value (Ignores the date filter)
        $lifetimeOrders = $this->customer->orders()->where('status', '!=', 'cancelled')->count();
        $lifetimeSpent = $this->customer->orders()
            ->where('status', '!=', 'cancelled')
            ->join('payments', 'orders.id', '=', 'payments.order_id')
            ->sum('payments.amount');

        return [
            'orders' => $query->latest()->paginate(10),
            'lifetimeOrders' => $lifetimeOrders,
            'lifetimeSpent' => $lifetimeSpent,
        ];
    }
};
?>

<div class="mx-auto pb-8">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Customer Profile</h1>
            <p class="text-sm text-gray-500 mt-1">View client details, lifetime value, and transaction history.</p>
        </div>

        <a href="{{ route('customers.index') }}"
            class="text-sm font-medium text-gray-600 hover:text-gray-900 bg-white px-4 py-2 rounded-md border border-gray-200 shadow-sm transition-colors">
            &larr; Back to List
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-3">

            <div class="p-6 lg:col-span-2 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ $customer->name }}</h2>
                            <div class="flex items-center gap-2 mt-1 text-gray-600 font-medium">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                    </path>
                                </svg>
                                {{ $customer->phone }}
                            </div>
                        </div>
                        <a href="{{ route('customers.edit', $customer->id) }}"
                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md font-medium text-sm text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                            <svg class="w-4 h-4 mr-1.5 text-gray-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                </path>
                            </svg>
                            Edit
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Delivery
                                Address</span>
                            <p
                                class="text-sm text-gray-900 bg-gray-50 p-3 rounded-md border border-gray-100 min-h-[60px]">
                                {{ $customer->address }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Delivery
                                Notes</span>
                            @if($customer->notes)
                                <p
                                    class="text-sm text-gray-900 bg-yellow-50 p-3 rounded-md border border-yellow-100 min-h-[60px]">
                                    {{ $customer->notes }}</p>
                            @else
                                <p
                                    class="text-sm text-gray-400 bg-gray-50 p-3 rounded-md border border-gray-100 italic min-h-[60px]">
                                    No special instructions.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border-t lg:border-t-0 lg:border-l border-gray-200 p-6 flex flex-col justify-center">
                <h3
                    class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-6 text-center border-b border-gray-200 pb-2">
                    Lifetime Value</h3>

                <div class="space-y-6">
                    <div class="text-center">
                        <span class="block text-sm font-medium text-gray-500 mb-1">Total Revenue</span>
                        <span
                            class="text-3xl font-black text-blue-600 tracking-tight">₱{{ number_format($lifetimeSpent, 2) }}</span>
                    </div>

                    <div class="text-center">
                        <span class="block text-sm font-medium text-gray-500 mb-1">Successful Orders</span>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($lifetimeOrders) }}</span>
                    </div>

                    <div class="text-center pt-2">
                        <span class="block text-xs text-gray-400">Customer since
                            {{ $customer->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">

        <div
            class="p-5 border-b border-gray-200 bg-gray-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Transaction History</h3>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <span class="text-xs font-bold text-gray-500 uppercase">Filter:</span>
                    <select wire:model.live="dateRange"
                        class="flex-1 md:w-36 px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white shadow-sm">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                @if($dateRange === 'custom')
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <input type="date" wire:model.live="startDate"
                            class="flex-1 md:w-auto px-2 py-1.5 border border-gray-300 rounded-md text-sm shadow-sm" />
                        <span class="text-gray-400 text-sm">to</span>
                        <input type="date" wire:model.live="endDate"
                            class="flex-1 md:w-auto px-2 py-1.5 border border-gray-300 rounded-md text-sm shadow-sm" />
                    </div>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 font-semibold tracking-wider">Order ID</th>
                        <th class="px-6 py-3 font-semibold tracking-wider">Date</th>
                        <th class="px-6 py-3 font-semibold tracking-wider">Status</th>
                        <th class="px-6 py-3 font-semibold tracking-wider">Items</th>
                        <th class="px-6 py-3 font-semibold tracking-wider text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-900">
                                #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-medium">
                                {{ $order->created_at->format('M d, Y') }}
                                <span
                                    class="block text-xs text-gray-400 font-normal">{{ $order->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($order->status === 'pending')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">Pending</span>
                                @elseif($order->status === 'dispatched')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">Dispatched</span>
                                @elseif($order->status === 'delivered')
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-100 text-green-800 border border-green-200">Delivered</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-800 border border-red-200">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-medium">
                                {{ $order->items->sum('quantity') }} items
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">
                                @if($order->status === 'cancelled')
                                    <span
                                        class="line-through text-gray-400">₱{{ number_format(optional($order->payment)->amount ?? 0, 2) }}</span>
                                @else
                                    ₱{{ number_format(optional($order->payment)->amount ?? 0, 2) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 bg-gray-50/50">
                                No transactions found for the selected date range.
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