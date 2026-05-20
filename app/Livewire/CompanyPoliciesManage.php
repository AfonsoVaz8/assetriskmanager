<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CompanyPolicy;
use Illuminate\Support\Facades\Storage;

class CompanyPoliciesManage extends Component
{
    use WithFileUploads;

    public $description;
    public $document;

    protected $rules = [
        'description' => 'required|string|max:255',
        'document' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
    ];

    public function save()
    {
        $this->validate();

        $path = $this->document->store('policies', 'public');

        CompanyPolicy::create([
            'description' => $this->description,
            'file_path' => $path,
            'original_filename' => $this->document->getClientOriginalName(),
        ]);

        $this->reset(['description', 'document']);
        session()->flash('message', 'Política adicionada com sucesso!');
    }

    public function deletePolicy($id)
    {
        $policy = CompanyPolicy::findOrFail($id);

        if (Storage::disk('public')->exists($policy->file_path)) {
            Storage::disk('public')->delete($policy->file_path);
        }

        $policy->delete();
    }

    public function render()
    {
        return view('livewire.company-policies-manage', [
            'policies' => CompanyPolicy::latest()->get()
        ]);
    }
}
