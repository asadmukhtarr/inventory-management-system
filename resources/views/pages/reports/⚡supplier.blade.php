<?php

use Livewire\Component;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Carbon\Carbon;

new class extends Component
{
    // Report Filters
    public $reportType = 'summary'; // summary, purchases, products
    public $supplierFilter = '';
    public $dateFrom;
    public $dateTo;
    public $statusFilter = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    
    // Report Data
    public $suppliers = [];
    public $supplierDetails = [];
    public $purchases = [];
    public $products = [];
    public $summary = [];
    public $allSuppliers = [];
    
    // Statistics
    public $totalSuppliers = 0;
    public $totalPurchases = 0;
    public $totalSpent = 0;
    public $totalProducts = 0;
    public $averageOrderValue = 0;
    public $topSupplier = null;
    
    public function mount()
    {
        $this->dateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->allSuppliers = Supplier::all();
        $this->loadReport();
    }
    
    public function loadReport()
    {
        switch ($this->reportType) {
            case 'summary':
                $this->loadSupplierSummary();
                break;
            case 'purchases':
                $this->loadPurchaseHistory();
                break;
            case 'products':
                $this->loadSupplierProducts();
                break;
            default:
                $this->loadSupplierSummary();
        }
        
        $this->calculateStatistics();
    }
    
    private function loadSupplierSummary()
    {
        $query = Supplier::with(['product']);
        
        // Apply supplier filter
        if ($this->supplierFilter) {
            $query->where('id', $this->supplierFilter);
        }
        
        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        $this->suppliers = $query->get();
        
        // Calculate summary for each supplier
        $this->supplierDetails = $this->suppliers->map(function($supplier) {
            // Get purchases (if you have purchases table)
            $purchases = collect(); // Placeholder - adjust when purchases table exists
            
            // Get products count
            $productCount = $supplier->product->count();
            
            return (object) [
                'supplier' => $supplier,
                'total_purchases' => $purchases->count(),
                'total_spent' => $purchases->sum('total') ?? 0,
                'total_products' => $productCount,
                'average_order' => $purchases->count() > 0 ? $purchases->sum('total') / $purchases->count() : 0,
                'last_purchase' => $purchases->max('purchase_date'),
                'balance' => $supplier->balance ?? 0,
            ];
        });
    }
    
    private function loadPurchaseHistory()
    {
        // If you have purchases table, load from there
        // For now, we'll show a message
        $this->purchases = collect();
        $this->summary = [
            'total_purchases' => 0,
            'total_spent' => 0,
            'total_items' => 0,
            'average_order' => 0,
        ];
        
        // Uncomment when purchases table exists:
        /*
        $query = Purchase::with(['supplier', 'items'])
            ->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
        
        if ($this->supplierFilter) {
            $query->where('supplier_id', $this->supplierFilter);
        }
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        $query->orderBy('purchase_date', 'desc');
        
        $this->purchases = $query->get();
        
        $this->summary = [
            'total_purchases' => $this->purchases->count(),
            'total_spent' => $this->purchases->sum('total'),
            'total_items' => $this->purchases->sum(function($purchase) {
                return $purchase->items->sum('quantity');
            }),
            'average_order' => $this->purchases->count() > 0 ? $this->purchases->sum('total') / $this->purchases->count() : 0,
        ];
        */
    }
    
    private function loadSupplierProducts()
    {
        $query = Product::with(['supplier']);
        
        // Apply supplier filter
        if ($this->supplierFilter) {
            $query->where('supplier_id', $this->supplierFilter);
        }
        
        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        $this->products = $query->get();
        
        // Calculate summary
        $this->summary = [
            'total_products' => $this->products->count(),
            'total_stock' => $this->products->sum('stock'),
            'total_value' => $this->products->sum(function($product) {
                return $product->stock * ($product->purchase_price ?? 0);
            }),
        ];
    }
    
    private function calculateStatistics()
    {
        if ($this->reportType == 'summary') {
            $this->totalSuppliers = $this->suppliers->count();
            $this->totalPurchases = $this->supplierDetails->sum('total_purchases');
            $this->totalSpent = $this->supplierDetails->sum('total_spent');
            $this->totalProducts = $this->supplierDetails->sum('total_products');
            $this->averageOrderValue = $this->totalPurchases > 0 ? $this->totalSpent / $this->totalPurchases : 0;
            
            // Find top supplier by spend
            $this->topSupplier = $this->supplierDetails->sortByDesc('total_spent')->first();
        }
    }
    
    public function setReportType($type)
    {
        $this->reportType = $type;
        $this->loadReport();
    }
    
    public function updatedSupplierFilter()
    {
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
    
    public function updatedStatusFilter()
    {
        $this->loadReport();
    }
    
    public function clearFilters()
    {
        $this->supplierFilter = '';
        $this->statusFilter = '';
        $this->dateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->loadReport();
    }
    
    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadReport();
    }
    
    public function getStatusBadgeClass($status)
    {
        return match($status) {
            'active' => 'success',
            'inactive' => 'secondary',
            'pending' => 'warning',
            'blocked' => 'danger',
            default => 'secondary'
        };
    }
    
    public function formatCurrency($amount)
    {
        return '$' . number_format($amount, 2);
    }
    
    public function exportReport()
    {
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
                                <i class="bi bi-truck"></i> Supplier Report
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
                        
                        <!-- Report Type Selector -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Report Type</label>
                                        <select class="form-control" wire:model.live="reportType" wire:change="setReportType($event.target.value)">
                                            <option value="summary">Supplier Summary</option>
                                            <option value="purchases">Purchase History</option>
                                            <option value="products">Supplier Products</option>
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
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Supplier</label>
                                        <select class="form-control" wire:model.live="supplierFilter">
                                            <option value="">All Suppliers</option>
                                            @foreach($allSuppliers as $supplier)
                                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    @if($reportType == 'summary')
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Supplier Status</label>
                                        <select class="form-control" wire:model.live="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="blocked">Blocked</option>
                                        </select>
                                    </div>
                                    @endif
                                    <div class="col-md-{{ $reportType == 'summary' ? '9' : '9' }} d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-secondary me-2" wire:click="clearFilters">
                                            <i class="bi bi-eraser"></i> Clear Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary Cards -->
                        @if($reportType == 'summary')
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Suppliers</h6>
                                        <h3 class="mb-0">{{ $totalSuppliers }}</h3>
                                        <small>Active Suppliers</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Products</h6>
                                        <h3 class="mb-0">{{ $totalProducts }}</h3>
                                        <small>Products Supplied</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Spent</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($totalSpent) }}</h3>
                                        <small>Total Purchase Amount</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body">
                                        <h6 class="mb-0">Average Order</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($averageOrderValue) }}</h3>
                                        <small>Per Purchase</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Supplier -->
                        @if($topSupplier)
                        <div class="card mb-4 bg-light">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5><i class="bi bi-trophy text-warning"></i> Top Supplier</h5>
                                        <h3>{{ $topSupplier->supplier->name ?? 'N/A' }}</h3>
                                        <p class="mb-0">
                                            <span class="badge bg-success">{{ $topSupplier->total_purchases }} Purchases</span>
                                            <span class="badge bg-info ms-2">{{ $this->formatCurrency($topSupplier->total_spent) }} Total Spent</span>
                                            <span class="badge bg-secondary ms-2">{{ $topSupplier->total_products }} Products</span>
                                            @if($topSupplier->balance > 0)
                                                <span class="badge bg-warning ms-2">Balance: {{ $this->formatCurrency($topSupplier->balance) }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="display-4 text-success">{{ $this->formatCurrency($topSupplier->total_spent) }}</div>
                                        <small>Total Spend</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endif
                        
                        @if($reportType == 'purchases')
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Note:</strong> Purchase history will be available when the purchases module is integrated.
                        </div>
                        @endif
                        
                        @if($reportType == 'products')
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Products</h6>
                                        <h3 class="mb-0">{{ $summary['total_products'] ?? 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Stock</h6>
                                        <h3 class="mb-0">{{ number_format($summary['total_stock'] ?? 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Value</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($summary['total_value'] ?? 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Report Table -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <strong>
                                    <i class="bi bi-table"></i> 
                                    @if($reportType == 'summary')
                                        Supplier Summary Details
                                    @elseif($reportType == 'purchases')
                                        Purchase History
                                    @else
                                        Supplier Products
                                    @endif
                                </strong>
                            </div>
                            <div class="card-body">
                                @if($reportType == 'summary')
                                    @if($supplierDetails->isEmpty())
                                        <div class="text-center py-5">
                                            <i class="bi bi-truck" style="font-size: 3rem;"></i>
                                            <p class="mt-3 text-muted">No suppliers found matching your criteria.</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th wire:click="sortBy('name')" style="cursor: pointer;">
                                                            Supplier
                                                            @if($sortBy == 'name')
                                                                <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                            @endif
                                                        </th>
                                                        <th>Contact</th>
                                                        <th>Email</th>
                                                        <th>Phone</th>
                                                        <th>Company</th>
                                                        <th class="text-end">Products</th>
                                                        <th class="text-end">Balance</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($supplierDetails as $index => $detail)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <strong>{{ $detail->supplier->name }}</strong>
                                                        </td>
                                                        <td>{{ $detail->supplier->contact_person ?? 'N/A' }}</td>
                                                        <td>{{ $detail->supplier->email ?? 'N/A' }}</td>
                                                        <td>{{ $detail->supplier->phone ?? 'N/A' }}</td>
                                                        <td>{{ $detail->supplier->company_name ?? 'N/A' }}</td>
                                                        <td class="text-end">{{ $detail->total_products }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency($detail->balance) }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $this->getStatusBadgeClass($detail->supplier->status) }}">
                                                                {{ ucfirst($detail->supplier->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                -
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-dark">
                                                    <tr>
                                                        <th colspan="6" class="text-end">Totals:</th>
                                                        <th class="text-end fw-bold">{{ $totalProducts }}</th>
                                                        <th class="text-end fw-bold">{{ $this->formatCurrency($supplierDetails->sum('balance')) }}</th>
                                                        <th colspan="2"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @endif
                                    
                                @elseif($reportType == 'purchases')
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> 
                                        <strong>Coming Soon!</strong> Purchase history will be available when the purchases module is fully integrated.
                                    </div>
                                    
                                @elseif($reportType == 'products')
                                    @if($products->isEmpty())
                                        <div class="text-center py-5">
                                            <i class="bi bi-box" style="font-size: 3rem;"></i>
                                            <p class="mt-3 text-muted">No products found for the selected supplier.</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Product</th>
                                                        <th>SKU</th>
                                                        <th>Supplier</th>
                                                        <th class="text-end">Stock</th>
                                                        <th class="text-end">Purchase Price</th>
                                                        <th class="text-end">Sale Price</th>
                                                        <th class="text-end">Stock Value</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($products as $index => $product)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <strong>{{ $product->name }}</strong>
                                                            @if($product->description)
                                                                <br>
                                                                <small class="text-muted">{{ Str::limit($product->description, 40) }}</small>
                                                            @endif
                                                        </td>
                                                        <td><code>{{ $product->sku }}</code></td>
                                                        <td>{{ $product->supplier->name ?? 'N/A' }}</td>
                                                        <td class="text-end">{{ number_format($product->stock) }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency($product->purchase_price ?? 0) }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency($product->sale_price ?? 0) }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency(($product->stock ?? 0) * ($product->purchase_price ?? 0)) }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $product->stock <= 0 ? 'danger' : ($product->stock <= 10 ? 'warning' : 'success') }}">
                                                                {{ $product->stock <= 0 ? 'Out of Stock' : ($product->stock <= 10 ? 'Low Stock' : 'In Stock') }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="{{ route('products.show', $product->id) }}" 
                                                                   class="btn btn-info text-white">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <a href="{{ route('products.edit', $product->id) }}" 
                                                                   class="btn btn-warning">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-dark">
                                                    <tr>
                                                        <th colspan="4" class="text-end">Totals:</th>
                                                        <th class="text-end fw-bold">{{ number_format($summary['total_stock'] ?? 0) }}</th>
                                                        <th colspan="3" class="text-end fw-bold">{{ $this->formatCurrency($summary['total_value'] ?? 0) }}</th>
                                                        <th colspan="2"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @endif
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