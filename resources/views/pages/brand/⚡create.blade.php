<?php

use Livewire\Component;

new class extends Component
{
    //
    public $title;
    public $description;
    public $logo;
    protected $rules = [
        'title'=> 'required',
        'description' => 'required'
    ];

    public function save(){
        $this->validate();  
        dd($this->title);
    }
};
?>

<div>
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card">
                    <form wire:submit="save">
                    <div class="card-header">
                        <label for="">Create New Brand</label>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Title</label>
                            <input type="text" class="form-control @error('title') is-invalid @endif" wire:model="title" />
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea class="form-control @error('description') is-invalid @endif" wire:model="description" id=""></textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-save"></i> Save
                        </button>
                        <span wire:loading>Loading ...</span>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>