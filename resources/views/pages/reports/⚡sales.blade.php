<?php

use Livewire\Component;
use App\Models\sale as Sale;
use App\Models\sale_item as SaleItem;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

new class extends Component
{
    // Report Filters
    public $reportType = 'monthly'; // daily, weekly, monthly, yearly, custom
    public $dateFrom;
    public $dateTo;
    public $customerFilter = '';
    public $paymentFilter = '';
    public $statusFilter = '';
    public $categoryFilter = '';
    
    // Report Data
    public $sales = [];
    public $summary = [];
    public $topProducts = [];
    public $topCustomers = [];
    public $chartData = [];
    public $customers = [];
    public $categories = [];
    
    // Statistics
    public $totalSales = 0;
    public $totalRevenue = 0;
    public $totalPaid = 0;
    public $totalDue = 0;
    public $averageSale = 0;
    public $totalItems = 0;
    
    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->customers = Customer::all();
        $this->loadReport();
    }
    
    public function loadReport()
    {
        // Build base query
        $query = Sale::with(['customer', 'items.product'])
                     ->whereBetween('sale_date', [$this->dateFrom, $this->dateTo]);
        
        // Apply filters
        if ($this->customerFilter) {
            $query->where('customer_id', $this->customerFilter);
        }
        
        if ($this->paymentFilter) {
            $query->where('payment_status', $this->paymentFilter);
        }
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        // Get sales
        $this->sales = $query->orderBy('sale_date', 'desc')->get();
        
        // Calculate summary
        $this->calculateSummary();
        
        // Get top products
        $this->getTopProducts();
        
        // Get top customers
        $this->getTopCustomers();
        
        // Get chart data
        $this->getChartData();
    }
    
    private function calculateSummary()
    {
        $this->totalSales = $this->sales->count();
        $this->totalRevenue = $this->sales->sum('total');
        $this->totalPaid = $this->sales->sum('paid_amount');
        $this->totalDue = $this->sales->sum('due_amount');
        $this->averageSale = $this->totalSales > 0 ? $this->totalRevenue / $this->totalSales : 0;
        $this->totalItems = $this->sales->sum(function($sale) {
            return $sale->items->sum('quantity');
        });
        
        // Summary by status
        $this->summary = [
            'pending' => $this->sales->where('status', 'pending')->count(),
            'completed' => $this->sales->where('status', 'completed')->count(),
            'cancelled' => $this->sales->where('status', 'cancelled')->count(),
            'paid' => $this->sales->where('payment_status', 'paid')->count(),
            'partial' => $this->sales->where('payment_status', 'partial')->count(),
            'unpaid' => $this->sales->where('payment_status', 'unpaid')->count(),
        ];
    }
    
    private function getTopProducts()
    {
        $this->topProducts = SaleItem::selectRaw('
            product_id,
            SUM(quantity) as total_quantity,
            SUM(total) as total_revenue
        ')
        ->whereHas('sale', function($query) {
            $query->whereBetween('sale_date', [$this->dateFrom, $this->dateTo])
                  ->where('status', 'completed');
        })
        ->with('product')
        ->groupBy('product_id')
        ->orderBy('total_revenue', 'desc')
        ->limit(10)
        ->get();
    }
    
    private function getTopCustomers()
    {
        $this->topCustomers = Sale::selectRaw('
            customer_id,
            COUNT(*) as total_sales,
            SUM(total) as total_revenue,
            SUM(paid_amount) as total_paid,
            SUM(due_amount) as total_due
        ')
        ->whereBetween('sale_date', [$this->dateFrom, $this->dateTo])
        ->where('status', 'completed')
        ->with('customer')
        ->groupBy('customer_id')
        ->orderBy('total_revenue', 'desc')
        ->limit(10)
        ->get();
    }
    
    private function getChartData()
    {
        $start = Carbon::parse($this->dateFrom);
        $end = Carbon::parse($this->dateTo);
        $days = $start->diffInDays($end);
        
        if ($days <= 31) {
            // Daily data
            $this->chartData = Sale::selectRaw('
                DATE(sale_date) as date,
                COUNT(*) as count,
                SUM(total) as revenue
            ')
            ->whereBetween('sale_date', [$this->dateFrom, $this->dateTo])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        } else {
            // Monthly data
            $this->chartData = Sale::selectRaw('
                DATE_FORMAT(sale_date, "%Y-%m") as date,
                COUNT(*) as count,
                SUM(total) as revenue
            ')
            ->whereBetween('sale_date', [$this->dateFrom, $this->dateTo])
            ->where('status', 'completed')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
        }
    }
    
    public function setReportType($type)
    {
        $this->reportType = $type;
        
        switch ($type) {
            case 'daily':
                $this->dateFrom = Carbon::now()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'weekly':
                $this->dateFrom = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'monthly':
                $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'yearly':
                $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->dateTo = Carbon::now()->format('Y-m-d');
                break;
            case 'custom':
                // Keep current dates
                break;
        }
        
        $this->loadReport();
    }
    
    public function updatedDateFrom()
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->loadReport();
        }
    }
    
    public function updatedDateTo()
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->loadReport();
        }
    }
    
    public function updatedCustomerFilter()
    {
        $this->loadReport();
    }
    
    public function updatedPaymentFilter()
    {
        $this->loadReport();
    }
    
    public function updatedStatusFilter()
    {
        $this->loadReport();
    }
    
    public function clearFilters()
    {
        $this->customerFilter = '';
        $this->paymentFilter = '';
        $this->statusFilter = '';
        $this->loadReport();
    }
    
    public function getStatusBadgeClass($status)
    {
        return match($status) {
            'completed' => 'success',
            'pending' => 'warning',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
    
    public function getPaymentBadgeClass($status)
    {
        return match($status) {
            'paid' => 'success',
            'partial' => 'warning',
            'unpaid' => 'danger',
            default => 'secondary'
        };
    }
    
    public function formatCurrency($amount)
    {
        return '$' . number_format($amount, 2);
    }
    
    public function exportReport()
    {
        // Implement export functionality
        session()->flash('message', 'Report export will be available soon!');
    }
    
    public function render()
    {
        return <<<'BLADE'
        <div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        @if(session()->has('message'))
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <i class="bi bi-graph-up-arrow"></i> Sales Report
                            </h2>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" wire:click="loadReport">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                                <button type="button" class="btn btn-success" wire:click="exportReport">
                                    <i class="bi bi-file-spreadsheet"></i> Export Report
                                </button>
                            </div>
                        </div>
                        
                        <!-- Date Range Selector -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Report Period</label>
                                        <select class="form-control" wire:model.live="reportType" wire:change="setReportType($event.target.value)">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="yearly">Yearly</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Date From</label>
                                        <input type="date" class="form-control" wire:model.live="dateFrom">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Date To</label>
                                        <input type="date" class="form-control" wire:model.live="dateTo">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Customer</label>
                                        <select class="form-control" wire:model.live="customerFilter">
                                            <option value="">All Customers</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Status</label>
                                        <select class="form-control" wire:model.live="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="completed">Completed</option>
                                            <option value="pending">Pending</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Payment</label>
                                        <select class="form-control" wire:model.live="paymentFilter">
                                            <option value="">All Payment</option>
                                            <option value="paid">Paid</option>
                                            <option value="partial">Partial</option>
                                            <option value="unpaid">Unpaid</option>
                                        </select>
                                    </div>
                                    <div class="col-md-10 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-secondary me-2" wire:click="clearFilters">
                                            <i class="bi bi-eraser"></i> Clear Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Sales</h6>
                                        <h3 class="mb-0">{{ $totalSales }}</h3>
                                        <small>{{ $this->formatCurrency($totalRevenue) }} Revenue</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Paid</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($totalPaid) }}</h3>
                                        <small>{{ $totalSales > 0 ? round(($totalPaid / $totalRevenue) * 100, 1) : 0 }}% Collection Rate</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Due</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($totalDue) }}</h3>
                                        <small>{{ $totalSales > 0 ? round(($totalDue / $totalRevenue) * 100, 1) : 0 }}% Outstanding</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Average Sale</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($averageSale) }}</h3>
                                        <small>{{ $totalItems }} Items Sold</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Chart -->
                        @if($chartData->count() > 0)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-bar-chart-line"></i> Sales Trend</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ $reportType == 'daily' ? 'Date' : 'Month' }}</th>
                                                <th class="text-end">Sales Count</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">% of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $maxRevenue = $chartData->max('revenue') ?: 1;
                                            @endphp
                                            @foreach($chartData as $data)
                                            <tr>
                                                <td>
                                                    <strong>{{ $data->date }}</strong>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-success" 
                                                             role="progressbar" 
                                                             style="width: {{ ($data->revenue / $maxRevenue) * 100 }}%;">
                                                            {{ $this->formatCurrency($data->revenue) }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">{{ $data->count }}</td>
                                                <td class="text-end">{{ $this->formatCurrency($data->revenue) }}</td>
                                                <td class="text-end">{{ $totalRevenue > 0 ? round(($data->revenue / $totalRevenue) * 100, 1) : 0 }}%</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Summary Stats -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong><i class="bi bi-pie-chart"></i> Status Summary</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="d-flex justify-content-between">
                                                    <span>Completed:</span>
                                                    <span class="badge bg-success">{{ $summary['completed'] ?? 0 }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <span>Pending:</span>
                                                    <span class="badge bg-warning">{{ $summary['pending'] ?? 0 }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <span>Cancelled:</span>
                                                    <span class="badge bg-danger">{{ $summary['cancelled'] ?? 0 }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex justify-content-between">
                                                    <span>Paid:</span>
                                                    <span class="badge bg-success">{{ $summary['paid'] ?? 0 }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <span>Partial:</span>
                                                    <span class="badge bg-warning">{{ $summary['partial'] ?? 0 }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mt-2">
                                                    <span>Unpaid:</span>
                                                    <span class="badge bg-danger">{{ $summary['unpaid'] ?? 0 }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong><i class="bi bi-trophy"></i> Quick Stats</strong>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <h5>Total Revenue</h5>
                                                    <h3 class="text-success">{{ $this->formatCurrency($totalRevenue) }}</h3>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <h5>Total Due</h5>
                                                    <h3 class="text-danger">{{ $this->formatCurrency($totalDue) }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Products -->
                        @if($topProducts->count() > 0)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-star"></i> Top Products</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th class="text-end">Quantity Sold</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">% of Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProducts as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $item->product->sku ?? '' }}</small>
                                                </td>
                                                <td class="text-end">{{ number_format($item->total_quantity) }}</td>
                                                <td class="text-end">{{ $this->formatCurrency($item->total_revenue) }}</td>
                                                <td class="text-end">{{ $totalRevenue > 0 ? round(($item->total_revenue / $totalRevenue) * 100, 1) : 0 }}%</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Top Customers -->
                        @if($topCustomers->count() > 0)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-people"></i> Top Customers</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Customer</th>
                                                <th class="text-end">Sales</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">Paid</th>
                                                <th class="text-end">Due</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topCustomers as $index => $customer)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $customer->customer->name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $customer->customer->email ?? '' }}</small>
                                                </td>
                                                <td class="text-end">{{ $customer->total_sales }}</td>
                                                <td class="text-end">{{ $this->formatCurrency($customer->total_revenue) }}</td>
                                                <td class="text-end text-success">{{ $this->formatCurrency($customer->total_paid) }}</td>
                                                <td class="text-end text-danger">{{ $this->formatCurrency($customer->total_due) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Sales List -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-list-ul"></i> Sales List ({{ $totalSales }} Transactions)</strong>
                            </div>
                            <div class="card-body">
                                @if($sales->isEmpty())
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3 text-muted">No sales found for the selected period.</p>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Invoice No</th>
                                                    <th>Customer</th>
                                                    <th>Date</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-end">Paid</th>
                                                    <th class="text-end">Due</th>
                                                    <th>Status</th>
                                                    <th>Payment</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sales as $sale)
                                                <tr class="{{ $sale->status == 'pending' ? 'table-warning' : ($sale->status == 'cancelled' ? 'table-danger' : '') }}">
                                                    <td>
                                                        <strong>{{ $sale->invoice_no }}</strong>
                                                    </td>
                                                    <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                                                    <td class="text-end fw-bold">{{ $this->formatCurrency($sale->total) }}</td>
                                                    <td class="text-end text-success">{{ $this->formatCurrency($sale->paid_amount) }}</td>
                                                    <td class="text-end text-danger">{{ $this->formatCurrency($sale->due_amount) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $this->getStatusBadgeClass($sale->status) }}">
                                                            {{ ucfirst($sale->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $this->getPaymentBadgeClass($sale->payment_status) }}">
                                                            {{ ucfirst($sale->payment_status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('sales.show', $sale->id) }}" 
                                                           class="btn btn-sm btn-info text-white">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-dark">
                                                <tr>
                                                    <th colspan="3" class="text-end">Totals:</th>
                                                    <th class="text-end fw-bold">{{ $this->formatCurrency($totalRevenue) }}</th>
                                                    <th class="text-end fw-bold text-success">{{ $this->formatCurrency($totalPaid) }}</th>
                                                    <th class="text-end fw-bold text-danger">{{ $this->formatCurrency($totalDue) }}</th>
                                                    <th colspan="3"></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        BLADE;
    }
}
?>