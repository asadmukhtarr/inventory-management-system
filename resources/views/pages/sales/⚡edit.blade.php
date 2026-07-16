<?php

use Livewire\Component;
use App\Models\Customer;
use App\Models\Product;
use App\Models\sale as Sale;
use App\Models\sale_item as SaleItem;

new class extends Component
{
    // Sale fields
    public $sale_id;
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
    public $status = 'pending';
    public $final_total = 0;
    
    // Sale items
    public $items = [];
    // Models data for dropdowns
    public $customers = [];
    public $products = [];
    
    public function mount($id)
    {
        // Load the sale
        $sale = Sale::with('items')->findOrFail($id);
        $this->sale_id = $sale->id;
        
        // Load dropdown data
        $this->products = Product::all();
        $this->customers = Customer::all();
        
        // Populate sale fields
        $this->invoice_no = $sale->invoice_no;
        $this->customer_id = $sale->customer_id;
        $this->sale_date = $sale->sale_date;
        $this->subtotal = $sale->subtotal;
        $this->discount = $sale->discount;
        $this->tax = $sale->tax;
        $this->total = $sale->total;
        $this->paid_amount = $sale->paid_amount;
        $this->due_amount = $sale->due_amount;
        $this->payment_status = $sale->payment_status;
        $this->payment_method = $sale->payment_method;
        $this->notes = $sale->notes;
        $this->status = $sale->status;
        $this->final_total = $sale->total;
        
        // Populate items
        foreach ($sale->items as $item) {
            $this->items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total
            ];
        }
        
        // If no items exist, add one empty row
        if (empty($this->items)) {
            $this->additem();
        }
        
        $this->calculateTotals();
    }
    
    public function additem(){
        $this->items[] = [
            'id' => null,
            'product_id' => '',
            'sku' => '',
            'quantity' => 1,
            'price' => 0,
            'total' => 0
        ];
        $this->calculateTotals();
    }
    
    public function changetotal($index){
        $this->items[$index]['total'] = $this->items[$index]['quantity'] * $this->items[$index]['price'];
        $this->calculateTotals();
    }
    
    public function removeItem($index){
        // If item has an ID, mark it for deletion
        if (isset($this->items[$index]['id']) && $this->items[$index]['id']) {
            SaleItem::find($this->items[$index]['id'])->delete();
        }
        
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotals();
    }
    
    public function updateItem($index){
        $product = Product::find($this->items[$index]['product_id']);
        if ($product) {
            $this->items[$index]['sku'] = $product->sku;
            $this->items[$index]['price'] = $product->sale_price;
            $this->items[$index]['total'] = $this->items[$index]['quantity'] * $this->items[$index]['price']; 
        }
        $this->calculateTotals();
    }
    
    private function calculateTotals()
    {
        $this->subtotal = array_sum(array_column($this->items, 'total'));
        $this->total = $this->subtotal - $this->discount + $this->tax;
    }
    
    public function dicount_calculation(){
        $disconted_amount = ($this->subtotal * $this->discount) / 100;
        $this->total = $this->subtotal - $disconted_amount;
        $this->tax_calculation();
    }
    
    public function tax_calculation(){
        $tax_amount = ($this->total * $this->tax) / 100;
        $this->final_total = $this->total + $tax_amount;
        $this->paid_amount_calculation();
    }
    
    public function paid_amount_calculation(){
        $this->due_amount = $this->final_total - $this->paid_amount;
        $this->updatePaymentStatus();
    }
    
    private function updatePaymentStatus()
    {
        if ($this->due_amount <= 0) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0 && $this->due_amount > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
        }
    }
    
    public function update_sale()
    {
        // Validate the input
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,mobile',
            'status' => 'required|in:completed,pending,cancelled',
        ]);

        // Recalculate all totals
        $this->calculateTotals();
        $this->dicount_calculation();
        $this->tax_calculation();
        $this->paid_amount_calculation();

        // Update the sale
        $sale = Sale::findOrFail($this->sale_id);
        $sale->update([
            'customer_id' => $this->customer_id,
            'sale_date' => $this->sale_date,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->final_total,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'status' => $this->status,
        ]);

        // Update sale items
        foreach ($this->items as $item) {
            if (isset($item['id']) && $item['id']) {
                // Update existing item
                SaleItem::where('id', $item['id'])->update([
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            } else {
                // Create new item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }
        }
         if($this->status == "completed"){
            foreach ($this->items as $item) {
                $product = Product::find($item['product_id']);
                $product->quantity -= $item['quantity'];
                $product->save();
            }
        }
        session()->flash('message', 'Sale updated successfully!');
        return redirect()->route('sales.show', $sale->id);
    }
    
    public function render()
    {
        return $this->view();
    }
}
?>
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Navigation -->
                <div class="mb-3">
                    <a href="{{ route('sales.sales') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Sales
                    </a>
                    <a href="{{ route('sales.show', $sale_id) }}" class="btn btn-info text-white">
                        <i class="bi bi-eye"></i> View Sale
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-pencil-square"></i> Edit Sale #{{ $invoice_no }}
                        </h4>
                    </div>
                    <div class="card-body">
                        @if(session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <form wire:submit.prevent="update_sale">
                            <!-- Sale Information -->
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Invoice No *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           value="{{ $invoice_no }}"
                                           readonly
                                           style="background-color: #e9ecef;">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Sale Date *</label>
                                    <input type="date" 
                                           class="form-control @error('sale_date') is-invalid @enderror" 
                                           wire:model="sale_date">
                                    @error('sale_date')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Customer *</label>
                                    <select class="form-control @error('customer_id') is-invalid @enderror" 
                                            wire:model="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Status *</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            wire:model="status">
                                        <option value="completed">Completed</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger small">{{ $message }}</span>
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
                                                <select class="form-control @error('items.'.$index.'.product_id') is-invalid @enderror" 
                                                        wire:model="items.{{ $index }}.product_id" wire:change="updateItem({{ $index }})">
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('items.'.$index.'.product_id')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="form-control" 
                                                       wire:model="items.{{ $index }}.sku"
                                                       readonly>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control @error('items.'.$index.'.quantity') is-invalid @enderror" 
                                                       wire:model="items.{{ $index }}.quantity"
                                                       wire:keyup="changetotal({{ $index }})" 
                                                       min="1">
                                                @error('items.'.$index.'.quantity')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" 
                                                       class="form-control @error('items.'.$index.'.price') is-invalid @enderror" 
                                                       wire:model="items.{{ $index }}.price" 
                                                       min="0">
                                                @error('items.'.$index.'.price')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                            </td>
                                            <td class="text-end fw-bold">
                                                ${{ number_format($item['total'] ?? 0, 2) }}
                                            </td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        wire:click="removeItem({{ $index }})"
                                                        onclick="return confirm('Are you sure you want to remove this item?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7">
                                                <button type="button" wire:click="additem" class="btn btn-sm btn-success">
                                                    <i class="bi bi-plus-circle"></i> Add Item
                                                </button>
                                                @error('items')
                                                    <span class="text-danger small d-block mt-1">{{ $message }}</span>
                                                @enderror
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
                                        <textarea class="form-control @error('notes') is-invalid @enderror" 
                                                  wire:model="notes" 
                                                  rows="3"
                                                  placeholder="Additional notes..."></textarea>
                                        @error('notes')
                                            <span class="text-danger small">{{ $message }}</span>
                                        @enderror
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
                                                           class="form-control form-control-sm d-inline-block @error('discount') is-invalid @enderror" 
                                                           style="width: 120px; text-align: right;"
                                                           wire:keyup="dicount_calculation"
                                                           wire:model="discount" 
                                                           min="0">
                                                    @error('discount')
                                                        <span class="text-danger small d-block">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <hr>
                                            
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Total:</div>
                                                <div class="col-6 text-end fw-bold fs-5">${{ number_format($total, 2) }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Tax:</div>
                                                <div class="col-6 text-end">
                                                    <input type="number" step="0.01" 
                                                           class="form-control form-control-sm d-inline-block @error('tax') is-invalid @enderror" 
                                                           style="width: 120px; text-align: right;"
                                                           wire:keyup="tax_calculation"
                                                           wire:model="tax" 
                                                           min="0">
                                                    @error('tax')
                                                        <span class="text-danger small d-block">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Final Total:</div>
                                                <div class="col-6 text-end fw-bold fs-5">${{ number_format($final_total, 2) }}</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-6 fw-bold">Paid Amount:</div>
                                                <div class="col-6 text-end">
                                                    <input type="number" step="0.01" 
                                                           class="form-control form-control-sm d-inline-block @error('paid_amount') is-invalid @enderror" 
                                                           style="width: 120px; text-align: right;"
                                                           wire:model="paid_amount"
                                                           wire:keyup="paid_amount_calculation" 
                                                           min="0">
                                                    @error('paid_amount')
                                                        <span class="text-danger small d-block">{{ $message }}</span>
                                                    @enderror
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
                                                    <select class="form-control form-control-sm @error('payment_method') is-invalid @enderror" 
                                                            wire:model="payment_method">
                                                        <option value="cash">Cash</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                        <option value="credit_card">Credit Card</option>
                                                        <option value="mobile_money">Mobile Money</option>
                                                    </select>
                                                    @error('payment_method')
                                                        <span class="text-danger small d-block">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="mt-3">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-save" wire:loading.remove></i> 
                                    <i class="bi bi-spinner bi-spin" wire:loading></i> 
                                    Update Sale
                                </button>
                                <a href="{{ route('sales.show', $sale_id) }}" class="btn btn-secondary btn-lg">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>