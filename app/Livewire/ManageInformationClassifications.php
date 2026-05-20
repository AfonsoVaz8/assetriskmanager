<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\InformationClassification;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;

class ManageInformationClassifications extends Component
{
    public $name;
    public $level;
    public $editingId = null;
    public $confirmingDeletion = false;
    public $idToDelete = null;


    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer',
        ]);

        if ($this->editingId) {
            $classification = InformationClassification::find($this->editingId);
            $classification->update(['name' => $this->name, 'level' => $this->level]);
        } else {
            InformationClassification::create(['name' => $this->name, 'level' => $this->level]);
        }

        $this->reset(['name', 'level', 'editingId']);
    }

    public function edit($id)
    {
        $classification = InformationClassification::find($id);
        $this->name = $classification->name;
        $this->level = $classification->level;
        $this->editingId = $classification->id;
    }

        public function confirmDeletion($id)
    {
        $this->idToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function delete()
    {
        if ($this->idToDelete) {
            InformationClassification::destroy($this->idToDelete);
            $this->confirmingDeletion = false;
            $this->idToDelete = null;
        }
    }

    public function render()
    {
        return view('livewire.manage-information-classifications', [
            'classifications' => InformationClassification::orderBy('level', 'desc')->get(),
        ])->layout('layouts.app');
    }

    public function validationAttributes()
    {
        return [
            'name' => __('nome'),
            'level' => __('nível'),
            'score' => __('pontuação'),
        ];
    }
    public function mount()
{
    $role = Auth::user()->role;
    $isSecurityOfficer = $role === UserRole::SECURITY_OFFICER || (is_scalar($role) && $role === UserRole::SECURITY_OFFICER->value);

    abort_if(!$isSecurityOfficer, 403, 'Acesso Negado. Apenas o Security Officer pode aceder a esta página.');
}
}
