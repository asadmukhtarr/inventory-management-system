<?php

use Livewire\Component;
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
    public $status = 'pending';
    
    // Sale items
    public $items = [];
    
    // Models data for dropdowns
    public $customers = [];
    public $products = [];
    public function mount(){
         $this->sale_date = date('Y-m-d');
         $this->products = product::all();
         $this->customers = customer::all();
    }
    public function additem(){
        $this->items[] = [
            'product_id' => '',
            'sku' => '',
            'quantity' => 1,
            'price' => 0,
            'total' => 0
        ];
    }
    public function removeItem($index){
        // dd($index);
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }
    public function updateItem($index){
        $product = product::find($this->items[$index]['product_id']);
        $this->items[$index]['sku'] = $product->sku;
        $this->items[$index]['price'] = $product->sale_price;
        $this->items[$index]['total'] = $this->items[$index]['quantity'] * $this->items[$index]['price']; 
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
                        <form>
                            <!-- Sale Information -->
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Invoice No *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model="invoice_no"
                                           placeholder="INV-2024-0001">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Sale Date *</label>
                                    <input type="date" 
                                           class="form-control" 
                                           wire:model="sale_date">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Customer *</label>
                                    <select class="form-control" 
                                            wire:model="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <select class="form-control" 
                                            wire:model="status">
                                        <option value="completed">Completed</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
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
                                                <select class="form-control" 
                                                        wire:model="items.{{ $index }}.product_id" wire:change="updateItem({{ $index }})">
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" 
                                                       class="form-control" 
                                                       wire:model="items.{{ $index }}.sku"
                                                       readonly>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       class="form-control" 
                                                       wire:model="items.{{ $index }}.quantity" 
                                                       min="1">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" 
                                                       class="form-control" 
                                                       wire:model="items.{{ $index }}.price" 
                                                       min="0">
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
                                        <textarea class="form-control" 
                                                  wire:model="notes" 
                                                  rows="3"
                                                  placeholder="Additional notes..."></textarea>
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
                                                    <select class="form-control form-control-sm" 
                                                            wire:model="payment_method">
                                                        <option value="cash">Cash</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                        <option value="credit_card">Credit Card</option>
                                                        <option value="mobile_money">Mobile Money</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-lg">
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