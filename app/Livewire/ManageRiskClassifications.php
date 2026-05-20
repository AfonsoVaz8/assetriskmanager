<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RiskClassification;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

class ManageRiskClassifications extends Component
{
    public $name;
    public $score;
    public $editingId = null;
    public $confirmingDeletion = false;
    public $idToDelete = null;

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'score' => 'required|integer',
        ]);

        if ($this->editingId) {
            $classification = RiskClassification::find($this->editingId);
            $classification->update(['name' => $this->name, 'score' => $this->score]);
        } else {
            RiskClassification::create(['name' => $this->name, 'score' => $this->score]);
        }

        $this->reset(['name', 'score', 'editingId']);
    }

    public function edit($id)
    {
        $classification = RiskClassification::find($id);
        $this->name = $classification->name;
        $this->score = $classification->score;
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
            RiskClassification::destroy($this->idToDelete);
            $this->confirmingDeletion = false;
            $this->idToDelete = null;
        }
    }

    public function render()
    {
        return view('livewire.manage-risk-classifications', [
            'classifications' => RiskClassification::orderBy('score', 'desc')->get(),
        ])->layout('layouts.app');
    }

    public function mount()
    {
        $role = Auth::user()->role;
        $isSecurityOfficer = $role === UserRole::SECURITY_OFFICER || (is_scalar($role) && $role === UserRole::SECURITY_OFFICER->value);

        abort_if(!$isSecurityOfficer, 403, 'Acesso Negado. Apenas o Security Officer pode aceder a esta página.');
    }
    }
