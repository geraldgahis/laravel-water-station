<?php

use Livewire\Component;
use Illuminate\Support\Carbon;

new class extends Component {

    public function with(): array
    {
        // 1. Mocking the Recent Orders with local Filipino names
        $recentOrders = collect([
            (object) [
                'customer' => (object) ['name' => 'Juan Dela Cruz', 'phone' => '0917-123-4567'],
                'status' => 'pending',
                'payment' => (object) ['amount' => 150.00], // e.g., 3 refills
                'created_at' => now()->subMinutes(12)
            ],
            (object) [
                'customer' => (object) ['name' => 'Maria Santos', 'phone' => '0918-987-6543'],
                'status' => 'dispatched',
                'payment' => (object) ['amount' => 450.00],
                'created_at' => now()->subMinutes(45)
            ],
            (object) [
                'customer' => (object) ['name' => 'Aling Nena (Sari-Sari)', 'phone' => '0922-333-4444'],
                'status' => 'delivered',
                'payment' => (object) ['amount' => 1200.00], // Wholesale order
                'created_at' => now()->subHours(2)
            ],
            (object) [
                'customer' => (object) ['name' => 'Pedro Penduko', 'phone' => '0999-888-7777'],
                'status' => 'pending',
                'payment' => null, // Represents an unpaid/COD order where payment hasn't been logged
                'created_at' => now()->subHours(3)
            ],
            (object) [
                'customer' => (object) ['name' => 'Lito Lapid', 'phone' => '0919-444-5555'],
                'status' => 'delivered',
                'payment' => (object) ['amount' => 200.00],
                'created_at' => now()->subHours(5)
            ],
        ]);

        return [
            // 2. Hardcoded Top Metrics
            'todaySales' => 4250.00,
            'pendingOrders' => 8,
            'deliveredToday' => 24,
            'recentOrders' => $recentOrders,
        ];
    }
};
?>

<div>
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Overview</h1>
            <p class="text-sm text-gray-500 mt-1">Here is what is happening at Daloy POS today.</p>
        </div>
        <div class="text-sm font-medium text-gray-600 bg-white px-4 py-2 rounded-md border border-gray-200 shadow-sm">
            {{ now()->format('l, F j, Y') }}
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm flex flex-col">
            <span class="text-sm font-medium text-gray-500 mb-1">Today's Income</span>
            <span class="text-3xl font-bold text-gray-900">₱{{ number_format($todaySales, 2) }}</span>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm flex flex-col">
            <span class="text-sm font-medium text-gray-500 mb-1">Pending Deliveries</span>
            <span class="text-3xl font-bold text-blue-600">{{ $pendingOrders }}</span>
        </div>

        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm flex flex-col">
            <span class="text-sm font-medium text-gray-500 mb-1">Completed Today</span>
            <span class="text-3xl font-bold text-gray-900">{{ $deliveredToday }}</span>
        </div>

    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
            <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-700">View all</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Amount</th>
                        <th class="px-6 py-3 font-medium text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $order->customer->name }}</div>
                                <div class="text-gray-500 text-xs mt-0.5">{{ $order->customer->phone }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($order->status === 'pending')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                @elseif($order->status === 'dispatched')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Dispatched</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Delivered</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                ₱{{ number_format(optional($order->payment)->amount ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-gray-500">
                                {{ $order->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No orders yet today. Waiting for the first customer!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>