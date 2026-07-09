<?php

use Livewire\Component;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\SaleItem;
use Carbon\Carbon;

new class extends Component
{
    public $sales = [];
    public $customers = [];
    public $statusFilter = '';
    public $paymentFilter = '';
    public $searchTerm = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $showFilters = false;
    
    // Statistics
    public $totalSales = 0;
    public $totalRevenue = 0;
    public $totalPaid = 0;
    public $totalDue = 0;
    public $averageSale = 0;
    public $pendingCount = 0;
    public $completedCount = 0;
    public $cancelledCount = 0;
    
    // Chart data
    public $monthlyStats = [];
    
    public function mount()
    {
        $this->customers = Customer::all();
        $this->loadSales();
        $this->calculateStatistics();
        $this->getMonthlyStats();
    }
    
    public function loadSales()
    {
        $query = Sale::with(['customer', 'creator', 'items']);
        
        // Apply status filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        // Apply payment filter
        if ($this->paymentFilter) {
            $query->where('payment_status', $this->paymentFilter);
        }
        
        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('invoice_no', 'like', '%' . $this->searchTerm . '%')
                  ->orWhereHas('customer', function($q) {
                      $q->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('phone', 'like', '%' . $this->searchTerm . '%');
                  });
            });
        }
        
        // Apply date filters
        if ($this->dateFrom) {
            $query->whereDate('sale_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('sale_date', '<=', $this->dateTo);
        }
        
        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);
        
        // Get all sales (no pagination for history view)
        $this->sales = $query->get();
    }
    
    private function calculateStatistics()
    {
        $this->totalSales = $this->sales->count();
        $this->totalRevenue = $this->sales->sum('total');
        $this->totalPaid = $this->sales->sum('paid_amount');
        $this->totalDue = $this->sales->sum('due_amount');
        $this->averageSale = $this->totalSales > 0 ? $this->totalRevenue / $this->totalSales : 0;
        
        // Status counts
        $this->pendingCount = $this->sales->where('status', 'pending')->count();
        $this->completedCount = $this->sales->where('status', 'completed')->count();
        $this->cancelledCount = $this->sales->where('status', 'cancelled')->count();
    }
    
    private function getMonthlyStats()
    {
        $this->monthlyStats = Sale::selectRaw('
            DATE_FORMAT(sale_date, "%Y-%m") as month,
            COUNT(*) as count,
            SUM(total) as revenue,
            SUM(paid_amount) as paid,
            SUM(due_amount) as due
        ')
        ->where('status', 'completed')
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->limit(6)
        ->get();
    }
    
    public function filterByStatus($status)
    {
        $this->statusFilter = $status;
        $this->loadSales();
        $this->calculateStatistics();
    }
    
    public function filterByPayment($status)
    {
        $this->paymentFilter = $status;
        $this->loadSales();
        $this->calculateStatistics();
    }
    
    public function updatedSearchTerm()
    {
        $this->loadSales();
        $this->calculateStatistics();
    }
    
    public function updatedDateFrom()
    {
        $this->loadSales();
        $this->calculateStatistics();
    }
    
    public function updatedDateTo()
    {
        $this->loadSales();
        $this->calculateStatistics();
    }
    
    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->paymentFilter = '';
        $this->searchTerm = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->loadSales();
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
        $this->loadSales();
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
                                <i class="bi bi-clock-history"></i> Sale History
                            </h2>
                            <div>
                                <button type="button" class="btn btn-outline-primary me-2" wire:click="loadSales">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                                <a href="{{ route('sales.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Create New Sale
                                </a>
                            </div>
                        </div>
                        
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="mb-0">Total Sales</h6>
                                        <h3 class="mb-0">{{ $totalSales }}</h3>
                                        <small>{{ $this->formatCurrency($totalRevenue) }} Total Revenue</small>
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
                                        <small>Per Transaction</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Monthly Stats Chart (Simple) -->
                        @if($monthlyStats->count() > 0)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong><i class="bi bi-bar-chart"></i> Monthly Performance (Last 6 Months)</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($monthlyStats as $stat)
                                    <div class="col-md-2 mb-3">
                                        <div class="text-center">
                                            <div class="fw-bold">{{ Carbon\Carbon::parse($stat->month . '-01')->format('M Y') }}</div>
                                            <div class="progress" style="height: 100px;">
                                                <div class="progress-bar bg-success" 
                                                     role="progressbar" 
                                                     style="width: {{ ($stat->revenue / ($monthlyStats->max('revenue') ?: 1)) * 100 }}%; height: 100px; flex-direction: column; justify-content: flex-end; padding-bottom: 5px;">
                                                    {{ $this->formatCurrency($stat->revenue) }}
                                                </div>
                                            </div>
                                            <small>{{ $stat->count }} sales</small>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   placeholder="Search by Invoice or Customer..."
                                                   wire:model.live="searchTerm">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control" wire:model.live="statusFilter">
                                            <option value="">All Status</option>
                                            <option value="completed">Completed</option>
                                            <option value="pending">Pending</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control" wire:model.live="paymentFilter">
                                            <option value="">All Payment</option>
                                            <option value="paid">Paid</option>
                                            <option value="partial">Partial</option>
                                            <option value="unpaid">Unpaid</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" class="form-control" placeholder="Date From" wire:model.live="dateFrom">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" class="form-control" placeholder="Date To" wire:model.live="dateTo">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                                            <i class="bi bi-eraser"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Filter Buttons -->
                        <div class="mb-3">
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-outline-secondary {{ $statusFilter == '' ? 'active' : '' }}"
                                        wire:click="filterByStatus('')">
                                    All <span class="badge bg-secondary ms-1">{{ $totalSales }}</span>
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-warning {{ $statusFilter == 'pending' ? 'active' : '' }}"
                                        wire:click="filterByStatus('pending')">
                                    <i class="bi bi-clock"></i> Pending <span class="badge bg-warning ms-1">{{ $pendingCount }}</span>
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-success {{ $statusFilter == 'completed' ? 'active' : '' }}"
                                        wire:click="filterByStatus('completed')">
                                    <i class="bi bi-check-circle"></i> Completed <span class="badge bg-success ms-1">{{ $completedCount }}</span>
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-danger {{ $statusFilter == 'cancelled' ? 'active' : '' }}"
                                        wire:click="filterByStatus('cancelled')">
                                    <i class="bi bi-x-circle"></i> Cancelled <span class="badge bg-danger ms-1">{{ $cancelledCount }}</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Sales Table -->
                        <div class="card">
                            <div class="card-body">
                                @if($sales->isEmpty())
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3 text-muted">No sales found matching your criteria.</p>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th wire:click="sortBy('invoice_no')" style="cursor: pointer;">
                                                        Invoice No 
                                                        @if($sortBy == 'invoice_no')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
                                                    <th wire:click="sortBy('customer_id')" style="cursor: pointer;">
                                                        Customer
                                                        @if($sortBy == 'customer_id')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
                                                    <th wire:click="sortBy('sale_date')" style="cursor: pointer;">
                                                        Date
                                                        @if($sortBy == 'sale_date')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
                                                    <th wire:click="sortBy('total')" style="cursor: pointer;" class="text-end">
                                                        Total
                                                        @if($sortBy == 'total')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
                                                    <th class="text-end">Paid</th>
                                                    <th class="text-end">Due</th>
                                                    <th wire:click="sortBy('status')" style="cursor: pointer;">
                                                        Status
                                                        @if($sortBy == 'status')
                                                            <i class="bi bi-chevron-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                                        @endif
                                                    </th>
                                                    <th>Payment</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($sales as $index => $sale)
                                                <tr class="{{ $sale->status == 'pending' ? 'table-warning' : ($sale->status == 'cancelled' ? 'table-danger' : '') }}">
                                                    <td>
                                                        <strong>{{ $sale->invoice_no }}</strong>
                                                    </td>
                                                    <td>
                                                        {{ $sale->customer->name ?? 'N/A' }}
                                                        <br>
                                                        <small class="text-muted">{{ $sale->customer->email ?? '' }}</small>
                                                    </td>
                                                    <td>
                                                        {{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}
                                                        <br>
                                                        <small class="text-muted">{{ \Carbon\Carbon::parse($sale->created_at)->format('H:i') }}</small>
                                                    </td>
                                                    <td class="text-end fw-bold">
                                                        ${{ number_format($sale->total, 2) }}
                                                        <br>
                                                        <small class="text-muted">{{ $sale->items->count() }} items</small>
                                                    </td>
                                                    <td class="text-end text-success">
                                                        ${{ number_format($sale->paid_amount, 2) }}
                                                    </td>
                                                    <td class="text-end text-danger fw-bold">
                                                        ${{ number_format($sale->due_amount, 2) }}
                                                    </td>
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
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('sales.show', $sale->id) }}" 
                                                               class="btn btn-info text-white" title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="{{ route('sales.edit', $sale->id) }}" 
                                                               class="btn btn-warning" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-secondary" title="Duplicate"
                                                                    wire:click="duplicateSale({{ $sale->id }})">
                                                                <i class="bi bi-files"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-dark">
                                                <tr>
                                                    <th colspan="3" class="text-end">Totals:</th>
                                                    <th class="text-end fw-bold">${{ number_format($sales->sum('total'), 2) }}</th>
                                                    <th class="text-end fw-bold text-success">${{ number_format($sales->sum('paid_amount'), 2) }}</th>
                                                    <th class="text-end fw-bold text-danger">${{ number_format($sales->sum('due_amount'), 2) }}</th>
                                                    <th colspan="3"></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <!-- Export Actions -->
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-success" wire:click="exportCSV">
                                            <i class="bi bi-file-spreadsheet"></i> Export CSV
                                        </button>
                                        <button type="button" class="btn btn-danger" wire:click="exportPDF">
                                            <i class="bi bi-file-pdf"></i> Export PDF
                                        </button>
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