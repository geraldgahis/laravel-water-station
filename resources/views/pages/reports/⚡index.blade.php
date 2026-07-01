<?php

use Livewire\Component;
use App\Models\{Order, Payment, OrderItem};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $dateRange = 'today';
    public string $startDate = '';
    public string $endDate = '';

    public function mount()
    {
        $this->startDate = today()->format('Y-m-d');
        $this->endDate = today()->format('Y-m-d');
    }

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
        }
    }

    public function with(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 1. UPDATED Financial Queries (Ignores cancelled orders!)
        $paymentsQuery = Payment::whereBetween('paid_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->whereHas('order', function ($query) {
                // This is the magic line that filters out cancelled orders
                $query->where('status', '!=', 'cancelled');
            });

        $totalSales = (clone $paymentsQuery)->sum('amount');
        $cashSales = (clone $paymentsQuery)->where('method', 'cash')->sum('amount');
        $gcashSales = (clone $paymentsQuery)->where('method', 'gcash')->sum('amount');

        // 2. Order Volume Queries
        $ordersQuery = Order::whereBetween('created_at', [$start, $end]);

        $totalOrders = (clone $ordersQuery)->count();
        $deliveredOrders = (clone $ordersQuery)->where('status', 'delivered')->count();
        $cancelledOrders = (clone $ordersQuery)->where('status', 'cancelled')->count();

        // 3. Product Performance
        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_quantity'), DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->get();

        return compact(
            'totalSales',
            'cashSales',
            'gcashSales',
            'totalOrders',
            'deliveredOrders',
            'cancelledOrders',
            'topProducts',
            'start',
            'end'
        );
    }
};
?>

<div class="mx-auto pb-8">

    <style>
        @media print {

            /* Hide the sidebar completely */
            .no-print {
                display: none !important;
            }

            /* Force the main layout to behave as a single column */
            .flex,
            .grid {
                display: block !important;
            }

            /* Reset width constraints */
            .max-w-6xl {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Ensure content fills the entire paper */
            .print-only-container {
                width: 100% !important;
                display: block !important;
            }
        }
    </style>

    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 no-print">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Financial Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Generate sales and inventory reports.</p>
        </div>
        <button onclick="window.print()"
            class="px-4 py-2 bg-gray-900 text-white text-sm font-bold rounded-lg hover:bg-black shadow-sm transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                </path>
            </svg>
            Print Report
        </button>
    </div>

    <div
        class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mb-6 no-print flex flex-col md:flex-row gap-4 items-end">
        <div class="w-full md:w-48 flex flex-col gap-1.5">
            <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Date Range</label>
            <select wire:model.live="dateRange"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 bg-white">
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>

        @if($dateRange === 'custom')
            <div class="w-full md:w-40 flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Start Date</label>
                <input type="date" wire:model.live="startDate"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" />
            </div>
            <div class="w-full md:w-40 flex flex-col gap-1.5">
                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">End Date</label>
                <input type="date" wire:model.live="endDate"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" />
            </div>
        @endif
    </div>

    <div class="hidden print-only mb-6 text-center">
        <h2 class="text-2xl font-black text-gray-900">Daloy POS - Sales Report</h2>
        <p class="text-gray-600">Period: {{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }}</p>
        <p class="text-xs text-gray-400 mt-1">Generated on {{ now()->format('M d, Y h:i A') }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm font-medium text-gray-500">Total Sales</span>
            <div class="text-3xl font-black text-blue-600 mt-1 tracking-tight">₱{{ number_format($totalSales, 2) }}
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm font-medium text-gray-500">Cash Received</span>
            <div class="text-3xl font-bold text-gray-900 mt-1 tracking-tight">₱{{ number_format($cashSales, 2) }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm font-medium text-gray-500">GCash Received</span>
            <div class="text-3xl font-bold text-gray-900 mt-1 tracking-tight">₱{{ number_format($gcashSales, 2) }}</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-sm font-medium text-gray-500">Total Orders</span>
            <div class="text-3xl font-bold text-gray-900 mt-1 tracking-tight">{{ number_format($totalOrders) }}</div>
            <p class="text-xs text-gray-500 mt-1">{{ $deliveredOrders }} Delivered • {{ $cancelledOrders }} Cancelled
            </p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
            <h2 class="text-lg font-bold text-gray-900">Product Sales Breakdown</h2>
            <p class="text-sm text-gray-500">Showing items sold between {{ $start->format('M d') }} and
                {{ $end->format('M d, Y') }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 font-semibold tracking-wider">Product Name</th>
                        <th class="px-6 py-3 font-semibold tracking-wider text-right">Qty Sold</th>
                        <th class="px-6 py-3 font-semibold tracking-wider text-right">Revenue Generated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProducts as $product)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $product->name }}</td>
                            <td class="px-6 py-4 text-right font-medium text-gray-600">
                                {{ number_format($product->total_quantity) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">
                                ₱{{ number_format($product->total_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center text-gray-500">
                                No products sold during this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($topProducts) > 0)
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900">Grand Total:</td>
                            <td class="px-6 py-3 text-right font-bold text-gray-900">
                                {{ number_format($topProducts->sum('total_quantity')) }}
                            </td>
                            <td class="px-6 py-3 text-right font-black text-blue-600 text-base">
                                ₱{{ number_format($topProducts->sum('total_revenue'), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>