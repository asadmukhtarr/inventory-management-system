<?php

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;

new class extends Component
{
    // Sale fields
    public $invoice_no;
    public $customer_id;
    public $sale_date;
    public $subtotal = 0;
    public $discount = 0;
    public $tax = 0;
    public $total = 0;
    public $paid_amount = 0;
    public $due_amount = 0;
    public $payment_status = 'unpaid';
    public $payment_method = 'cash';
    public $notes;
    public $status = 'completed';
    
    // Sale items
    public $items = [];
    
    // Static data for dropdowns
    public $customers = [];
    public $products = [];
    public $paymentStatuses = [
        'paid' => 'Paid',
        'partial' => 'Partial',
        'unpaid' => 'Unpaid'
    ];
    public $paymentMethods = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'credit_card' => 'Credit Card',
        'mobile_money' => 'Mobile Money'
    ];
    public $statuses = [
        'completed' => 'Completed',
        'pending' => 'Pending',
        'cancelled' => 'Cancelled'
    ];
    
    public function mount()
    {
        // Load static data
        $this->customers = Customer::orderBy('name')->get();
        $this->products = Product::orderBy('name')->get();
        
        // Generate invoice number
        $this->invoice_no = 'INV-' . time() . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $this->sale_date = date('Y-m-d');
        
        // Add one empty item row
        $this->addItem();
    }
    
    public function addItem()
    {
        $this->items[] = [
            'product_id' => '',
            'sku' => '',
            'quantity' => 1,
            'price' => 0,
            'total' => 0
        ];
    }
    
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotals();
    }
    
    public function updatedItems($value, $key)
    {
        // Parse the key to get index and field
        $parts = explode('.', $key);
        if (count($parts) == 2) {
            $index = $parts[0];
            $field = $parts[1];
            
            // If product is selected, auto-fill SKU and price
            if ($field == 'product_id' && !empty($value)) {
                $product = Product::find($value);
                if ($product) {
                    $this->items[$index]['sku'] = $product->sku;
                    $this->items[$index]['price'] = $product->sale_price;
                }
            }
            
            // Calculate item total
            if (in_array($field, ['quantity', 'price'])) {
                $quantity = $this->items[$index]['quantity'] ?? 0;
                $price = $this->items[$index]['price'] ?? 0;
                $this->items[$index]['total'] = $quantity * $price;
            }
        }
        
        $this->calculateTotals();
    }
    
    public function calculateTotals()
    {
        $this->subtotal = 0;
        foreach ($this->items as $item) {
            $this->subtotal += $item['total'] ?? 0;
        }
        
        $this->total = $this->subtotal - $this->discount + $this->tax;
        $this->due_amount = $this->total - $this->paid_amount;
        
        // Auto update payment status
        if ($this->paid_amount >= $this->total && $this->total > 0) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0 && $this->paid_amount < $this->total) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }
    }
    
    public function updatedDiscount()
    {
        $this->calculateTotals();
    }
    
    public function updatedTax()
    {
        $this->calculateTotals();
    }
    
    public function updatedPaidAmount()
    {
        $this->calculateTotals();
    }
    
    public function saveSale()
    {
        $this->validate([
            'invoice_no' => 'required|string|unique:sales,invoice_no',
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'due_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:paid,partial,unpaid',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,mobile_money',
            'notes' => 'nullable|string',
            'status' => 'required|in:completed,pending,cancelled',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0'
        ]);
        
        // Create sale
        $sale = Sale::create([
            'invoice_no' => $this->invoice_no,
            'customer_id' => $this->customer_id,
            'sale_date' => $this->sale_date,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => auth()->id() ?? 1
        ]);
        
        // Create sale items
        foreach ($this->items as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total']
            ]);
        }
        
        session()->flash('message', 'Sale created successfully!');
        session()->flash('message_type', 'success');
        
        // Reset form
        $this->reset(['items', 'discount', 'tax', 'paid_amount', 'notes']);
        $this->subtotal = 0;
        $this->total = 0;
        $this->due_amount = 0;
        $this->payment_status = 'unpaid';
        $this->invoice_no = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $this->sale_date = date('Y-m-d');
        $this->addItem();
    }
}
?>

<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-cart-plus"></i> Create New Sale
                        </h4>
                    </div>
                    <div class="card-body">
                        @if(session()->has('message'))
                            <div class="alert alert-{{ session('message_type', 'success') }} alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <form wire:submit.prevent="saveSale">
                            <!-- Sale Information -->
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Invoice No *</label>
                                    <input type="text" 
                                           class="form-control @error('invoice_no') is-invalid @enderror" 
                                           wire:model="invoice_no" readonly>
                                    @error('invoice_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Sale Date *</label>
                                    <input type="date" 
                                           class="form-control @error('sale_date') is-invalid @enderror" 
                                           wire:model="sale_date">
                                    @error('sale_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Customer *</label>
                                    <select class="form-control @error('customer_id') is-invalid @enderror" 
                                            wire:model="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            wire:model="status">
                                        @foreach($statuses as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <hr>
                            <h5 class="mb-3">Sale Items</h5>
                            
                            <!-- Sale Items Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="25%">Product *</th>
                                            <th width="15%">SKU</th>
                                            <th width="15%">Quantity *</th>
                                            <th width="20%">Price *</th>
                                            <th width="15%">Total</th>
                                            <th width="5%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <select class="form-control @error('items.' . $index . '.product_id') is-invalid @enderror" 
                                                        wire:model="items.{{ $index }}.product_id">
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('items.' . $index . '.product_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="form-control" 
                                                       wire:model="items.{{ $index }}.sku" readonly>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control @error('items.' . $index . '.quantity') is-invalid @enderror" 
                                                       wire:model="items.{{ $index }}.quantity" 
                                                       min="1">
                                                @error('items.' . $index . '.quantity')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" 
                                                       class="form-control @error('items.' . $index . '.price') is-invalid @enderror" 
                                                       wire:model="items.{{ $index }}.price" 
                                                       min="0">
                                                @error('items.' . $index . '.price')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="text-end fw-bold">
                                                ${{ number_format($item['total'] ?? 0, 2) }}
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        wire:click="removeItem({{ $index }})"
                                                        {{ count($items) <= 1 ? 'disabled' : '' }}>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7">
                                                <button type="button" class="btn btn-sm btn-success" wire:click="addItem">
                                                    <i class="bi bi-plus-circle"></i> Add Item
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <!-- Totals -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" wire:model="notes" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Subtotal:</div>
                                                <div class="col-6 text-end">${{ number_format($subtotal, 2) }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Discount:</div>
                                                <div class="col-6 text-end">
                                                    <input type="number" step="0.01" 
                                                           class="form-control form-control-sm d-inline-block" 
                                                           style="width: 120px; text-align: right;"
                                                           wire:model="discount" 
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Tax:</div>
                                                <div class="col-6 text-end">
                                                    <input type="number" step="0.01" 
                                                           class="form-control form-control-sm d-inline-block" 
                                                           style="width: 120px; text-align: right;"
                                                           wire:model="tax" 
                                                           min="0">
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Total:</div>
                                                <div class="col-6 text-end fw-bold fs-5">${{ number_format($total, 2) }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Paid Amount:</div>
                                                <div class="col-6 text-end">
                                                    <input type="number" step="0.01" 
                                                           class="form-control form-control-sm d-inline-block" 
                                                           style="width: 120px; text-align: right;"
                                                           wire:model="paid_amount" 
                                                           min="0">
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Due Amount:</div>
                                                <div class="col-6 text-end fw-bold text-danger">${{ number_format($due_amount, 2) }}</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6 fw-bold">Payment Status:</div>
                                                <div class="col-6 text-end">
                                                    <span class="badge bg-{{ $payment_status == 'paid' ? 'success' : ($payment_status == 'partial' ? 'warning' : 'danger') }}">
                                                        {{ ucfirst($payment_status) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-6 fw-bold">Payment Method:</div>
                                                <div class="col-6 text-end">
                                                    <select class="form-control form-control-sm" wire:model="payment_method">
                                                        @foreach($paymentMethods as $key => $value)
                                                            <option value="{{ $key }}">{{ $value }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Create Sale
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="bi bi-arrow-repeat"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>