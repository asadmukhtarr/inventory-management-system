<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Supplier;

new class extends Component
{
    use WithPagination;
    
    // Form fields
    public $name, $email, $phone, $address, $contact_person, $company_name, $balance, $status;
    
    // Edit properties
    public $editId = null;
    public $editName, $editEmail, $editPhone, $editAddress, $editContactPerson, $editCompanyName, $editBalance, $editStatus;
    
    // Modal controls
    public $showModal = false;
    public $showEditModal = false;
    
    // Search
    public $search = '';
    
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
        $this->contact_person = '';
        $this->company_name = '';
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
        $this->editContactPerson = '';
        $this->editCompanyName = '';
        $this->editBalance = 0;
        $this->editStatus = 'active';
    }
    
    public function createSupplier()
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:100',
            'balance' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive'
        ]);
        
        Supplier::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'company_name' => $this->company_name,
            'balance' => $this->balance ?? 0,
            'status' => $this->status
        ]);
        
        $this->resetForm();
        $this->showModal = false;
        session()->flash('message', 'Supplier created successfully!');
        session()->flash('message_type', 'success');
    }
    
    public function editSupplier($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $this->editId = $id;
            $this->editName = $supplier->name;
            $this->editEmail = $supplier->email;
            $this->editPhone = $supplier->phone;
            $this->editAddress = $supplier->address;
            $this->editContactPerson = $supplier->contact_person;
            $this->editCompanyName = $supplier->company_name;
            $this->editBalance = $supplier->balance;
            $this->editStatus = $supplier->status;
            $this->showEditModal = true;
        }
    }
    
    public function updateSupplier()
    {
        $this->validate([
            'editName' => 'required|string|max:100',
            'editEmail' => 'required|email|unique:suppliers,email,' . $this->editId,
            'editPhone' => 'required|string|max:20',
            'editAddress' => 'nullable|string',
            'editContactPerson' => 'nullable|string|max:100',
            'editCompanyName' => 'nullable|string|max:100',
            'editBalance' => 'nullable|numeric|min:0',
            'editStatus' => 'required|in:active,inactive'
        ]);
        
        $supplier = Supplier::find($this->editId);
        if ($supplier) {
            $supplier->update([
                'name' => $this->editName,
                'email' => $this->editEmail,
                'phone' => $this->editPhone,
                'address' => $this->editAddress,
                'contact_person' => $this->editContactPerson,
                'company_name' => $this->editCompanyName,
                'balance' => $this->editBalance ?? 0,
                'status' => $this->editStatus
            ]);
        }
        
        $this->resetEditForm();
        $this->showEditModal = false;
        session()->flash('message', 'Supplier updated successfully!');
        session()->flash('message_type', 'success');
    }
    
    public function deleteSupplier($id)
    {
        $supplier = Supplier::find($id);
        if ($supplier) {
            $supplier->delete();
            session()->flash('message', 'Supplier deleted successfully!');
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
        $query = Supplier::query();
        
        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('company_name', 'like', '%' . $this->search . '%');
        }
        
        $suppliers = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return $this->view([
            'suppliers' => $suppliers
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
                            <h4 class="mb-0">Supplier Management</h4>
                            <div>
                                <button class="btn btn-primary" wire:click="openModal">
                                    <i class="bi bi-plus-circle"></i> Add New Supplier
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Search -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           wire:model.live.debounce.300ms="search" 
                                           placeholder="Search by name, email, phone or company...">
                                </div>
                            </div>
                        </div>
                    </div>
                        <!-- Suppliers Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">Name</th>
                                        <th width="15%">Email</th>
                                        <th width="15%">Company</th>
                                        <th width="10%">Balance</th>
                                        <th width="8%">Status</th>
                                        <th width="10%">Created</th>
                                        <th width="12%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($suppliers as $index => $supplier)
                                    <tr>
                                        <td>{{ $suppliers->firstItem() + $index }}</td>
                                        <td>
                                            <strong>{{ $supplier->name }}</strong><br>
                                            <small class="text-muted">Contact: {{ $supplier->contact_person ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ $supplier->email }}</td>
                                        <td>{{ $supplier->company_name ?? 'N/A' }}</td>
                                        <td class="text-end">${{ number_format($supplier->balance, 2) }}</td>
                                        <td>
                                            @if($supplier->status == 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>{{ $supplier->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-info" 
                                                    wire:click="editSupplier({{ $supplier->id }})"
                                                    title="Edit">
                                                <i class="bi bi-pencil"></i> 
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    wire:click="deleteSupplier({{ $supplier->id }})"
                                                    wire:confirm="Are you sure you want to delete '{{ $supplier->name }}'?"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i> 
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <i class="bi bi-inbox"></i> No suppliers found
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    <div class="card-body">
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $suppliers->firstItem() }} to {{ $suppliers->lastItem() }} of {{ $suppliers->total() }} suppliers
                            </div>
                            <div>
                                {{ $suppliers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Supplier Modal -->
    @if($showModal)
    <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Add New Supplier
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="createSupplier">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       wire:model="name">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       wire:model="email">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="text" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       wire:model="phone">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" 
                                       class="form-control" 
                                       wire:model="contact_person">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       wire:model="company_name">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Balance</label>
                                <input type="number" step="0.01" 
                                       class="form-control" 
                                       wire:model="balance">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" wire:model="address"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" wire:model="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Supplier Modal -->
    @if($showEditModal)
    <div class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Edit Supplier
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="updateSupplier">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" 
                                       class="form-control @error('editName') is-invalid @enderror" 
                                       wire:model="editName">
                                @error('editName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" 
                                       class="form-control @error('editEmail') is-invalid @enderror" 
                                       wire:model="editEmail">
                                @error('editEmail')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone *</label>
                                <input type="text" 
                                       class="form-control @error('editPhone') is-invalid @enderror" 
                                       wire:model="editPhone">
                                @error('editPhone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person</label>
                                <input type="text" 
                                       class="form-control" 
                                       wire:model="editContactPerson">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       wire:model="editCompanyName">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Balance</label>
                                <input type="number" step="0.01" 
                                       class="form-control" 
                                       wire:model="editBalance">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" wire:model="editAddress"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-control" wire:model="editStatus">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" wire:click="closeEditModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Supplier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>