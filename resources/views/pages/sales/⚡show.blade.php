<?php

use Livewire\Component;
use App\Models\sale as Sale;
use App\Models\sale_item as SaleItem;

new class extends Component
{
    public $sale;
    public $saleItems = [];
    public $customer;
    public $saleId;
    
    public function mount($id)
    {
        $this->saleId = $id;
        
        // Load the sale with relationships
        $this->sale = Sale::with(['customer', 'creator'])->findOrFail($id);
        $this->saleItems = SaleItem::where('sale_id', $id)
                                   ->with('product')
                                   ->get();
        $this->customer = $this->sale->customer;
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
    
    public function cancelSale()
    {
        if ($this->sale->status !== 'cancelled') {
            $this->sale->status = 'cancelled';
            $this->sale->save();
            
            session()->flash('message', 'Sale cancelled successfully!');
            $this->mount($this->saleId);
        }
    }
    
    public function render()
    {
        return <<<'BLADE'
        <div>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        @if(session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <!-- Breadcrumb / Navigation -->
                        <div class="mb-3">
                            <a href="{{ route('sales.sales') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Sales
                            </a>
                            <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-warning">
                                <i class="bi bi-pencil"></i> Edit Sale
                            </a>
                            <button type="button" class="btn btn-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            @if($sale->status != 'cancelled')
                            <button type="button" class="btn btn-danger" 
                                    onclick="if(confirm('Are you sure you want to cancel this sale?')) { @this.cancelSale() }">
                                <i class="bi bi-x-circle"></i> Cancel Sale
                            </button>
                            @endif
                        </div>
                        
                        <!-- Sale Header Card -->
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">
                                    <i class="bi bi-receipt"></i> Sale Details
                                </h4>
                                <div>
                                    <span class="badge bg-{{ $this->getStatusBadgeClass($sale->status) }} fs-6 me-2">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                    <span class="badge bg-{{ $this->getPaymentBadgeClass($sale->payment_status) }} fs-6">
                                        {{ ucfirst($sale->payment_status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Sale Information -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <label class="text-muted small">Invoice Number</label>
                                        <p class="fw-bold fs-5">{{ $sale->invoice_no }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted small">Sale Date</label>
                                        <p class="fw-bold">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d M Y') }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted small">Customer</label>
                                        <p class="fw-bold">{{ $customer->name ?? 'N/A' }}</p>
                                        @if($customer)
                                            <small class="text-muted">{{ $customer->email ?? '' }}</small>
                                            <br>
                                            <small class="text-muted">{{ $customer->phone ?? '' }}</small>
                                        @endif
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted small">Created By</label>
                                        <p class="fw-bold">{{ $sale->creator->name ?? 'N/A' }}</p>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($sale->created_at)->format('d M Y H:i') }}</small>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <!-- Sale Items Table -->
                                <h5 class="mb-3">Sale Items</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="30%">Product</th>
                                                <th width="15%">SKU</th>
                                                <th width="15%">Quantity</th>
                                                <th width="15%">Unit Price</th>
                                                <th width="15%" class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($saleItems as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                    @if($item->product && $item->product->description)
                                                        <br>
                                                        <small class="text-muted">{{ $item->product->description }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $item->sku ?? '-' }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>${{ number_format($item->price, 2) }}</td>
                                                <td class="text-end fw-bold">${{ number_format($item->total, 2) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No items found for this sale.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <td colspan="5" class="text-end fw-bold">Total Items:</td>
                                                <td class="text-end fw-bold">{{ $saleItems->sum('quantity') }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <!-- Totals Section -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        @if($sale->notes)
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <strong><i class="bi bi-sticky"></i> Notes</strong>
                                                </div>
                                                <div class="card-body">
                                                    {{ $sale->notes }}
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- Customer Info if available -->
                                        @if($customer && ($customer->address || $customer->city))
                                        <div class="card mt-3">
                                            <div class="card-header bg-light">
                                                <strong><i class="bi bi-person"></i> Customer Details</strong>
                                            </div>
                                            <div class="card-body">
                                                @if($customer->address)
                                                    <p><strong>Address:</strong> {{ $customer->address }}</p>
                                                @endif
                                                @if($customer->city)
                                                    <p><strong>City:</strong> {{ $customer->city }}</p>
                                                @endif
                                                @if($customer->phone)
                                                    <p><strong>Phone:</strong> {{ $customer->phone }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="row mb-2">
                                                    <div class="col-6 fw-bold">Subtotal:</div>
                                                    <div class="col-6 text-end">${{ number_format($sale->subtotal, 2) }}</div>
                                                </div>
                                                
                                                @if($sale->discount > 0)
                                                <div class="row mb-2">
                                                    <div class="col-6 fw-bold">Discount:</div>
                                                    <div class="col-6 text-end text-success">-${{ number_format($sale->discount, 2) }}</div>
                                                </div>
                                                @endif
                                                
                                                @if($sale->tax > 0)
                                                <div class="row mb-2">
                                                    <div class="col-6 fw-bold">Tax:</div>
                                                    <div class="col-6 text-end">+${{ number_format($sale->tax, 2) }}</div>
                                                </div>
                                                @endif
                                                
                                                <hr>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-6 fw-bold fs-5">Total:</div>
                                                    <div class="col-6 text-end fw-bold fs-5">${{ number_format($sale->total, 2) }}</div>
                                                </div>
                                                
                                                <hr>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-6 fw-bold">Paid Amount:</div>
                                                    <div class="col-6 text-end text-success">${{ number_format($sale->paid_amount, 2) }}</div>
                                                </div>
                                                
                                                <div class="row mb-2">
                                                    <div class="col-6 fw-bold">Due Amount:</div>
                                                    <div class="col-6 text-end fw-bold text-danger">${{ number_format($sale->due_amount, 2) }}</div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-6 fw-bold">Payment Method:</div>
                                                    <div class="col-6 text-end">
                                                        <span class="badge bg-info">
                                                            {{ ucfirst(str_replace('_', ' ', $sale->payment_method)) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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