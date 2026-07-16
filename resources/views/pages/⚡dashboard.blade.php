<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\Customer;
use App\Models\sale as Sale;
use App\Models\sale_item as SaleItem;
use Carbon\Carbon;

new class extends Component
{
    public $stats = [];
    public $recentSales = [];
    public $lowStockProducts = [];
    public $topProducts = [];
    public $salesChart = [];
    public $recentCustomers = [];
    public $pendingOrders = [];
    public $name = "Asad";
    public $dateRange = 'week';
    
    public function mount()
    {
        $this->loadDashboardData();
    }
    
    public function loadDashboardData()
    {
        // Get real stats from database
        $this->stats = [
            'total_products' => Product::count(),
            'total_categories' => 0,
            'total_suppliers' => 18, // Replace with your supplier model
            'total_sales' => Sale::count(),
            'total_revenue' => Sale::sum('total'),
            'low_stock' => Product::where('quantity', '<=', 2)->count(),
            'today_sales' => Sale::whereDate('created_at', Carbon::today())->count(),
            'today_revenue' => Sale::whereDate('created_at', Carbon::today())->sum('total'),
            'pending_sales' => Sale::where('status', 'pending')->count(),
        ];
        
        // Recent Sales (Last 5)
        $this->recentSales = Sale::with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'customer' => $sale->customer->name ?? 'Walk-in Customer',
                    'amount' => $sale->total,
                    'date' => $sale->created_at->format('Y-m-d'),
                    'status' => $sale->status,
                    'payment_status' => $sale->payment_status,
                ];
            })
            ->toArray();
        
        // Low Stock Products
        $this->lowStockProducts = Product::where('quantity', '<=', 2)
    ->orderBy('quantity', 'asc')
    ->take(5)
    ->get()
    ->map(function($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'stock' => $product->quantity,
            'min_stock' => $product->min_stock ?? 10,
            'category' => $product->category ?? 'Uncategorized',
        ];
    })
    ->toArray();
        
        // Top Selling Products
        $this->topProducts = SaleItem::select('product_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->product->name ?? 'Unknown',
                    'quantity' => $item->total_quantity,
                    'revenue' => $item->product ? $item->product->price * $item->total_quantity : 0,
                ];
            })
            ->toArray();
        
        // Recent Customers
        $this->recentCustomers = Customer::latest()
            ->take(5)
            ->get()
            ->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'total_orders' => $customer->sales_count,
                    'joined' => $customer->created_at->diffForHumans(),
                ];
            })
            ->toArray();
        
        // Pending Orders
        $this->pendingOrders = Sale::where('status', 'pending')
            ->with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'customer' => $sale->customer->name ?? 'Walk-in Customer',
                    'amount' => $sale->total,
                    'date' => $sale->created_at->format('Y-m-d'),
                ];
            })
            ->toArray();
        
        // Sales Chart Data (Last 7 Days)
        $this->salesChart = $this->getSalesChartData();
    }
    
    private function getSalesChartData()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dailyTotal = Sale::whereDate('created_at', $date)->sum('total');
            $data[] = [
                'date' => $date->format('D'),
                'sales' => $dailyTotal,
                'count' => Sale::whereDate('created_at', $date)->count(),
            ];
        }
        return $data;
    }
    
    public function changeDateRange($range)
    {
        $this->dateRange = $range;
        $this->loadDashboardData();
    }
}

?>
<div>
    <style>
        .stat-card {
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .table-custom {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .table-custom thead {
            background: #f8fafc;
        }
        
        .badge-stock {
            background: #dcfce7;
            color: #166534;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-low-stock {
            background: #fee2e2;
            color: #991b1b;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .quick-actions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
        }
        
        .chart-bar {
            transition: height 0.5s ease;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 20px;
            padding: 30px;
        }
        
        .greeting-text {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .date-range-btn {
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .date-range-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .date-range-btn:hover:not(.active) {
            background: #f0f0f0;
        }
    </style>

    <!-- Dashboard Header -->
    <div class="dashboard-header text-white mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h3 class="fw-bold mb-1">
                    <i class="bi bi-house-door me-2"></i> Welcome back, {{ $name }}! 👋
                </h3>
                <p class="opacity-75 mb-0">
                    {{ \Carbon\Carbon::now()->format('h:i A') }} | 
                    <span class="badge bg-light text-dark ms-2">
                        <i class="bi bi-clock"></i> {{ \Carbon\Carbon::now()->format('h:i A') }}
                    </span>
                </p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button class="btn btn-light me-2" onclick="window.location.href='{{ route('sales.create') }}'">
                    <i class="bi bi-plus-circle"></i> New Sale
                </button>
                <button class="btn btn-outline-light" onclick="window.location.href='{{ route('products.create') }}'">
                    <i class="bi bi-box-plus"></i> Add Product
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Revenue</h6>
                            <h2 class="mb-0 fw-bold">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</h2>
                        </div>
                        <i class="bi bi-currency-dollar stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-arrow-up"></i> Today: ${{ number_format($stats['today_revenue'] ?? 0, 2) }}
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Sales</h6>
                            <h2 class="mb-0 fw-bold">{{ number_format($stats['total_sales'] ?? 0) }}</h2>
                        </div>
                        <i class="bi bi-cart-check stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-arrow-up"></i> Today: {{ $stats['today_sales'] ?? 0 }} orders
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Products</h6>
                            <h2 class="mb-0 fw-bold">{{ number_format($stats['total_products'] ?? 0) }}</h2>
                        </div>
                        <i class="bi bi-box-seam stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-tag"></i> {{ $stats['total_categories'] ?? 0 }} categories
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Low Stock Alert</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['low_stock'] ?? 0 }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-clock"></i> {{ $stats['pending_sales'] ?? 0 }} pending orders
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="card table-custom mb-4">
        <div class="card-header bg-white border-0 pt-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-graph-up-arrow me-2 text-primary"></i> Sales Overview
                </h5>
                <div class="btn-group mt-2 mt-md-0">
                    <button class="btn btn-sm date-range-btn {{ $dateRange == 'week' ? 'active' : 'btn-outline-secondary' }}" 
                            wire:click="changeDateRange('week')">Week</button>
                    <button class="btn btn-sm date-range-btn {{ $dateRange == 'month' ? 'active' : 'btn-outline-secondary' }}" 
                            wire:click="changeDateRange('month')">Month</button>
                    <button class="btn btn-sm date-range-btn {{ $dateRange == 'year' ? 'active' : 'btn-outline-secondary' }}" 
                            wire:click="changeDateRange('year')">Year</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row align-items-end" style="height: 200px;">
                @foreach($salesChart as $day)
                <div class="col text-center">
                    <div class="text-muted small">{{ $day['date'] }}</div>
                    <div class="chart-bar bg-primary" 
                         style="height: {{ ($day['sales'] / max(array_column($salesChart, 'sales')) * 100) ?: 5 }}%; 
                                width: 60%; 
                                margin: 0 auto;
                                border-radius: 5px 5px 0 0;
                                transition: height 0.5s ease;
                                min-height: 5px;">
                    </div>
                    <div class="small fw-bold mt-1">${{ number_format($day['sales'], 2) }}</div>
                    <div class="text-muted small">{{ $day['count'] }} orders</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row g-4">
        <!-- Recent Sales -->
        <div class="col-xl-7">
            <div class="card table-custom">
                <div class="card-header bg-white border-0 pt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-clock-history me-2"></i> Recent Sales
                        </h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td><span class="fw-bold">#{{ $sale['invoice_no'] ?? $sale['id'] }}</span></td>
                                    <td>{{ $sale['customer'] }}</td>
                                    <td>${{ number_format($sale['amount'], 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($sale['date'])->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $sale['status'] == 'completed' ? 'success' : ($sale['status'] == 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($sale['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sale['payment_status'] == 'paid' ? 'success' : ($sale['payment_status'] == 'partial' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($sale['payment_status']) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        No recent sales found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock & Pending Orders -->
        <div class="col-xl-5">
            <!-- Low Stock Products -->
            <div class="card table-custom mb-4">
                <div class="card-header bg-white border-0 pt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-exclamation-diamond me-2 text-warning"></i> Low Stock Alert
                        </h5>
                        <a href="#" class="btn btn-sm btn-outline-warning">
                            Manage <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($lowStockProducts as $product)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <span class="fw-bold">{{ $product['name'] }}</span>
                            <br>
                            <small class="text-muted">{{ $product['category'] }}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge-low-stock">
                                {{ $product['stock'] }} / {{ $product['min_stock'] }}
                            </span>
                            <br>
                            <button class="btn btn-sm btn-primary mt-1" 
                                    onclick="window.location.href='{{ route('products.edit', $product['id']) }}'">
                                <i class="bi bi-plus-circle"></i> Restock
                            </button>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-muted py-3">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                        All products are well stocked!
                    </p>
                    @endforelse
                </div>
            </div>

            <!-- Pending Orders -->
            <div class="card table-custom">
                <div class="card-header bg-white border-0 pt-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">
                            <i class="bi bi-clock me-2 text-warning"></i> Pending Orders
                        </h5>
                        <a href="#" class="btn btn-sm btn-outline-warning">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($pendingOrders as $order)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <span class="fw-bold">#{{ $order['invoice_no'] }}</span>
                            <br>
                            <small class="text-muted">{{ $order['customer'] }}</small>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold">${{ number_format($order['amount'], 2) }}</span>
                            <br>
                            <small class="text-muted">{{ Carbon\Carbon::parse($order['date'])->diffForHumans() }}</small>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-muted py-3">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                        No pending orders
                    </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Footer -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 quick-actions text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-2">
                                <i class="bi bi-lightning-fill me-2"></i> Quick Actions
                            </h5>
                            <p class="mb-0 opacity-75">Manage your business efficiently with these quick actions</p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <button class="btn btn-light me-2" onclick="window.location.href='{{ route('sales.create') }}'">
                                <i class="bi bi-cart-plus"></i> New Sale
                            </button>
                            <button class="btn btn-outline-light me-2" onclick="window.location.href='{{ route('products.create') }}'">
                                <i class="bi bi-plus-circle"></i> Add Product
                            </button>
                            <button class="btn btn-outline-light" onclick="window.location.href='{{ route('reports.sales') }}'">
                                <i class="bi bi-file-text"></i> Reports
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>