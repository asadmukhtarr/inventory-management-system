<?php

use Livewire\Component;

new class extends Component
{
    public $categories = [];
    public $title = '';
    public $editId = null;
    public $editTitle = '';
    public $showModal = false;
    public $showEditModal = false;
    
    public function mount()
    {
        // Static categories data
        $this->categories = [
            ['id' => 1, 'title' => 'Electronics', 'created_at' => '2024-01-10', 'product_count' => 45],
            ['id' => 2, 'title' => 'Clothing', 'created_at' => '2024-01-12', 'product_count' => 78],
            ['id' => 3, 'title' => 'Books', 'created_at' => '2024-01-15', 'product_count' => 112],
            ['id' => 4, 'title' => 'Home & Garden', 'created_at' => '2024-01-18', 'product_count' => 34],
            ['id' => 5, 'title' => 'Sports', 'created_at' => '2024-01-20', 'product_count' => 56],
            ['id' => 6, 'title' => 'Toys', 'created_at' => '2024-01-22', 'product_count' => 89],
            ['id' => 7, 'title' => 'Automotive', 'created_at' => '2024-01-25', 'product_count' => 23],
            ['id' => 8, 'title' => 'Health & Beauty', 'created_at' => '2024-01-28', 'product_count' => 67],
        ];
    }
    
    public function createCategory()
    {
        $this->validate([
            'title' => 'required|min:2|max:50'
        ]);
        
        $newId = count($this->categories) + 1;
        
        $this->categories[] = [
            'id' => $newId,
            'title' => $this->title,
            'created_at' => date('Y-m-d'),
            'product_count' => 0
        ];
        
        $this->title = '';
        $this->showModal = false;
        
        session()->flash('message', 'Category created successfully!');
    }
    
    public function editCategory($id)
    {
        $category = collect($this->categories)->firstWhere('id', $id);
        if ($category) {
            $this->editId = $id;
            $this->editTitle = $category['title'];
            $this->showEditModal = true;
        }
    }
    
    public function updateCategory()
    {
        $this->validate([
            'editTitle' => 'required|min:2|max:50'
        ]);
        
        foreach ($this->categories as $key => $category) {
            if ($category['id'] == $this->editId) {
                $this->categories[$key]['title'] = $this->editTitle;
                break;
            }
        }
        
        $this->editId = null;
        $this->editTitle = '';
        $this->showEditModal = false;
        
        session()->flash('message', 'Category updated successfully!');
    }
    
    public function deleteCategory($id)
    {
        $this->categories = array_filter($this->categories, function($category) use ($id) {
            return $category['id'] != $id;
        });
        
        $this->categories = array_values($this->categories);
        
        session()->flash('message', 'Category deleted successfully!');
    }
    
    public function openModal()
    {
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->title = '';
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editId = null;
        $this->editTitle = '';
    }
};

?>

<div>
    <style>
        .category-card {
            transition: all 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .modal-backdrop-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
        }
        .modal-fixed {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1050;
            max-width: 500px;
            width: 90%;
        }
    </style>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-tags me-2"></i>Categories
            </h3>
            <p class="text-muted mb-0">Manage your product categories</p>
        </div>
        <button class="btn btn-primary" onclick="Livewire.dispatch('openModal')">
            <i class="bi bi-plus-circle me-2"></i>Add Category
        </button>
    </div>

    <!-- Alert Message -->
    @if(session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Categories</h6>
                            <h2 class="mb-0 fw-bold">{{ count($categories) }}</h2>
                        </div>
                        <i class="bi bi-tags stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Products</h6>
                            <h2 class="mb-0 fw-bold">{{ collect($categories)->sum('product_count') }}</h2>
                        </div>
                        <i class="bi bi-box-seam stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Recent Added</h6>
                            <h2 class="mb-0 fw-bold">{{ count(array_slice($categories, -3)) }}</h2>
                        </div>
                        <i class="bi bi-clock-history stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="row g-4">
        @foreach($categories as $category)
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card category-card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="bi bi-folder fs-4 text-primary"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-link text-dark p-0" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical fs-5"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="Livewire.dispatch('editCategory', [{{ $category['id'] }}])">
                                        <i class="bi bi-pencil me-2"></i>Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="if(confirm('Are you sure you want to delete this category?')) Livewire.dispatch('deleteCategory', [{{ $category['id'] }}])">
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">{{ $category['title'] }}</h5>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-calendar3 me-1"></i> Created: {{ \Carbon\Carbon::parse($category['created_at'])->format('M d, Y') }}
                    </p>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-box"></i> {{ $category['product_count'] }} Products
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Create Category Modal -->
    @if($showModal)
    <div class="modal-backdrop-fixed" onclick="Livewire.dispatch('closeModal')"></div>
    <div class="modal-fixed">
        <div class="card border-0 shadow-lg">
            <div class="card-header bg-white border-0 pt-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" onclick="Livewire.dispatch('closeModal')"></button>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="createCategory">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Title</label>
                        <input type="text" 
                               class="form-control form-control-lg @error('title') is-invalid @enderror" 
                               wire:model="title"
                               placeholder="Enter category name">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-check-circle me-2"></i>Create Category
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="Livewire.dispatch('closeModal')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Category Modal -->
    @if($showEditModal)
    <div class="modal-backdrop-fixed" onclick="Livewire.dispatch('closeEditModal')"></div>
    <div class="modal-fixed">
        <div class="card border-0 shadow-lg">
            <div class="card-header bg-white border-0 pt-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" onclick="Livewire.dispatch('closeEditModal')"></button>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="updateCategory">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Title</label>
                        <input type="text" 
                               class="form-control form-control-lg @error('editTitle') is-invalid @enderror" 
                               wire:model="editTitle"
                               placeholder="Enter category name">
                        @error('editTitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save me-2"></i>Update Category
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="Livewire.dispatch('closeEditModal')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>