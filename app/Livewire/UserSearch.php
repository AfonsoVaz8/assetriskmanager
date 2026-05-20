<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;

class UserSearch extends Component
{
    public $searchTerm = '';
    public $users = [];
    public $showDropdown = false;

    public $selectedManagerId = null;

    public function updatedSearchTerm()
    {
        if (strlen($this->searchTerm) >= 2) {
            $this->users = User::where('name', 'like', '%' . $this->searchTerm . '%')
                ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
                ->get();
            $this->showDropdown = true;
        } else {
            $this->users = [];
            $this->showDropdown = false;
        }
    }

    public function selectUser($id, $name, $email) {
        $this->selectedManagerId = $id;
        $this->searchTerm = $name . ' (' . $email . ')';
        $this->showDropdown = false;
    }

    public function clearSelection() {
        $this->selectedManagerId = null;
        $this->searchTerm = '';
        $this->users = collect();
    }

    public function render()
    {
        return view('livewire.user-search');
    }
}
