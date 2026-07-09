<?php

use Livewire\Component;
use App\Models\sale;
use App\Models\sale_item;
use App\Models\Customer;

new class extends Component
{
    public $sales = [];
    public $customers = [];
    public $statusFilter = '';
    public $searchTerm = '';
    public $dateFrom = '';
    public $dateTo = '';
    
    // Status counts for badges
    public $pendingCount = 0;
    public $completedCount = 0;
    public $cancelledCount = 0;
    
    public function mount()
    {
        $this->customers = Customer::all();
        $this->loadSales();
    }
    
    public function loadSales()
    {
        $query = Sale::with(['customer', 'creator']);
        
        // Apply status filter if selected
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('invoice_no', 'like', '%' . $this->searchTerm . '%')
                  ->orWhereHas('customer', function($q) {
                      $q->where('name', 'like', '%' . $this->searchTerm . '%')
                        ->orWhere('email', 'like', '%' . $this->searchTerm . '%');
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
        
        // Order by status priority: pending first, then completed, then cancelled
        // Using CASE statement for custom ordering
        $this->sales = $query->orderByRaw("
            CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'completed' THEN 2
                WHEN status = 'cancelled' THEN 3
                ELSE 4
            END
        ")
        ->orderBy('created_at', 'desc')
        ->get();
        
        // Update status counts
        $this->updateStatusCounts();
    }
    
    private function updateStatusCounts()
    {
        $counts = Sale::selectRaw('status, count(*) as count')
                      ->groupBy('status')
                      ->pluck('count', 'status')
                      ->toArray();
        
        $this->pendingCount = $counts['pending'] ?? 0;
        $this->completedCount = $counts['completed'] ?? 0;
        $this->cancelledCount = $counts['cancelled'] ?? 0;
    }
    
    public function filterByStatus($status)
    {
        $this->statusFilter = $status;
        $this->loadSales();
    }
    
    public function updatedSearchTerm()
    {
        $this->loadSales();
    }
    
    public function updatedDateFrom()
    {
        $this->loadSales();
    }
    
    public function updatedDateTo()
    {
        $this->loadSales();
    }
    
    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->searchTerm = '';
        $this->dateFrom = '';
        $this->dateTo = '';
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
        return $this->view();
    }
    public function deleteSale($id){
        $sale = sale::find($id);
        $sale_items = sale_item::where('sale_id',$sale->id)->delete();
        $sale->delete();
        session()->flash('success','Sale deleted successfully');
        $this->loadSales();
    }
}
?>
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi bi-receipt-cutoff"></i> Sales Management <i class="fa fa-spinner fa-spin" wire:loading></i>
                    </h2>
                    <a href="{{ route('sales.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create New Sale
                    </a>
                </div>
                
                <!-- Status Filter Badges -->
                <div class="mb-4">
                    <div class="btn-group" role="group">
                        <button type="button" 
                                class="btn btn-outline-secondary {{ $statusFilter == '' ? 'active' : '' }}"
                                wire:click="filterByStatus('')">
                            All <span class="badge bg-secondary ms-1">{{ $pendingCount + $completedCount + $cancelledCount }}</span>
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
                
                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" 
                                           class="form-control" 
                                           placeholder="Search by Invoice or Customer..."
                                           wire:model.live="searchTerm">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <input type="date" 
                                       class="form-control" 
                                       placeholder="Date From"
                                       wire:model.live="dateFrom">
                            </div>
                            <div class="col-md-3">
                                <input type="date" 
                                       class="form-control" 
                                       placeholder="Date To"
                                       wire:model.live="dateTo">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                                    <i class="bi bi-eraser"></i> Clear
                                </button>
                            </div>
                        </div>
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
                                            <th>#</th>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Paid</th>
                                            <th>Due</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sales as $index => $sale)
                                        <tr class="{{ $sale->status == 'pending' ? 'table-warning' : ($sale->status == 'cancelled' ? 'table-danger' : '') }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <strong>{{ $sale->invoice_no }}</strong>
                                            </td>
                                            <td>
                                                {{ $sale->customer->name ?? 'N/A' }}
                                                <br>
                                                <small class="text-muted">{{ $sale->customer->email ?? '' }}</small>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</td>
                                            <td class="fw-bold">${{ number_format($sale->total, 2) }}</td>
                                            <td class="text-success">${{ number_format($sale->paid_amount, 2) }}</td>
                                            <td class="text-danger fw-bold">${{ number_format($sale->due_amount, 2) }}</td>
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
                                                       class="btn btn-info text-white">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('sales.edit', $sale->id) }}" 
                                                       class="btn btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-danger"
                                                            wire:click="deleteSale({{ $sale->id }})" wire:confirm="Are you sure you want to delete this sale?">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-dark">
                                            <th colspan="4" class="text-end">Totals:</th>
                                            <th class="fw-bold">${{ number_format($sales->sum('total'), 2) }}</th>
                                            <th class="text-success fw-bold">${{ number_format($sales->sum('paid_amount'), 2) }}</th>
                                            <th class="text-danger fw-bold">${{ number_format($sales->sum('due_amount'), 2) }}</th>
                                            <th colspan="3"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <!-- Summary Cards -->
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="card bg-warning text-white">
                                        <div class="card-body">
                                            <h6 class="mb-0">Pending Sales</h6>
                                            <h3 class="mb-0">{{ $pendingCount }}</h3>
                                            <small>Total: ${{ number_format($sales->where('status', 'pending')->sum('total'), 2) }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body">
                                            <h6 class="mb-0">Completed Sales</h6>
                                            <h3 class="mb-0">{{ $completedCount }}</h3>
                                            <small>Total: ${{ number_format($sales->where('status', 'completed')->sum('total'), 2) }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body">
                                            <h6 class="mb-0">Cancelled Sales</h6>
                                            <h3 class="mb-0">{{ $cancelledCount }}</h3>
                                            <small>Total: ${{ number_format($sales->where('status', 'cancelled')->sum('total'), 2) }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body">
                                            <h6 class="mb-0">Total Sales</h6>
                                            <h3 class="mb-0">{{ $pendingCount + $completedCount + $cancelledCount }}</h3>
                                            <small>Total: ${{ number_format($sales->sum('total'), 2) }}</small>
                                        </div>
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