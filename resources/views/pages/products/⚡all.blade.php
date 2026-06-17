<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;

new class extends Component
{
    use WithPagination;
    
    public $search = '';
    public $perPage = 10;
    
    protected $paginationTheme = 'bootstrap';
    
    public function deleteProduct($id)
    {
        $product = Product::find($id);
        if ($product) {
            // Delete image if exists
            if ($product->image && file_exists(storage_path('app/public/' . $product->image))) {
                unlink(storage_path('app/public/' . $product->image));
            }
            $product->delete();
            session()->flash('message', 'Product deleted successfully!');
            session()->flash('message_type', 'success');
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $query = Product::with(['category', 'brand', 'supplier']);
        
        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
        }
        
        $products = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        
        return $this->view([
            'products' => $products
        ]);
    }
}
?>

<div>
    <!-- Alert Message -->
    @if(session()->has('message'))
        <div class="alert alert-{{ session('message_type', 'success') }} alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Products List</h4>
                            <div class="d-flex gap-2">
                                <div class="input-group" style="width: 300px;">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model.live.debounce.300ms="search" 
                                           placeholder="Search products...">
                                </div>
                                <select class="form-select" style="width: 100px;" wire:model.live="perPage">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Product</th>
                                        <th width="15%">SKU</th>
                                        <th width="12%">Category</th>
                                        <th width="10%">Quantity</th>
                                        <th width="13%">Price</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $index => $product)
                                    <tr>
                                        <td>{{ $products->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    @if($product->image)
                                                        <img src="{{ asset('storage/' . $product->image) }}" 
                                                             alt="{{ $product->name }}"
                                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd;">
                                                    @else
                                                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; border: 2px solid #ddd;">
                                                            <i class="bi bi-box" style="font-size: 24px; color: #6c757d;"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $product->name }}</div>
                                                    @if($product->description)
                                                        <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $product->sku }}</span>
                                        </td>
                                        <td>
                                            @if($product->category)
                                                <span class="badge bg-info">{{ $product->category->title ?? 'N/A' }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $product->quantity > 10 ? 'success' : ($product->quantity > 0 ? 'warning' : 'danger') }}">
                                                {{ $product->quantity }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="fw-bold text-success">₹{{ number_format($product->sale_price, 2) }}</span>
                                                <br>
                                                <small class="text-muted text-decoration-line-through">₹{{ number_format($product->purchase_price, 2) }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('products.show',$product->id)}}">
                                                <button class="btn btn-sm btn-info" 
                                                        title="View Product">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                </a>
                                                 <a href="{{ route('products.edit',$product->id)}}">
                                                <button class="btn btn-sm btn-warning" 
                                                        title="View Product">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                </a>
                                                <button class="btn btn-sm btn-danger" 
                                                        wire:click="deleteProduct({{ $product->id }})"
                                                        wire:confirm="Are you sure you want to delete '{{ $product->name }}'?"
                                                        title="Delete Product">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-box-seam fs-1 text-muted"></i>
                                            <p class="text-muted mt-2 mb-0">No products found</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                            </div>
                            <div>
                                {{ $products->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>