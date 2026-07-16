<?php

use Livewire\Component;
use App\Models\Customer;
use App\Models\Product;
use App\Models\sale as Sale;
use App\Models\sale_item as SaleItem;

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
    public $status = 'pending';
    public $final_total = 0;
    
    // Sale items
    public $items = [];
    // Models data for dropdowns
    public $customers = [];
    public $products = [];
    
    public function mount(){
        $this->sale_date = date('Y-m-d');
        $this->products = Product::all();
        $this->customers = Customer::all();
        $this->generateInvoiceNumber(); // Auto-generate invoice number
        $this->calculateTotals();
    }
    
    /**
     * Auto-generate invoice number
     * Format: INV-YYYY-MM-XXXX (where XXXX is sequential number)
     */
    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        // Get the last invoice number for this month
        $lastInvoice = Sale::whereYear('created_at', $year)
                           ->whereMonth('created_at', $month)
                           ->orderBy('id', 'desc')
                           ->first();
        
        if ($lastInvoice) {
            // Extract the sequence number from last invoice
            $parts = explode('-', $lastInvoice->invoice_no);
            $lastSeq = (int) end($parts);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newSeq = '0001';
        }
        
        $this->invoice_no = 'INV-' . $year . '-' . $month . '-' . $newSeq;
    }
    
    public function additem(){
        $this->items[] = [
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
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->calculateTotals();
    }
    
    public function updateItem($index)
{
    // First, check if a product is selected
    if (empty($this->items[$index]['product_id'])) {
        return; // Do nothing if no product selected
    }
    
    $selectedProductId = $this->items[$index]['product_id'];
    
    // Check for duplicates in the items array (excluding the current index)
    $duplicateIndex = $this->findDuplicateProduct($selectedProductId, $index);
    
    if ($duplicateIndex !== false) {
        // Product already exists in another row
        // Option 1: Show error message and reset the current selection
        session()->flash('error', 'This product has already been added!');
        
        // Reset the current item's product selection
        $this->items[$index]['product_id'] = '';
        $this->items[$index]['sku'] = '';
        $this->items[$index]['price'] = 0;
        $this->items[$index]['total'] = 0;
        
        // Recalculate totals
        $this->calculateTotals();
        return;
    }
    
    // No duplicate found, proceed with normal update
    $product = Product::find($selectedProductId);
    if ($product) {
        $this->items[$index]['sku'] = $product->sku;
        $this->items[$index]['price'] = $product->sale_price;
        $this->items[$index]['total'] = $this->items[$index]['quantity'] * $this->items[$index]['price'];
    }
    $this->calculateTotals();
}

/**
 * Find duplicate product in items array using array_search()
 * 
 * @param int $productId The product ID to search for
 * @param int $currentIndex The index to exclude from search
 * @return int|false The index of duplicate or false if not found
 */
private function findDuplicateProduct($productId, $currentIndex)
{
    // Extract all product IDs from the items array
    $productIds = array_column($this->items, 'product_id');
    
    // Remove the current index from search
    unset($productIds[$currentIndex]);
    
    // Search for the product ID in the remaining items
    return array_search($productId, $productIds);
}
    
    private function calculateTotals()
    {
        $this->subtotal = array_sum(array_column($this->items, 'total'));
        $this->total = $this->subtotal - $this->discount + $this->tax;
    }
    
    public function dicount_calculation(){
        $disconted_amount = ($this->subtotal * $this->discount) / 100;
        $this->total = $this->subtotal - $disconted_amount;
        $this->tax_calculation(); // Auto-calculate tax when discount changes
    }
    
    public function tax_calculation(){
        $tax_amount = ($this->total * $this->tax) / 100;
        $this->final_total = $this->total + $tax_amount;
        $this->paid_amount_calculation(); // Auto-update due when tax changes
    }
    
    public function paid_amount_calculation(){
        $this->due_amount = $this->final_total - $this->paid_amount;
        $this->updatePaymentStatus(); // Auto-update payment status
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
    
    public function create_sale()
    {
        // Validate the input
        $this->validate([
            'invoice_no' => 'required|unique:sales,invoice_no',
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,mobile',
            'status' => 'required|in:completed,pending,cancelled',
        ]);

        // Recalculate all totals to ensure accuracy
        $this->calculateTotals();
        $this->dicount_calculation();
        $this->tax_calculation();
        $this->paid_amount_calculation();
        //dd($this->status);
        // Create the sale
        $sale = Sale::create([
            'invoice_no' => $this->invoice_no,
            'customer_id' => $this->customer_id,
            'sale_date' => $this->sale_date,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->final_total, // final_total maps to total in database
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => auth()->id(),
        ]);

        // Create sale items
        foreach ($this->items as $item) {
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'sku' => $item['sku'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['quantity'] * $item['price'],
            ]);

        }
        // for  subtract quantity ..
        if($this->status == "completed"){
            foreach ($this->items as $item) {
                $product = Product::find($item['product_id']);
                $product->stock -= $item['quantity'];
                $product->save();
            }
        }
        // Reset form or redirect
        session()->flash('message', 'Sale created successfully!');
        
        // Redirect to sale details or list
        return redirect()->route('sales.show', $sale->id);
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
                        <form wire:submit.prevent="create_sale">
                            <!-- Sale Information -->
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Invoice No *</label>
                                    <input type="text" 
                                           class="form-control @error('invoice_no') is-invalid @enderror" 
                                           wire:model="invoice_no"
                                           readonly
                                           style="background-color: #e9ecef;">
                                    @error('invoice_no')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
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
                                    <label class="form-label fw-bold">Status</label>
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
        wire:model="items.{{ $index }}.product_id" 
        wire:change="updateItem({{ $index }})">
    <option value="">Select Product</option>
    @foreach($products as $product)
        @if($product->quantity > 0)
            @php
                // Check if this product is already added in other rows
                $isAlreadyAdded = false;
                foreach($items as $key => $item) {
                    if($key != $index && isset($item['product_id']) && $item['product_id'] == $product->id) {
                        $isAlreadyAdded = true;
                        break;
                    }
                }
            @endphp
            <option value="{{ $product->id }}" 
                    {{ $isAlreadyAdded ? 'disabled' : '' }}
                    {{ isset($item['product_id']) && $item['product_id'] == $product->id ? 'selected' : '' }}>
                {{ $product->name }}
                @if($isAlreadyAdded)
                    (Already Added)
                @endif
            </option>
        @endif
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
                                                        class="btn btn-sm btn-danger" wire:click="removeItem({{ $index }})">
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
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save" wire:loading.remove></i> <i class="bi bi-spinner bi-spin" wire:loading></i> Create Sale 
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