<?php

namespace App\Livewire;

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class UserSearch extends Component
{
    use AuthorizesRequests;

    public $users;
    public $searchTerm = '';
    public $selectedManagerId;
    public $showDropdown = false;

    public function mount($selectedManagerId = null)
    {
        $this->selectedManagerId = $selectedManagerId;
        $this->users = collect();

        if ($this->selectedManagerId) {
            $user = User::find($this->selectedManagerId);
            if ($user) {
                // Formata o nome para ficar bonito no input
                $this->searchTerm = $user->name . ' (' . $user->email . ')';
            }
        }
    }

    public function updatedSearchTerm()
    {
        $this->selectedManagerId = null;
        $this->showDropdown = true;

        if (strlen($this->searchTerm) >= 2) {
            $this->users = UserController::filterUser($this->searchTerm)->take(10)->get();
        } else {
            $this->users = collect();
            $this->showDropdown = false;
        }
    }

    public function selectUser($userId, $userName, $userEmail)
    {
        $this->selectedManagerId = $userId;
        $this->searchTerm = $userName . ' (' . $userEmail . ')';
        $this->showDropdown = false;
        $this->users = collect();

        $this->dispatch('managerSelected', $this->selectedManagerId);
    }

    public function render()
    {
        $this->authorize('viewAny', User::class);
        return view('livewire.user-search');
    }
}