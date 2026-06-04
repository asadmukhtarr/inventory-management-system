<?php

use Livewire\Component;

new class extends Component
{
    public $stats = [];
    public $recentSales = [];
    public $lowStockProducts = [];
    public $name = "Asad";

    public function mount()
    {
        // Static Stats Data
        $this->stats = [
            'total_products' => 245,
            'total_categories' => 12,
            'total_suppliers' => 18,
            'total_sales' => 1240,
            'total_revenue' => 45890,
            'low_stock' => 8,
        ];

        // Recent Sales
        $this->recentSales = [
            ['id' => 1, 'customer' => 'John Doe', 'product' => 'Wireless Mouse', 'amount' => 25.99, 'date' => '2024-01-15', 'status' => 'completed'],
            ['id' => 2, 'customer' => 'Jane Smith', 'product' => 'Mechanical Keyboard', 'amount' => 89.99, 'date' => '2024-01-15', 'status' => 'completed'],
            ['id' => 3, 'customer' => 'Robert Johnson', 'product' => 'USB-C Cable', 'amount' => 12.99, 'date' => '2024-01-14', 'status' => 'pending'],
            ['id' => 4, 'customer' => 'Maria Garcia', 'product' => 'Laptop Stand', 'amount' => 45.50, 'date' => '2024-01-14', 'status' => 'completed'],
            ['id' => 5, 'customer' => 'David Lee', 'product' => 'HDMI Cable', 'amount' => 8.99, 'date' => '2024-01-13', 'status' => 'completed'],
        ];

        // Low Stock Products
        $this->lowStockProducts = [
            ['id' => 1, 'name' => 'Wireless Mouse', 'stock' => 5, 'min_stock' => 10, 'category' => 'Electronics'],
            ['id' => 2, 'name' => 'HDMI Cable', 'stock' => 3, 'min_stock' => 8, 'category' => 'Accessories'],
            ['id' => 3, 'name' => 'USB Drive 32GB', 'stock' => 7, 'min_stock' => 15, 'category' => 'Storage'],
            ['id' => 4, 'name' => 'Laptop Bag', 'stock' => 4, 'min_stock' => 10, 'category' => 'Accessories'],
            ['id' => 5, 'name' => 'Webcam HD', 'stock' => 2, 'min_stock' => 5, 'category' => 'Electronics'],
        ];
    }
};

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
        }
        
        .quick-actions {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
        }
    </style>

    <!-- Welcome Section -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Welcome back, {{ $name }}! 👋</h3>
        <p class="text-muted">{{ now()->format('l, F j, Y') }}</p>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Products</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['total_products'] }}</h2>
                        </div>
                        <i class="bi bi-box-seam stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-arrow-up"></i> +12% from last month
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Revenue</h6>
                            <h2 class="mb-0 fw-bold">${{ number_format($stats['total_revenue']) }}</h2>
                        </div>
                        <i class="bi bi-currency-dollar stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-arrow-up"></i> +8.5% from last month
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Sales</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['total_sales'] }}</h2>
                        </div>
                        <i class="bi bi-cart-check stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-arrow-up"></i> +15 transactions
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
                            <h2 class="mb-0 fw-bold">{{ $stats['low_stock'] }}</h2>
                        </div>
                        <i class="bi bi-exclamation-triangle stat-icon"></i>
                    </div>
                    <small class="opacity-75 mt-3 d-block">
                        <i class="bi bi-clock"></i> Needs attention
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row - Additional Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Categories</h6>
                            <h3 class="fw-bold mb-0">{{ $stats['total_categories'] }}</h3>
                        </div>
                        <i class="bi bi-tags fs-1 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Suppliers</h6>
                            <h3 class="fw-bold mb-0">{{ $stats['total_suppliers'] }}</h3>
                        </div>
                        <i class="bi bi-truck fs-1 text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Sales Table -->
    <div class="card table-custom mb-4">
        <div class="card-header bg-white border-0 pt-4">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-clock-history me-2"></i> Recent Sales
                </h5>
                <button class="btn btn-sm btn-outline-primary" onclick="alert('Navigate to all sales page')">
                    View All <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentSales as $sale)
                        <tr>
                            <td>#{{ $sale['id'] }}</td>
                            <td>{{ $sale['customer'] }}</td>
                            <td>{{ $sale['product'] }}</td>
                            <td>${{ number_format($sale['amount'], 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($sale['date'])->format('M d, Y') }}</td>
                            <td>
                                <span class="badge bg-{{ $sale['status'] == 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($sale['status']) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                 \\
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alert Table -->
    <div class="card table-custom">
        <div class="card-header bg-white border-0 pt-4">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                    <i class="bi bi-exclamation-diamond me-2 text-warning"></i> Low Stock Products
                </h5>
                <button class="btn btn-sm btn-outline-warning" onclick="alert('Navigate to stock management')">
                    Manage Stock <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Min Stock Level</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockProducts as $product)
                        <tr>
                            <td class="fw-bold">{{ $product['name'] }}</td>
                            <td>{{ $product['category'] }}</td>
                            <td>{{ $product['stock'] }} units</td>
                            <td>{{ $product['min_stock'] }} units</td>
                            <td>
                                <span class="badge-low-stock">
                                    <i class="bi bi-exclamation-triangle"></i> Low Stock
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="alert('Restock {{ $product['name'] }}')">
                                    <i class="bi bi-plus-circle"></i> Restock
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                \\
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Footer -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 quick-actions text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="fw-bold mb-2">Quick Actions</h5>
                            <p class="mb-0 opacity-75">Add new product, create sale, or generate reports quickly</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <button class="btn btn-light me-2" onclick="alert('Navigate to add product page')">
                                <i class="bi bi-plus-circle"></i> Add Product
                            </button>
                            <button class="btn btn-outline-light" onclick="alert('Navigate to create sale page')">
                                <i class="bi bi-cart-plus"></i> New Sale
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>