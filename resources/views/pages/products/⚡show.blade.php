<?php

use Livewire\Component;
use App\Models\Product;

new class extends Component
{
    public $product;
    
    public function mount($id)
    {
        $this->product = Product::with(['category', 'brand', 'supplier'])->find($id);
        
        if (!$this->product) {
            abort(404, 'Product not found');
        }
    }
    
    public function goBack()
    {
        return redirect()->route('products.index');
    }
};
?>

<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Back Button -->
                <div class="mb-3">
                    <a href="{{ url()->previous() }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>
                
                <!-- Product Details Card -->
                <div class="card">
                    <div class="card-header">
                        <h4>Product Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Product Image -->
                            <div class="col-md-4 text-center">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" 
                                         alt="{{ $product->name }}"
                                         class="img-fluid rounded"
                                         style="max-height: 400px; object-fit: cover; border: 1px solid #ddd;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                         style="height: 400px; border: 1px solid #ddd;">
                                        <i class="bi bi-box-seam" style="font-size: 100px; color: #6c757d;"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Product Information -->
                            <div class="col-md-8">
                                <h2 class="mb-3">{{ $product->name }}</h2>
                                
                                <div class="row">
                                    <!-- Basic Info -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted">SKU:</label>
                                            <p><span class="badge bg-secondary">{{ $product->sku }}</span></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted">Status:</label>
                                            <p>
                                                @if($product->status == 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted">Quantity:</label>
                                            <p>
                                                <span class="badge bg-{{ $product->quantity > 10 ? 'success' : ($product->quantity > 0 ? 'warning' : 'danger') }}">
                                                    {{ $product->quantity }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Pricing Info -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted">Sale Price:</label>
                                            <p><span class="fw-bold text-success" style="font-size: 24px;">₹{{ number_format($product->sale_price, 2) }}</span></p>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="fw-bold text-muted">Purchase Price:</label>
                                            <p><span class="text-danger">₹{{ number_format($product->purchase_price, 2) }}</span></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Related Info -->
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <i class="bi bi-tags text-primary" style="font-size: 24px;"></i>
                                                <h6 class="mt-2">Category</h6>
                                                <p class="mb-0">{{ $product->category->title ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <i class="bi bi-building text-success" style="font-size: 24px;"></i>
                                                <h6 class="mt-2">Brand</h6>
                                                <p class="mb-0">{{ $product->brand->title ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <i class="bi bi-person-badge text-info" style="font-size: 24px;"></i>
                                                <h6 class="mt-2">Supplier</h6>
                                                <p class="mb-0">{{ $product->supplier->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="mt-4">
                                    <label class="fw-bold text-muted">Description:</label>
                                    <p class="p-3 bg-light rounded">{{ $product->description ?? 'No description available.' }}</p>
                                </div>
                                
                                <!-- Timestamps -->
                                <div class="mt-3 text-muted">
                                    <small>
                                        <i class="bi bi-calendar-plus"></i> Created: {{ $product->created_at->format('F d, Y H:i A') }}
                                        <br>
                                        <i class="bi bi-calendar-check"></i> Updated: {{ $product->updated_at->format('F d, Y H:i A') }}
                                    </small>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="mt-4">
                                    <a href="{{ url()->previous() }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <a href="{{ route('products.edit',$product->id)}}" class="btn btn-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <button class="btn btn-danger" 
                                            wire:click="deleteProduct({{ $product->id }})"
                                            wire:confirm="Are you sure you want to delete this product?">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>