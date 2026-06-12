<?php

use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use App\Models\brand;

new class extends Component
{
    //
    use WithFileUploads;
    public $title;
    public $description;
    public $logo;
    public $brands;
    public $brand;
    public $status;
    protected $rules = [
        'title' => 'required',
        'description' => 'required'
    ];

    public function mount(){
        $this->brands = brand::orderby('id','desc')->get();
       // dd($this->brands);
    }

    public function save(){
        $this->validate(); 
        $imageName = time() .".".$this->logo->getClientOriginalExtension();
        $this->logo->storeAs('brands',$imageName,'public');
        $brand = new brand;
        $brand->title = $this->title;
        $brand->description = $this->description;
        $brand->slug = Str::slug($this->title);
        $brand->logo = "brands/".$imageName;
        $brand->save();
        // Reset fields after save
        $this->reset(['title', 'description', 'logo']);
        session()->flash('success','Brand Added Succesfully');
    }
    public function update($id){
        $this->validate(); 
        $brand = brand::find($id);
        $brand->title = $this->title;
        $brand->description = $this->description;
        $brand->status = $this->status;
        $brand->slug = Str::slug($this->title);
        if($this->logo){
            $imageName = time() .".".$this->logo->getClientOriginalExtension();
            $this->logo->storeAs('brands',$imageName,'public');
            $brand->logo = "brands/".$imageName;
        }
        $brand->save();
        $this->brands = brand::orderby('id','desc')->get();

        // Reset fields after save
        $this->reset(['title', 'description', 'logo','brand']);
        session()->flash('success','Brand Updated Succesfully');
    }
    public function edit($id){
        $this->brand = brand::find($id);
        $this->title = $this->brand->title;
        $this->description = $this->brand->description;
        //dd($this->brand);
    }

    public function delete($id){
        $brand = brand::find($id);
        //dd($brand);
        $brand->delete();
        session()->flash('warning','Brand Deleted Succesfully');
        $this->brands = brand::orderby('id','desc')->get();

    }
};
?>

<div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card">
                    @if(empty($brand))
                    <form wire:submit="save">
                        <div class="card-header">
                            <label for="">Create New Brand</label>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <input type="file" wire:model="logo">
                                @error('logo') <span class="error">{{ $message }}</span> @enderror
                                @if ($logo) 
                                    <img src="{{ $logo->temporaryUrl() }}" height="40px">
                                @endif 
                            </div>
                            <div class="form-group">
                                <label for="">Title</label>
                                <input type="text" class="form-control @error('title') is-invalid @endif" wire:model="title" />
                                @error('title')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Description</label>
                                <textarea class="form-control @error('description') is-invalid @endif" wire:model="description" id=""></textarea>
                                @error('description')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fa fa-spinner fa-spin" wire:loading></i> <i class="bi bi-save" wire:loading.remove></i> Save
                            </button>
                        </div>
                    </form>
                    @else 
                     <form wire:submit="update({{ $brand->id }})">
                        <div class="card-header">
                            <label for="">Edit {{ $brand->title }}</label>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <input type="file" wire:model="logo">
                                @error('logo') <span class="error">{{ $message }}</span> @enderror
                                @if ($logo) 
                                    <img src="{{ $logo->temporaryUrl() }}" height="40px">
                                @endif 
                                <img src="{{ asset('storage/') }}/{{ $brand->logo }}" class="rounded-circle" width="60" height="60"  />
                            </div>
                            <div class="form-group">
                                <label for="">Title</label>
                                <input type="text" class="form-control @error('title') is-invalid @endif" wire:model="title" />
                                @error('title')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="">Description</label>
                                <textarea class="form-control @error('description') is-invalid @endif" wire:model="description" id=""></textarea>
                                @error('description')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                             <div class="form-group">
                                <label for="">Status</label>
                                <select name="" id="" wire:model="status" class="form-control">
                                    <option value="Active" @if($brand->status == "active") selected @endif>Active</option>
                                    <option value="Inactive" @if($brand->status == "inactive") selected @endif>Inactive</option>
                                </select>
                                @error('status')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-spinner fa-spin" wire:loading></i> <i class="bi bi-save" wire:loading.remove></i> Update
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-danger">{{ session('warning') }}</div>
            @endif
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-list"></i> Brands
                </div>
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    @foreach($brands as $brand)
                    <tr>
                        <td>
                            <img src="{{ asset('storage/') }}/{{ $brand->logo }}" class="rounded-circle" width="40" height="40"  />
                            {{ $brand->title }}
                        </td>
                        <td>
                            @if($brand->status == 'active')
                                <span class="badge bg-success">
                                    <i class="fa fa-check-circle"></i> Active
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fa fa-times-circle"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" wire:click="edit({{ $brand->id }})"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" wire:click="delete({{ $brand->id }})" wire:confirm="Are you sure? You want to delete brand."><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>