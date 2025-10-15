<?php

namespace App\Livewire;

use App\Models\UserProfile;
use Livewire\Component;

class DataManager extends Component
{
    public $profiles = [];
    public $selectedProfile = null;
    public $dataFields = [];
    public $editingField = null;
    public $fieldKey = '';
    public $fieldValue = '';
    public $fieldType = 'text';

    protected $listeners = ['profileUpdated' => 'loadProfiles'];

    public function mount()
    {
        $this->loadProfiles();
    }

    public function loadProfiles()
    {
        $this->profiles = UserProfile::where('user_id', auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function selectProfile($profileId)
    {
        $profile = UserProfile::find($profileId);
        if ($profile && $profile->user_id === auth()->id()) {
            $this->selectedProfile = $profile->toArray();
            $this->dataFields = $profile->data ?? [];
        }
    }

    public function editField($key)
    {
        $this->editingField = $key;
        $this->fieldKey = $key;
        $this->fieldValue = $this->dataFields[$key] ?? '';
        $this->fieldType = $this->detectFieldType($this->fieldValue);
    }

    public function saveField()
    {
        if (!$this->selectedProfile) return;

        $this->validate([
            'fieldKey' => 'required|string|max:255',
            'fieldValue' => 'nullable|string',
        ]);

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $data = $profile->data ?? [];
            $data[$this->fieldKey] = $this->fieldValue;
            $profile->data = $data;
            $profile->save();

            $this->dataFields = $data;
            $this->resetFieldForm();
            session()->flash('message', 'Field updated successfully!');
        }
    }

    public function deleteField($key)
    {
        if (!$this->selectedProfile) return;

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $data = $profile->data ?? [];
            unset($data[$key]);
            $profile->data = $data;
            $profile->save();

            $this->dataFields = $data;
            session()->flash('message', 'Field deleted successfully!');
        }
    }

    public function addNewField()
    {
        $this->editingField = 'new';
        $this->fieldKey = '';
        $this->fieldValue = '';
        $this->fieldType = 'text';
    }

    public function resetFieldForm()
    {
        $this->editingField = null;
        $this->fieldKey = '';
        $this->fieldValue = '';
        $this->fieldType = 'text';
    }

    public function cancelEdit()
    {
        $this->resetFieldForm();
    }

    private function detectFieldType($value)
    {
        if (!is_string($value)) {
            if (is_array($value)) {
                return 'array';
            }
            if (is_object($value)) {
                return 'object';
            }
            if (is_bool($value)) {
                return 'boolean';
            }
            if (is_numeric($value)) {
                return 'number';
            }
            return 'unknown';
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return 'date';
        }
        if (preg_match('/^\d+$/', $value)) {
            return 'number';
        }
        return 'text';
    }

    public function render()
    {
        return view('livewire.data-manager');
    }
}
