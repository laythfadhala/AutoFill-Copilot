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
    public $editingDocumentName = null; // For tracking which document group we're editing
    public $originalFieldKey = null; // For tracking original field key when editing extracted fields
    public $groupedDocuments = [];
    public $manualFields = [];
    public $activeTab = 'extracted'; // 'extracted' or 'manual'
    public $totalFieldCount = 0;
    public $expandedAccordions = [];

    protected $listeners = [
        'profileUpdated' => 'loadProfiles',
        'selectProfileFromManager' => 'selectProfileFromManager'
    ];

    public function mount()
    {
        $this->loadProfiles();

        // Check if a profile was selected from the profile manager
        if (session()->has('selected_profile_for_data_tab')) {
            $profileId = session('selected_profile_for_data_tab');
            // session()->forget('selected_profile_for_data_tab'); // Clear the session variable
            $this->selectProfile($profileId);
            $this->activeTab = 'extracted';
        }
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
        $this->editingField = null; // Reset any editing state
        $profile = UserProfile::find($profileId);
        if ($profile && $profile->user_id === auth()->id()) {
            $this->selectedProfile = $profile->toArray();
            session(['selected_profile_for_data_tab' => $profileId]); // Save to session for persistence
            $allData = $profile->data ?? [];

            // Separate document data from manual fields
            $this->groupedDocuments = [];
            $this->manualFields = [];

            foreach ($allData as $item) {
                if (is_array($item) && isset($item['extracted_data'])) {
                    // This is a document record
                    $extractedData = $item['extracted_data'];
                    $fileName = $item['filename'] ?? 'Unknown';
                    if (!isset($this->groupedDocuments[$fileName])) {
                        $this->groupedDocuments[$fileName] = [
                            'title' => $item['extracted_data']['Title'] ?? $fileName,
                            'documents' => [],
                            'fields' => []
                        ];
                    }
                    $this->groupedDocuments[$fileName]['documents'][] = $item;
                    // Add all fields except 'Title' to the grouped fields
                    foreach ($extractedData as $key => $value) {
                        if ($key !== 'Title' && $key !== 'error') {
                            $this->groupedDocuments[$fileName]['fields'][$key] = $value;
                        }
                    }
                } else {
                    // This is a manual field
                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $this->manualFields[$key] = $value;
                        }
                    }
                }
            }

            $this->calculateTotalFieldCount();
        }
    }

    private function calculateTotalFieldCount()
    {
        $this->totalFieldCount = count($this->manualFields);
        foreach ($this->groupedDocuments as $group) {
            $this->totalFieldCount += count($group['fields']);
        }
    }

    public function selectProfileFromManager($data)
    {
        $profileId = $data['profileId'];
        $this->selectProfile($profileId);
        $this->activeTab = 'extracted'; // Switch to extracted data tab
    }

    public function addNewField()
    {
        $this->resetFieldForm();
        $this->editingField = 'new';
        $this->activeTab = 'manual';
    }

    public function editManualField($key, $value)
    {
        $this->editingField = 'manual';
        $this->fieldKey = $key;
        $this->originalFieldKey = $key;
        $this->fieldValue = $value;
    }

    public function saveField()
    {
        if ($this->editingField === 'extracted') {
            $this->saveExtractedField();
        } else {
            $this->saveManualField();
        }
    }

    private function saveManualField()
    {
        if (!$this->selectedProfile) return;

        $this->validate([
            'fieldKey' => 'required|string|max:255',
            'fieldValue' => 'nullable',
        ]);

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $data = $profile->data ?? [];

            // Ensure manual_fields is an array
            if (!isset($data['manual_fields']) || !is_array($data['manual_fields'])) {
                $data['manual_fields'] = [];
            }

            if($this->editingField === 'new') {
                $data['manual_fields'][$this->fieldKey] = $this->fieldValue;
            } else {
                // If the key has changed, rename it
                if($this->originalFieldKey !== $this->fieldKey) {
                    unset($data['manual_fields'][$this->originalFieldKey]);
                }
                $data['manual_fields'][$this->fieldKey] = $this->fieldValue;
            }

            $profile->data = $data;
            $profile->save();

            $this->manualFields = $data['manual_fields'];
            $this->dataFields = $data['manual_fields'];
            $this->resetFieldForm();
            $this->calculateTotalFieldCount();
            session()->flash('message', 'Field updated successfully!');
        }
    }

    public function deleteManualField($key)
    {
        if (!$this->selectedProfile) return;

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $data = $profile->data ?? [];

            // Check if manual_fields exists at top level
            if (isset($data['manual_fields']) && is_array($data['manual_fields'])) {
                unset($data['manual_fields'][$key]);
                $profile->data = $data;
                $profile->save();

                $this->manualFields = $data['manual_fields'];
                $this->dataFields = $data['manual_fields'];
                $this->calculateTotalFieldCount();
                session()->flash('message', 'Field deleted successfully!');
            }
        }
    }

    public function editExtractedField($documentName, $fieldKey)
    {
        $this->editingField = 'extracted';
        $this->editingDocumentName = $documentName;
        $this->originalFieldKey = $fieldKey; // Store original key
        $this->fieldKey = $fieldKey;
        $this->fieldValue = $this->groupedDocuments[$documentName]['fields'][$fieldKey] ?? '';
    }

    public function saveExtractedField()
    {
        if (!$this->selectedProfile || !$this->editingDocumentName) return;

        $this->validate([
            'fieldKey' => 'required|string|max:255',
            'fieldValue' => 'nullable|string',
        ]);

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $allData = $profile->data ?? [];

            if(isset($allData[$this->editingDocumentName]['extracted_data'][$this->originalFieldKey])) {
                $allData[$this->editingDocumentName]['extracted_data'][$this->originalFieldKey] = $this->fieldValue;
                if($this->originalFieldKey !== $this->fieldKey) {
                    // If the key has changed, rename it
                    $allData[$this->editingDocumentName]['extracted_data'][$this->fieldKey] = $allData[$this->editingDocumentName]['extracted_data'][$this->originalFieldKey];
                    unset($allData[$this->editingDocumentName]['extracted_data'][$this->originalFieldKey]);
                }
            }

            $profile->data = $allData;
            $profile->save();

            // Refresh the grouped data
            $this->selectProfile($this->selectedProfile['id']);
            $this->resetFieldForm();
            session()->flash('message', 'Extracted field updated successfully!');
        }
    }

    public function deleteExtractedField($documentName, $fieldKey)
    {
        if (!$this->selectedProfile) return;

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $allData = $profile->data ?? [];

            if(isset($allData[$documentName]['extracted_data'][$fieldKey])) {
                unset($allData[$documentName]['extracted_data'][$fieldKey]);
            }

            $profile->data = $allData;
            $profile->save();

            // Refresh the grouped data
            $this->selectProfile($this->selectedProfile['id']);
            session()->flash('message', 'Extracted field deleted successfully!');
        }
    }

    public function deleteDocumentGroup($documentName)
    {
        if (!$this->selectedProfile) return;

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $allData = $profile->data ?? [];

            if(isset($allData[$documentName])) {
                unset($allData[$documentName]);
            }

            $profile->data = $allData;
            $profile->save();

            // Refresh the grouped data
            $this->selectProfile($this->selectedProfile['id']);
            session()->flash('message', 'Document group deleted successfully!');
        }
    }

    public function resetFieldForm()
    {
        $this->editingField = null;
        $this->editingDocumentName = null;
        $this->originalFieldKey = null;
        $this->fieldKey = '';
        $this->fieldValue = '';
        $this->fieldKey = '';
        $this->fieldValue = '';
    }

    public function cancelEdit()
    {
        $this->resetFieldForm();
    }

    public function toggleAccordion($index)
    {
        if (in_array($index, $this->expandedAccordions)) {
            $this->expandedAccordions = array_diff($this->expandedAccordions, [$index]);
        } else {
            $this->expandedAccordions[] = $index;
        }
    }

    public function render()
    {
        return view('livewire.data-manager');
    }
}
