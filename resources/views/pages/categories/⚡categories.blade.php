<?php

use Livewire\Component;
use App\Models\Category;

new class extends Component
{
    public $categories = [];
    public $title = '';
    public $editId = null;
    public $editTitle = '';
    public $editStatus = '';
    public $showModal = false;
    public $showEditModal = false;
    public $search = '';
    
    public function mount()
    {
        $this->loadCategories();
    }
    
    public function loadCategories()
    {
        $query = Category::query();
        
        if (!empty($this->search)) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }
        
        $this->categories = $query->orderBy('created_at', 'desc')->get()->toArray();
    }
    
    public function updatedSearch()
    {
        $this->loadCategories();
    }
    
    public function createCategory()
    {
        $this->validate([
            'title' => 'required|min:2|max:50|unique:categories,title'
        ]);
        
        Category::create([
            'title' => $this->title,
            'status' => 'active'
        ]);
        
        $this->title = '';
        $this->showModal = false;
        $this->loadCategories();
        
        session()->flash('message', 'Category created successfully!');
        session()->flash('message_type', 'success');
    }
    
    public function editCategory($id)
    {
        $category = Category::find($id);
        if ($category) {
            $this->editId = $id;
            $this->editTitle = $category->title;
            $this->editStatus = $category->status;
            $this->showEditModal = true;
        }
    }
    
    public function updateCategory()
    {
        $this->validate([
            'editTitle' => 'required|min:2|max:50|unique:categories,title,' . $this->editId,
            'editStatus' => 'required|in:active,inactive'
        ]);
        
        $category = Category::find($this->editId);
        if ($category) {
            $category->update([
                'title' => $this->editTitle,
                'status' => $this->editStatus
            ]);
        }
        
        $this->editId = null;
        $this->editTitle = '';
        $this->editStatus = '';
        $this->showEditModal = false;
        $this->loadCategories();
        
        session()->flash('message', 'Category updated successfully!');
        session()->flash('message_type', 'success');
    }
    
    public function deleteCategory($id)
    {
        $category = Category::find($id);
        if ($category) {
            $category->delete();
            $this->loadCategories();
            
            session()->flash('message', 'Category deleted successfully!');
            session()->flash('message_type', 'success');
        }
    }
    
    public function openModal()
    {
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->title = '';
        $this->resetErrorBag();
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editId = null;
        $this->editTitle = '';
        $this->editStatus = '';
        $this->resetErrorBag();
    }
};

?>

<div>
    <style>
        .form-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .table-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: none;
        }
        
        .table-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .action-btns .btn {
            margin: 0 3px;
            transition: all 0.3s ease;
        }
        
        .action-btns .btn:hover {
            transform: scale(1.05);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 25px;
            border: 1px solid #e0e0e0;
            background: white;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #999;
        }
        
        .stats-row {
            margin-bottom: 20px;
        }
        
        .stat-badge {
            background: white;
            border-radius: 10px;
            padding: 10px 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-custom {
            animation: slideIn 0.3s ease;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            min-width: 300px;
        }
        
        .form-control:focus, .btn:focus {
            box-shadow: none;
        }
    </style>

    <!-- Alert Message -->
    @if(session()->has('message'))
        <div class="alert alert-{{ session('message_type', 'success') }} alert-custom alert-dismissible fade show" role="alert">
            <i class="bi bi-{{ session('message_type', 'success') == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }} me-2"></i>
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="container-fluid px-4 py-4">
        <div class="row g-4">
            <!-- Left Column - Form -->
            <div class="col-lg-4">
                <div class="form-card p-4 text-white">
                    <div class="text-center mb-4">
                        <i class="bi bi-tags fs-1"></i>
                        <h3 class="mt-2 mb-0">Category Manager</h3>
                        <p class="opacity-75 small">Add new categories to your collection</p>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-badge text-dark">
                            <div class="text-center">
                                <small class="text-muted">Total Categories</small>
                                <h4 class="mb-0 fw-bold">{{ count($categories) }}</h4>
                            </div>
                        </div>
                    </div>
                    
                    <form wire:submit.prevent="createCategory">
                        <div class="mb-3">
                            <label class="form-label text-white fw-bold">
                                <i class="bi bi-pencil-square me-2"></i>Category Name
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('title') is-invalid @enderror" 
                                   wire:model="title"
                                   placeholder="Enter category name..."
                                   style="border-radius: 10px;">
                            @error('title')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-light w-100 fw-bold py-2" style="border-radius: 10px;">
                            <i class="bi bi-plus-circle me-2"></i>
                            Create New Category
                        </button>
                    </form>
                    
                    <div class="mt-4 pt-3 border-top border-white border-opacity-25">
                        <div class="text-center small opacity-75">
                            <i class="bi bi-info-circle"></i> Fill in the form above to add new categories
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Table -->
            <div class="col-lg-8">
                <div class="card table-card">
                    <div class="card-header text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-table me-2"></i>
                                <strong>Categories List</strong>
                            </div>
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" 
                                       class="form-control form-control-sm" 
                                       wire:model.live.debounce.300ms="search"
                                       placeholder="Search categories..."
                                       style="width: 250px;">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="35%">Category Name</th>
                                        <th width="20%">Created Date</th>
                                        <th width="15%">Status</th>
                                        <th width="25%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $index => $category)
                                    <tr>
                                        <td>
                                            <i class="bi bi-folder-fill text-primary me-2"></i>
                                            <strong>{{ $category['title'] }}</strong>
                                        </td>
                                        <td>
                                            <small>
                                            <i class="bi bi-calendar3 text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($category['created_at'])->diffforhumans(); }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $category['status'] }}">
                                                <i class="bi bi-{{ $category['status'] == 'active' ? 'check-circle' : 'x-circle' }} me-1"></i>
                                            </span>
                                        </td>
                                        <td class="action-btns">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    wire:click="editCategory({{ $category['id'] }})"
                                                    title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    wire:click="deleteCategory({{ $category['id'] }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $category['title'] }}' category?"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <i class="bi bi-inbox fs-1 text-muted"></i>
                                            <p class="text-muted mt-2 mb-0">No categories found</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Showing {{ count($categories) }} {{ Str::plural('category', count($categories)) }}
                            </small>
                            <button class="btn btn-sm btn-primary" wire:click="openModal">
                                <i class="bi bi-plus-lg"></i> Quick Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Category Modal -->
    @if($showModal)
    <div class="modal-backdrop-fixed" wire:click="closeModal"></div>
    <div class="modal-fixed">
        <div class="card border-0 shadow-lg" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 pt-4" style="border-radius: 15px 15px 0 0;">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-plus-circle text-primary me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="createCategory">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Title</label>
                        <input type="text" 
                               class="form-control form-control-lg @error('title') is-invalid @enderror" 
                               wire:model="title"
                               placeholder="Enter category name"
                               style="border-radius: 10px;">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1 py-2" style="border-radius: 10px;">
                            <i class="bi bi-check-circle me-2"></i>Create Category
                        </button>
                        <button type="button" class="btn btn-secondary px-4" wire:click="closeModal" style="border-radius: 10px;">
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
    <div class="modal-backdrop-fixed" wire:click="closeEditModal"></div>
    <div class="modal-fixed">
        <div class="card border-0 shadow-lg" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 pt-4" style="border-radius: 15px 15px 0 0;">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pencil-square text-warning me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="updateCategory">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Title</label>
                        <input type="text" 
                               class="form-control form-control-lg @error('editTitle') is-invalid @enderror" 
                               wire:model="editTitle"
                               placeholder="Enter category name"
                               style="border-radius: 10px;">
                        @error('editTitle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-select form-select-lg @error('editStatus') is-invalid @enderror" 
                                wire:model="editStatus"
                                style="border-radius: 10px;">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('editStatus')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1 py-2" style="border-radius: 10px;">
                            <i class="bi bi-save me-2"></i>Update Category
                        </button>
                        <button type="button" class="btn btn-secondary px-4" wire:click="closeEditModal" style="border-radius: 10px;">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>