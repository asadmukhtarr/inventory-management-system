<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Customer;

new class extends Component
{
    use WithPagination;
    
    // Form fields
    public $name;
    public $email;
    public $phone;
    public $address;
    public $balance = 0;
    public $status = 'active';
    
    // Edit properties
    public $editId = null;
    public $editName;
    public $editEmail;
    public $editPhone;
    public $editAddress;
    public $editBalance;
    public $editStatus;
    
    // Modal controls
    public $showModal = false;
    public $showEditModal = false;
    
    // Search
    public $search = '';
    public $perPage = 10;
    
    protected $paginationTheme = 'bootstrap';
    
    public function mount()
    {
        $this->resetForm();
    }
    
    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->balance = 0;
        $this->status = 'active';
    }
    
    public function resetEditForm()
    {
        $this->editId = null;
        $this->editName = '';
        $this->editEmail = '';
        $this->editPhone = '';
        $this->editAddress = '';
        $this->editBalance = 0;
        $this->editStatus = 'active';
    }
    
    public function saveCustomer()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive'
        ]);
        
        Customer::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'balance' => $this->balance ?? 0,
            'status' => $this->status
        ]);
        
        $this->resetForm();
        session()->flash('message', 'Customer created successfully!');
        session()->flash('message_type', 'success');
    }
    
    public function editCustomer($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $this->editId = $id;
            $this->editName = $customer->name;
            $this->editEmail = $customer->email;
            $this->editPhone = $customer->phone;
            $this->editAddress = $customer->address;
            $this->editBalance = $customer->balance;
            $this->editStatus = $customer->status;
            $this->showEditModal = true;
        }
    }
    
    public function updateCustomer()
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmail' => 'required|email|unique:customers,email,' . $this->editId,
            'editPhone' => 'required|string|max:20',
            'editAddress' => 'nullable|string',
            'editBalance' => 'nullable|numeric|min:0',
            'editStatus' => 'required|in:active,inactive'
        ]);
        
        $customer = Customer::find($this->editId);
        if ($customer) {
            $customer->update([
                'name' => $this->editName,
                'email' => $this->editEmail,
                'phone' => $this->editPhone,
                'address' => $this->editAddress,
                'balance' => $this->editBalance ?? 0,
                'status' => $this->editStatus
            ]);
        }
        
        $this->resetEditForm();
        $this->showEditModal = false;
        session()->flash('message', 'Customer updated successfully!');
        session()->flash('message_type', 'success');
    }
    
    public function deleteCustomer($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->delete();
            session()->flash('message', 'Customer deleted successfully!');
            session()->flash('message_type', 'success');
        }
    }
    
    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }
    
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetErrorBag();
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetEditForm();
        $this->resetErrorBag();
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        $query = Customer::query();
        
        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
        }
        
        $customers = $query->orderBy('created_at', 'desc')->paginate($this->perPage);
        
        return $this->view([
            'customers' => $customers
        ]);
    }
}
?>

<div>
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            padding: 15px;
            color: white;
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
            max-width: 600px;
            width: 90%;
        }
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .customer-avatar i {
            font-size: 20px;
            color: #6c757d;
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
    <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="stats-card">
                    <small>Total Customers</small>
                    <h4 class="mb-0">{{ $customers->total() }}</h4>
                </div>
            </div>
            <div class="col-6">
                <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <small>Active</small>
                    <h4 class="mb-0">{{ $customers->where('status', 'active')->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <!-- Left Column - Form -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-plus"></i> Add New Customer
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Stats -->
                        
                        <form wire:submit.prevent="saveCustomer">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Name *</label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       wire:model="name"
                                       placeholder="Enter customer name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email *</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       wire:model="email"
                                       placeholder="Enter email address">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone *</label>
                                <input type="text" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       wire:model="phone"
                                       placeholder="+92 300 1234567">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          wire:model="address" 
                                          rows="2"
                                          placeholder="Enter address"></textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Balance</label>
                                <input type="number" step="0.01" 
                                       class="form-control @error('balance') is-invalid @enderror" 
                                       wire:model="balance"
                                       placeholder="0.00">
                                @error('balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <select class="form-control @error('status') is-invalid @enderror" 
                                        wire:model="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Save Customer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - List -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-people"></i> Customers List
                            </h5>
                            <div class="d-flex gap-2">
                                <div class="input-group" style="width: 200px;">
                                    <span class="input-group-text bg-white">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model.live.debounce.300ms="search" 
                                           placeholder="Search...">
                                </div>
                                <select class="form-select" style="width: 80px;" wire:model.live="perPage">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Name</th>
                                        <th width="10%">Balance</th>
                                        <th width="10%">Status</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customers as $index => $customer)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="customer-avatar me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $customer->name }}</div>
                                                    <small class="text-muted">{{ Str::limit($customer->address, 20) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">${{ number_format($customer->balance, 2) }}</td>
                                        <td>
                                            @if($customer->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    wire:click="editCustomer({{ $customer->id }})"
                                                    title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    wire:click="deleteCustomer({{ $customer->id }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $customer->name }}'?"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-people fs-1 text-muted"></i>
                                            <p class="text-muted mt-2 mb-0">No customers found</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers
                            </small>
                            <div>
                                {{ $customers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    @if($showEditModal)
    <div class="modal-backdrop-fixed" wire:click="closeEditModal"></div>
    <div class="modal-fixed">
        <div class="card">
            <div class="card-header bg-warning text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Edit Customer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeEditModal"></button>
                </div>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="updateCustomer">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name *</label>
                        <input type="text" 
                               class="form-control @error('editName') is-invalid @enderror" 
                               wire:model="editName">
                        @error('editName')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email *</label>
                        <input type="email" 
                               class="form-control @error('editEmail') is-invalid @enderror" 
                               wire:model="editEmail">
                        @error('editEmail')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone *</label>
                        <input type="text" 
                               class="form-control @error('editPhone') is-invalid @enderror" 
                               wire:model="editPhone">
                        @error('editPhone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea class="form-control @error('editAddress') is-invalid @enderror" 
                                  wire:model="editAddress" 
                                  rows="2"></textarea>
                        @error('editAddress')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Balance</label>
                        <input type="number" step="0.01" 
                               class="form-control @error('editBalance') is-invalid @enderror" 
                               wire:model="editBalance">
                        @error('editBalance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-control @error('editStatus') is-invalid @enderror" 
                                wire:model="editStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('editStatus')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-save"></i> Update Customer
                        </button>
                        <button type="button" class="btn btn-secondary" wire:click="closeEditModal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>