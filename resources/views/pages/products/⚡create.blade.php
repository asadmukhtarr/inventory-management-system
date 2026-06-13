<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;

new class extends Component
{
    use WithFileUploads;
    
    public $name;
    public $image;
    public $description;
    public $sku;
    public $category_id;
    public $brand_id;
    public $supplier_id;
    public $quantity;
    public $sale_price;
    public $purchase_price;
    public $status = 1;
    
    public $categories = [];
    public $brands = [];
    public $suppliers = [];
    
    public $temp_image; // Temporary variable for image upload
    
    public function mount(){
        //dd("Hello Mount");
        $this->categories = Category::all();
        $this->brands = Brand::all();
        $this->suppliers = Supplier::all();
       //dd($this->supplier->count());
    }
    
    public function saveProduct()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'temp_image' => 'nullable|image|max:2048', // 2MB Max
            'description' => 'nullable|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|integer|min:0',
            'sale_price' => 'required|integer|min:0',
            'purchase_price' => 'required|integer|min:0',
            'status' => 'required|in:0,1'
        ]);
        
        // Handle image upload
        $imagePath = null;
        if ($this->temp_image) {
            $imagePath = $this->temp_image->store('products', 'public');
        }
        
        Product::create([
            'name' => $this->name,
            'image' => $imagePath,
            'description' => $this->description,
            'sku' => $this->sku,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'supplier_id' => $this->supplier_id,
            'quantity' => $this->quantity,
            'sale_price' => $this->sale_price,
            'purchase_price' => $this->purchase_price,
            'status' => $this->status
        ]);
        
        session()->flash('message', 'Product created successfully!');
        
        // Reset form
        $this->reset(['name', 'image', 'description', 'sku', 'category_id', 
                      'brand_id', 'supplier_id', 'quantity', 'sale_price', 
                      'purchase_price', 'status', 'temp_image']);
    }
}

?>

<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Create New Product</h4>
                    </div>
                    <div class="card-body">
                        @if(session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <form wire:submit.prevent="saveProduct" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           wire:model="name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">SKU *</label>
                                    <input type="text" 
                                           class="form-control @error('sku') is-invalid @enderror" 
                                           wire:model="sku">
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Product Image</label>
                                    <input type="file" 
                                           class="form-control @error('temp_image') is-invalid @enderror" 
                                           wire:model="temp_image"
                                           accept="image/*">
                                    @error('temp_image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    
                                    @if($temp_image)
                                        <div class="mt-2">
                                            <label class="form-label">Image Preview:</label>
                                            <br>
                                            <img src="{{ $temp_image->temporaryUrl() }}" 
                                                 alt="Preview" 
                                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;">
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              wire:model="description" 
                                              rows="3"></textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-control @error('category_id') is-invalid @enderror" 
                                            wire:model="category_id">
                                        <option value="">Select Category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Brand *</label>
                                    <select class="form-control @error('brand_id') is-invalid @enderror" 
                                            wire:model="brand_id">
                                        <option value="">Select Brand</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}">{{ $brand->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Supplier *</label>
                                    <select class="form-control @error('supplier_id') is-invalid @enderror" 
                                            wire:model="supplier_id">
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" 
                                           class="form-control @error('quantity') is-invalid @enderror" 
                                           wire:model="quantity"
                                           min="0">
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Purchase Price *</label>
                                    <input type="number" 
                                           class="form-control @error('purchase_price') is-invalid @enderror" 
                                           wire:model="purchase_price"
                                           min="0">
                                    @error('purchase_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Sale Price *</label>
                                    <input type="number" 
                                           class="form-control @error('sale_price') is-invalid @enderror" 
                                           wire:model="sale_price"
                                           min="0">
                                    @error('sale_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror" 
                                            wire:model="status">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Product
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