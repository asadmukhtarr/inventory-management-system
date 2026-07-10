<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use Carbon\Carbon;

new class extends Component
{
    // Report Filters
    public $reportType = 'current'; // current, movement, valuation
    public $categoryFilter = '';
    public $stockFilter = '';
    public $dateFrom;
    public $dateTo;
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    
    // Report Data
    public $products = [];
    public $stockMovements = [];
    public $summary = [];
    public $categories = [];
    
    // Statistics
    public $totalProducts = 0;
    public $totalStock = 0;
    public $totalValue = 0;
    public $lowStockCount = 0;
    public $outOfStockCount = 0;
    public $totalSalesQuantity = 0;
    public $totalPurchaseQuantity = 0;
    
    public function mount()
    {
        $this->dateFrom = Carbon::now()->subMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->loadReport();
    }
    
    public function loadReport()
    {
        switch ($this->reportType) {
            case 'current':
                $this->loadCurrentStock();
                break;
            case 'movement':
                $this->loadStockMovement();
                break;
            case 'valuation':
                $this->loadStockValuation();
                break;
            default:
                $this->loadCurrentStock();
        }
        
        $this->calculateStatistics();
    }
    
    private function loadCurrentStock()
    {
        $query = Product::query();
        
        // Apply search/filters
        if ($this->stockFilter == 'low') {
            $query->where('stock', '<=', 10)->where('stock', '>', 0);
        } elseif ($this->stockFilter == 'out') {
            $query->where('stock', 0);
        } elseif ($this->stockFilter == 'in') {
            $query->where('stock', '>', 0);
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
            'low_stock' => $this->products->where('stock', '<=', 10)->where('stock', '>', 0)->count(),
            'out_of_stock' => $this->products->where('stock', 0)->count(),
        ];
    }
    
    private function loadStockMovement()
    {
        // Get sales data
        $salesData = SaleItem::selectRaw('
            product_id,
            SUM(quantity) as total_sold,
            SUM(total) as total_revenue
        ')
        ->whereHas('sale', function($query) {
            $query->whereBetween('sale_date', [$this->dateFrom, $this->dateTo])
                  ->where('status', 'completed');
        })
        ->with('product')
        ->groupBy('product_id')
        ->get();
        
        // Get purchase data (if you have purchases table)
        // $purchaseData = PurchaseItem::selectRaw('
        //     product_id,
        //     SUM(quantity) as total_purchased
        // ')
        // ->whereHas('purchase', function($query) {
        //     $query->whereBetween('purchase_date', [$this->dateFrom, $this->dateTo]);
        // })
        // ->with('product')
        // ->groupBy('product_id')
        // ->get();
        
        // Combine data
        $this->stockMovements = $salesData->map(function($item) {
            return (object) [
                'product' => $item->product,
                'product_id' => $item->product_id,
                'total_sold' => $item->total_sold,
                'total_revenue' => $item->total_revenue,
                'current_stock' => $item->product->stock ?? 0,
                // 'total_purchased' => 0, // Uncomment when purchases table exists
            ];
        });
        
        $this->totalSalesQuantity = $salesData->sum('total_sold');
        // $this->totalPurchaseQuantity = $purchaseData->sum('total_purchased');
    }
    
    private function loadStockValuation()
    {
        $this->products = Product::orderBy('stock', 'desc')->get();
        
        // Calculate valuation summary
        $this->summary = [
            'total_products' => $this->products->count(),
            'total_stock' => $this->products->sum('stock'),
            'total_purchase_value' => $this->products->sum(function($product) {
                return $product->stock * ($product->purchase_price ?? 0);
            }),
            'total_sale_value' => $this->products->sum(function($product) {
                return $product->stock * ($product->sale_price ?? 0);
            }),
            'potential_profit' => $this->products->sum(function($product) {
                return $product->stock * (($product->sale_price ?? 0) - ($product->purchase_price ?? 0));
            }),
        ];
    }
    
    private function calculateStatistics()
    {
        if ($this->reportType == 'current' || $this->reportType == 'valuation') {
            $this->totalProducts = $this->products->count();
            $this->totalStock = $this->products->sum('stock');
            $this->totalValue = $this->products->sum(function($product) {
                return $product->stock * ($product->purchase_price ?? 0);
            });
            $this->lowStockCount = $this->products->where('stock', '<=', 10)->where('stock', '>', 0)->count();
            $this->outOfStockCount = $this->products->where('stock', 0)->count();
        }
    }
    
    public function setReportType($type)
    {
        $this->reportType = $type;
        $this->loadReport();
    }
    
    public function updatedStockFilter()
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
    
    public function clearFilters()
    {
        $this->stockFilter = '';
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
    
    public function getStockBadgeClass($stock)
    {
        if ($stock <= 0) {
            return 'danger';
        } elseif ($stock <= 10) {
            return 'warning';
        } else {
            return 'success';
        }
    }
    
    public function getStockStatus($stock)
    {
        if ($stock <= 0) {
            return 'Out of Stock';
        } elseif ($stock <= 10) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
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
                                <i class="bi bi-box-seam"></i> Stock Report
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
                                            <option value="current">Current Stock</option>
                                            <option value="movement">Stock Movement</option>
                                            <option value="valuation">Stock Valuation</option>
                                        </select>
                                    </div>
                                    @if($reportType == 'movement')
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Date From</label>
                                        <input type="date" class="form-control" wire:model.live="dateFrom">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Date To</label>
                                        <input type="date" class="form-control" wire:model.live="dateTo">
                                    </div>
                                    @endif
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Stock Status</label>
                                        <select class="form-control" wire:model.live="stockFilter">
                                            <option value="">All Stock</option>
                                            <option value="in">In Stock</option>
                                            <option value="low">Low Stock (≤ 10)</option>
                                            <option value="out">Out of Stock</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                                            <i class="bi bi-eraser"></i> Clear Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary Cards -->
                        @if($reportType == 'current' || $reportType == 'valuation')
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Products</h6>
                                        <h3 class="mb-0">{{ $totalProducts }}</h3>
                                        <small>Active Products</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Stock</h6>
                                        <h3 class="mb-0">{{ number_format($totalStock) }}</h3>
                                        <small>Units in Inventory</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Value</h6>
                                        <h3 class="mb-0">{{ $this->formatCurrency($totalValue) }}</h3>
                                        <small>At Purchase Price</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body">
                                        <h6 class="mb-0">Low/Out of Stock</h6>
                                        <h3 class="mb-0">{{ $lowStockCount + $outOfStockCount }}</h3>
                                        <small>{{ $lowStockCount }} Low, {{ $outOfStockCount }} Out</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($reportType == 'valuation')
                        <!-- Valuation Summary -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-calculator"></i> Valuation Summary</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h6>Purchase Value</h6>
                                            <h3 class="text-primary">{{ $this->formatCurrency($summary['total_purchase_value'] ?? 0) }}</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h6>Sale Value</h6>
                                            <h3 class="text-success">{{ $this->formatCurrency($summary['total_sale_value'] ?? 0) }}</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h6>Potential Profit</h6>
                                            <h3 class="text-warning">{{ $this->formatCurrency($summary['potential_profit'] ?? 0) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($reportType == 'movement')
                        <!-- Movement Summary -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-arrows-move"></i> Stock Movement Summary</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h6>Period</h6>
                                            <h5>{{ $dateFrom }} to {{ $dateTo }}</h5>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h6>Total Sold</h6>
                                            <h3 class="text-danger">{{ number_format($totalSalesQuantity) }} Units</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <h6>Products Moved</h6>
                                            <h3 class="text-primary">{{ $stockMovements->count() }}</h3>
                                        </div>
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
                                    @if($reportType == 'current')
                                        Current Stock Details
                                    @elseif($reportType == 'movement')
                                        Stock Movement Details
                                    @else
                                        Stock Valuation Details
                                    @endif
                                </strong>
                            </div>
                            <div class="card-body">
                                @if($reportType == 'current' || $reportType == 'valuation')
                                    @if($products->isEmpty())
                                        <div class="text-center py-5">
                                            <i class="bi bi-box" style="font-size: 3rem;"></i>
                                            <p class="mt-3 text-muted">No products found matching your criteria.</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th wire:click="sortBy('image')" style="cursor: pointer;">Image</th>
                                                        <th wire:click="sortBy('name')" style="cursor: pointer;">
                                                            Product
                                                            @if($sortBy == 'name')
                                                                <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                            @endif
                                                        </th>
                                                        <th wire:click="sortBy('sku')" style="cursor: pointer;">
                                                            SKU
                                                            @if($sortBy == 'sku')
                                                                <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                            @endif
                                                        </th>
                                                        <th wire:click="sortBy('stock')" style="cursor: pointer;" class="text-end">
                                                            Stock
                                                            @if($sortBy == 'stock')
                                                                <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                            @endif
                                                        </th>
                                                        <th class="text-end">Purchase Price</th>
                                                        <th class="text-end">Sale Price</th>
                                                        @if($reportType == 'valuation')
                                                        <th class="text-end">Stock Value</th>
                                                        <th class="text-end">Sale Value</th>
                                                        <th class="text-end">Potential Profit</th>
                                                        @else
                                                        <th class="text-end">Stock Value</th>
                                                        @endif
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($products as $index => $product)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            @if($product->image)
                                                                <img src="{{ asset('storage/' . $product->image) }}" 
                                                                     alt="{{ $product->name }}" 
                                                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                                            @else
                                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                                     style="width: 40px; height: 40px; border-radius: 5px;">
                                                                    <i class="bi bi-image text-muted"></i>
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <strong>{{ $product->name }}</strong>
                                                            @if($product->description)
                                                                <br>
                                                                <small class="text-muted">{{ Str::limit($product->description, 40) }}</small>
                                                            @endif
                                                        </td>
                                                        <td><code>{{ $product->sku }}</code></td>
                                                        <td class="text-end fw-bold">
                                                            {{ number_format($product->stock) }}
                                                        </td>
                                                        <td class="text-end">{{ $this->formatCurrency($product->purchase_price ?? 0) }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency($product->sale_price ?? 0) }}</td>
                                                        @if($reportType == 'valuation')
                                                        <td class="text-end">{{ $this->formatCurrency(($product->stock ?? 0) * ($product->purchase_price ?? 0)) }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency(($product->stock ?? 0) * ($product->sale_price ?? 0)) }}</td>
                                                        <td class="text-end text-success">
                                                            {{ $this->formatCurrency(($product->stock ?? 0) * (($product->sale_price ?? 0) - ($product->purchase_price ?? 0))) }}
                                                        </td>
                                                        @else
                                                        <td class="text-end">{{ $this->formatCurrency(($product->stock ?? 0) * ($product->purchase_price ?? 0)) }}</td>
                                                        @endif
                                                        <td>
                                                            <span class="badge bg-{{ $this->getStockBadgeClass($product->stock) }}">
                                                                {{ $this->getStockStatus($product->stock) }}
                                                            </span>
                                                            @if($product->stock <= 10 && $product->stock > 0)
                                                                <br>
                                                                <small class="text-warning">Reorder soon!</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="{{ route('products.show', $product->id) }}" 
                                                                   class="btn btn-info text-white" title="View">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <a href="{{ route('products.edit', $product->id) }}" 
                                                                   class="btn btn-warning" title="Edit">
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
                                                        <th class="text-end fw-bold">{{ number_format($totalStock) }}</th>
                                                        <th colspan="2"></th>
                                                        @if($reportType == 'valuation')
                                                        <th class="text-end fw-bold">{{ $this->formatCurrency($summary['total_purchase_value'] ?? 0) }}</th>
                                                        <th class="text-end fw-bold">{{ $this->formatCurrency($summary['total_sale_value'] ?? 0) }}</th>
                                                        <th class="text-end fw-bold text-success">{{ $this->formatCurrency($summary['potential_profit'] ?? 0) }}</th>
                                                        @else
                                                        <th class="text-end fw-bold">{{ $this->formatCurrency($totalValue) }}</th>
                                                        @endif
                                                        <th colspan="2"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @endif
                                    
                                @elseif($reportType == 'movement')
                                    @if($stockMovements->isEmpty())
                                        <div class="text-center py-5">
                                            <i class="bi bi-arrows-move" style="font-size: 3rem;"></i>
                                            <p class="mt-3 text-muted">No stock movements found for the selected period.</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Product</th>
                                                        <th>SKU</th>
                                                        <th class="text-end">Quantity Sold</th>
                                                        <th class="text-end">Revenue</th>
                                                        <th class="text-end">Current Stock</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($stockMovements as $index => $item)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                        </td>
                                                        <td><code>{{ $item->product->sku ?? '' }}</code></td>
                                                        <td class="text-end">{{ number_format($item->total_sold) }}</td>
                                                        <td class="text-end">{{ $this->formatCurrency($item->total_revenue) }}</td>
                                                        <td class="text-end">{{ number_format($item->current_stock ?? 0) }}</td>
                                                        <td>
                                                            <span class="badge bg-{{ $this->getStockBadgeClass($item->current_stock ?? 0) }}">
                                                                {{ $this->getStockStatus($item->current_stock ?? 0) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="table-dark">
                                                    <tr>
                                                        <th colspan="3" class="text-end">Totals:</th>
                                                        <th class="text-end fw-bold">{{ number_format($totalSalesQuantity) }}</th>
                                                        <th class="text-end fw-bold">{{ $this->formatCurrency($stockMovements->sum('total_revenue')) }}</th>
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