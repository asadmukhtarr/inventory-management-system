<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\PurchaseItem;

new class extends Component
{
    public $products = [];
    public $searchTerm = '';
    public $stockFilter = '';
    public $sortBy = 'name';
    public $sortDirection = 'asc';
    
    // Statistics
    public $totalProducts = 0;
    public $totalStock = 0;
    public $lowStockCount = 0;
    public $outOfStockCount = 0;
    public $totalValue = 0;
    
    public function mount()
    {
        $this->loadProducts();
        $this->calculateStatistics();
    }
    
    public function loadProducts()
    {
        $query = Product::query();
        
        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('sku', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        // Apply stock filter
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
    }
    
    private function calculateStatistics()
    {
        $this->totalProducts = $this->products->count();
        $this->totalStock = $this->products->sum('stock');
        $this->lowStockCount = $this->products->where('stock', '<=', 10)->where('stock', '>', 0)->count();
        $this->outOfStockCount = $this->products->where('stock', 0)->count();
        $this->totalValue = $this->products->sum(function($product) {
            return $product->stock * ($product->purchase_price ?? 0);
        });
    }
    
    public function filterByStock($filter)
    {
        $this->stockFilter = $filter;
        $this->loadProducts();
        $this->calculateStatistics();
    }
    
    public function updatedSearchTerm()
    {
        $this->loadProducts();
        $this->calculateStatistics();
    }
    
    public function clearFilters()
    {
        $this->searchTerm = '';
        $this->stockFilter = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->loadProducts();
        $this->calculateStatistics();
    }
    
    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadProducts();
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
    
    public function render()
    {
        return <<<'BLADE'
        <div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>
                                <i class="bi bi-box-seam"></i> Stock Details
                            </h2>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" wire:click="loadProducts">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                                <a href="{{ route('products.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add Product
                                </a>
                            </div>
                        </div>
                        
                        <!-- Statistics Cards -->
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
                                        <small>{{ $this->formatCurrency($totalValue) }} Total Value</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body">
                                        <h6 class="mb-0">Low Stock</h6>
                                        <h3 class="mb-0">{{ $lowStockCount }}</h3>
                                        <small>Items with stock ≤ 10</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Out of Stock</h6>
                                        <h3 class="mb-0">{{ $outOfStockCount }}</h3>
                                        <small>Items with zero stock</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Filter Buttons -->
                        <div class="mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-outline-secondary {{ $stockFilter == '' ? 'active' : '' }}"
                                        wire:click="filterByStock('')">
                                    All Stock
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-success {{ $stockFilter == 'in' ? 'active' : '' }}"
                                        wire:click="filterByStock('in')">
                                    <i class="bi bi-check-circle"></i> In Stock
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-warning {{ $stockFilter == 'low' ? 'active' : '' }}"
                                        wire:click="filterByStock('low')">
                                    <i class="bi bi-exclamation-triangle"></i> Low Stock
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-danger {{ $stockFilter == 'out' ? 'active' : '' }}"
                                        wire:click="filterByStock('out')">
                                    <i class="bi bi-x-circle"></i> Out of Stock
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   placeholder="Search by Name, SKU, or Description..."
                                                   wire:model.live="searchTerm">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control" wire:model.live="stockFilter">
                                            <option value="">All Stock Status</option>
                                            <option value="in">In Stock</option>
                                            <option value="low">Low Stock (≤ 10)</option>
                                            <option value="out">Out of Stock</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                                            <i class="bi bi-eraser"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stock Table -->
                        <div class="card">
                            <div class="card-body">
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
                                                    <th wire:click="sortBy('purchase_price')" style="cursor: pointer;" class="text-end">
                                                        Purchase Price
                                                        @if($sortBy == 'purchase_price')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
                                                    <th wire:click="sortBy('sale_price')" style="cursor: pointer;" class="text-end">
                                                        Sale Price
                                                        @if($sortBy == 'sale_price')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
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
                                                        @if($product->image)
                                                            <img src="{{ asset('storage/' . $product->image) }}" 
                                                                 alt="{{ $product->name }}" 
                                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                        @else
                                                            <div class="bg-light d-flex align-items-center justify-content-center" 
                                                                 style="width: 50px; height: 50px; border-radius: 5px;">
                                                                <i class="bi bi-image text-muted"></i>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong>{{ $product->name }}</strong>
                                                        @if($product->description)
                                                            <br>
                                                            <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                                        @endif
                                                    </td>
                                                    <td><code>{{ $product->sku }}</code></td>
                                                    <td class="text-end fw-bold">
                                                        {{ number_format($product->stock) }}
                                                    </td>
                                                    <td class="text-end">{{ $this->formatCurrency($product->purchase_price ?? 0) }}</td>
                                                    <td class="text-end">{{ $this->formatCurrency($product->sale_price ?? 0) }}</td>
                                                    <td class="text-end">{{ $this->formatCurrency(($product->stock ?? 0) * ($product->purchase_price ?? 0)) }}</td>
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
                                                            <button type="button" 
                                                                    class="btn btn-success" 
                                                                    title="Adjust Stock"
                                                                    wire:click="adjustStock({{ $product->id }})">
                                                                <i class="bi bi-plus-slash-minus"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-dark">
                                                <tr>
                                                    <th colspan="4" class="text-end">Totals:</th>
                                                    <th class="text-end fw-bold">{{ number_format($totalStock) }}</th>
                                                    <th colspan="3" class="text-end fw-bold">{{ $this->formatCurrency($totalValue) }}</th>
                                                    <th colspan="2"></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <!-- Stock Summary -->
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>Stock Summary:</strong>
                                                {{ $totalProducts }} products with {{ number_format($totalStock) }} total units,
                                                valued at {{ $this->formatCurrency($totalValue) }}.
                                                @if($lowStockCount > 0)
                                                    <span class="text-warning">{{ $lowStockCount }} products are low on stock.</span>
                                                @endif
                                                @if($outOfStockCount > 0)
                                                    <span class="text-danger">{{ $outOfStockCount }} products are out of stock.</span>
                                                @endif
                                            </div>
                                        </div>
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