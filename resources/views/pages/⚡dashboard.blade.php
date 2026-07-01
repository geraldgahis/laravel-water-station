<?php

use Livewire\Component;
use Illuminate\Support\Carbon;
use App\Models\{Order, Payment, Product, Customer};

new class extends Component {

    public function with(): array
    {
        $today = Carbon::today();

        // 1. Live Metrics
        $todaySales = Payment::whereDate('paid_at', $today)
            ->where('payment_status', 'paid')
            ->whereHas('order', function($q) { $q->where('status', '!=', 'cancelled'); }) // <-- Add this
            ->sum('amount');

        $pendingOrders = Order::where('status', 'pending')->count();

        $deliveredToday = Order::where('status', 'delivered')->whereDate('updated_at', $today)->count();

        $totalCustomers = Customer::count();

        // 2. Low Stock Alerts
        $lowStockProducts = Product::where('stock_count', '<=', 15)->orderBy('stock_count', 'asc')->get();

        // 3. Real recent orders feed
        $recentOrders = Order::with([
            'customer', 'payment'
        ])->latest()->take(5)->get();

        // 4. Dynamic Sales Chart Data
        $chartData = collect(range(6,0))->map(function ($daysBack) {
            $date = Carbon::today()->subDays($daysBack);
            $sales = Payment::whereDate('paid_at', $date)
                ->where('payment_status', 'paid')
                ->whereHas('order', function($q) { $q->where('status', '!=', 'cancelled'); }) // <-- Add this
                ->sum('amount');

            return [
                'day' => $date->format('D'),
                'date' => $date->format('M d'),
                'sales' => $sales
            ];
        });

        // 5. Calculate Max Sales to scale the CSS chart
        $maxSales = max(1, $chartData->max('sales'));
        
        return compact('todaySales', 'pendingOrders', 'deliveredToday', 'totalCustomers', 'lowStockProducts', 'recentOrders', 'chartData', 'maxSales');
    }
};
?>

<div class=mx-auto pb-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">Dashboard Overview</h1>
            <p class="text-sm text-gray-500 mt-1">Live business summary for {{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('orders.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 shadow-sm transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Order
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-500">Today's Income</span>
                <span class="p-2 bg-green-50 text-green-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <span class="text-3xl font-black text-gray-900 tracking-tight">₱{{ number_format($todaySales, 2) }}</span>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-500">Pending Deliveries</span>
                <span class="p-2 bg-yellow-50 text-yellow-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </span>
            </div>
            <span class="text-3xl font-black text-gray-900 tracking-tight">{{ $pendingOrders }}</span>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-500">Completed Today</span>
                <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </span>
            </div>
            <span class="text-3xl font-black text-gray-900 tracking-tight">{{ $deliveredToday }}</span>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <span class="text-sm font-medium text-gray-500">Total Customers</span>
                <span class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
            </div>
            <span class="text-3xl font-black text-gray-900 tracking-tight">{{ number_format($totalCustomers) }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 flex flex-col gap-8">
            
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                    <h2 class="text-lg font-bold text-gray-900">7-Day Sales Trend</h2>
                    <span class="text-sm font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-full">Live Data</span>
                </div>
                
                <div class="flex items-end justify-between h-56 gap-2 sm:gap-4 px-2 pt-6">
                    @foreach($chartData as $data)
                        @php 
                            // Calculate percentage height, cap at 100%
                            $heightPercent = ($data['sales'] / $maxSales) * 100;
                        @endphp
                        <div class="flex flex-col items-center flex-1 gap-2 h-full justify-end group">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-xs font-bold px-2 py-1 rounded shadow-lg whitespace-nowrap mb-1">
                                ₱{{ number_format($data['sales'], 2) }}
                            </div>
                            
                            <div class="w-full bg-blue-100 hover:bg-blue-500 rounded-t-md transition-colors relative cursor-pointer flex flex-col justify-end" 
                                 style="height: {{ max(5, $heightPercent) }}%">
                                @if($data['sales'] == $maxSales && $maxSales > 0)
                                    <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-8 h-1 bg-blue-600 rounded-full"></div>
                                @endif
                            </div>
                            
                            <div class="text-center">
                                <span class="block text-xs font-bold text-gray-700">{{ $data['day'] }}</span>
                                <span class="block text-[10px] text-gray-400">{{ $data['date'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-center bg-gray-50/50">
                    <h2 class="text-lg font-bold text-gray-900">Recent Activity</h2>
                    <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">View All Orders &rarr;</a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-white border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 font-semibold tracking-wider">Customer</th>
                                <th class="px-6 py-3 font-semibold tracking-wider">Status</th>
                                <th class="px-6 py-3 font-semibold tracking-wider">Amount</th>
                                <th class="px-6 py-3 font-semibold tracking-wider text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $order->customer->name }}</div>
                                        <div class="text-gray-500 text-xs mt-0.5">{{ $order->customer->phone }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($order->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-200">Pending</span>
                                        @elseif($order->status === 'dispatched')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">Dispatched</span>
                                        @elseif($order->status === 'delivered')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-100 text-green-800 border border-green-200">Delivered</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-800 border border-red-200">Cancelled</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        @if($order->status === 'cancelled')
                                            <span class="line-through text-gray-400">₱{{ number_format(optional($order->payment)->amount ?? 0, 2) }}</span>
                                        @else
                                            ₱{{ number_format(optional($order->payment)->amount ?? 0, 2) }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500 text-xs font-medium">
                                        {{ $order->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500 bg-gray-50/50">
                                        No recent orders found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-8">
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-gray-200 bg-red-50/50 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Low Stock Alerts
                    </h2>
                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full">{{ count($lowStockProducts) }} Items</span>
                </div>
                
                <div class="p-0">
                    @if(count($lowStockProducts) > 0)
                        <ul class="divide-y divide-gray-100">
                            @foreach($lowStockProducts as $product)
                                <li class="px-5 py-3 flex justify-between items-center hover:bg-gray-50 transition-colors">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 line-clamp-1">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">ID: {{ $product->id }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-red-600 font-black text-lg">{{ $product->stock_count }}</span>
                                        <span class="text-xs text-gray-500 block -mt-1">Left</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="p-6 text-center">
                            <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm font-medium text-gray-900">Inventory is healthy!</p>
                            <p class="text-xs text-gray-500 mt-1">No items are running low on stock.</p>
                        </div>
                    @endif
                </div>
                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50">
                    <a href="{{ route('products.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wider block text-center">Manage Inventory</a>
                </div>
            </div>

            <div class="bg-blue-600 rounded-xl shadow-sm p-6 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-20 h-20 bg-white opacity-10 rounded-full blur-xl"></div>
                
                <h3 class="text-lg font-bold mb-2 relative z-10">Need to update prices?</h3>
                <p class="text-blue-100 text-sm mb-4 relative z-10">Ensure your catalog pricing is up to date before logging new orders.</p>
                <a href="{{ route('products.index') }}" class="inline-block bg-white text-blue-600 font-bold text-sm px-6 py-2 rounded-lg shadow-sm hover:bg-gray-50 transition-colors relative z-10">
                    Go to Products
                </a>
            </div>

        </div>
    </div>
</div>