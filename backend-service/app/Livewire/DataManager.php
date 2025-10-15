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
    public $editingDocumentTitle = null; // For tracking which document group we're editing
    public $originalFieldKey = null; // For tracking original field key when editing extracted fields
    public $groupedDocuments = [];
    public $manualFields = [];
    public $activeTab = 'extracted'; // 'extracted' or 'manual'
    public $totalFieldCount = 0;

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
            $allData = $profile->data ?? [];

            // Separate document data from manual fields
            $this->groupedDocuments = [];
            $this->manualFields = [];

            foreach ($allData as $item) {
                if (is_array($item) && isset($item['extracted_data'])) {
                    // This is a document record
                    $extractedData = $item['extracted_data'];
                    if (is_array($extractedData) && isset($extractedData['Title'])) {
                        $title = $extractedData['Title'];
                        if (!isset($this->groupedDocuments[$title])) {
                            $this->groupedDocuments[$title] = [
                                'title' => $title,
                                'documents' => [],
                                'fields' => []
                            ];
                        }
                        $this->groupedDocuments[$title]['documents'][] = $item;
                        // Add all fields except 'Title' to the grouped fields
                        foreach ($extractedData as $key => $value) {
                            if ($key !== 'Title' && $key !== 'error') {
                                $this->groupedDocuments[$title]['fields'][$key] = $value;
                            }
                        }
                    }
                } elseif (is_array($item) && isset($item['filename'])) {
                    // This is a document record (new format)
                    $extractedData = $item['extracted_data'] ?? [];
                    if (is_array($extractedData) && isset($extractedData['Title'])) {
                        $title = $extractedData['Title'];
                        if (!isset($this->groupedDocuments[$title])) {
                            $this->groupedDocuments[$title] = [
                                'title' => $title,
                                'documents' => [],
                                'fields' => []
                            ];
                        }
                        $this->groupedDocuments[$title]['documents'][] = $item;
                        // Add all fields except 'Title' to the grouped fields
                        foreach ($extractedData as $key => $value) {
                            if ($key !== 'Title' && $key !== 'error') {
                                $this->groupedDocuments[$title]['fields'][$key] = $value;
                            }
                        }
                    }
                } else {
                    // This might be a manual field (key => value pair)
                    if (is_string($item) || is_numeric($item)) {
                        // Assume this is a manual field stored as key => value
                        // But we need to handle the case where data is stored differently
                        $this->manualFields = $allData; // Fallback to showing all data
                        break;
                    }
                }
            }

            // If no documents found, treat all data as manual fields
            if (empty($this->groupedDocuments)) {
                $this->manualFields = $allData;
            }

            // Calculate total field count
            $this->totalFieldCount = count($this->manualFields);
            foreach ($this->groupedDocuments as $group) {
                $this->totalFieldCount += count($group['fields']);
            }

            // Keep backward compatibility for editing
            $this->dataFields = $this->manualFields;
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

    public function editExtractedField($documentTitle, $fieldKey)
    {
        $this->editingField = 'extracted';
        $this->editingDocumentTitle = $documentTitle;
        $this->originalFieldKey = $fieldKey; // Store original key
        $this->fieldKey = $fieldKey;
        $this->fieldValue = $this->groupedDocuments[$documentTitle]['fields'][$fieldKey] ?? '';
        $this->fieldType = $this->detectFieldType($this->fieldValue);
    }

    public function saveExtractedField()
    {
        if (!$this->selectedProfile || !$this->editingDocumentTitle) return;

        $this->validate([
            'fieldKey' => 'required|string|max:255',
            'fieldValue' => 'nullable|string',
        ]);

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $allData = $profile->data ?? [];

            // Find and update the document records for this title
            foreach ($allData as &$item) {
                if (is_array($item) && isset($item['extracted_data']) &&
                    isset($item['extracted_data']['Title']) &&
                    $item['extracted_data']['Title'] === $this->editingDocumentTitle) {
                    // If the key changed, remove the old key
                    if ($this->originalFieldKey && $this->originalFieldKey !== $this->fieldKey) {
                        unset($item['extracted_data'][$this->originalFieldKey]);
                    }
                    $item['extracted_data'][$this->fieldKey] = $this->fieldValue;
                } elseif (is_array($item) && isset($item['filename']) &&
                         isset($item['extracted_data']['Title']) &&
                         $item['extracted_data']['Title'] === $this->editingDocumentTitle) {
                    // If the key changed, remove the old key
                    if ($this->originalFieldKey && $this->originalFieldKey !== $this->fieldKey) {
                        unset($item['extracted_data'][$this->originalFieldKey]);
                    }
                    $item['extracted_data'][$this->fieldKey] = $this->fieldValue;
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

    public function deleteExtractedField($documentTitle, $fieldKey)
    {
        if (!$this->selectedProfile) return;

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $allData = $profile->data ?? [];

            // Find and update the document records for this title
            foreach ($allData as &$item) {
                if (is_array($item) && isset($item['extracted_data']) &&
                    isset($item['extracted_data']['Title']) &&
                    $item['extracted_data']['Title'] === $documentTitle) {
                    unset($item['extracted_data'][$fieldKey]);
                } elseif (is_array($item) && isset($item['filename']) &&
                         isset($item['extracted_data']['Title']) &&
                         $item['extracted_data']['Title'] === $documentTitle) {
                    unset($item['extracted_data'][$fieldKey]);
                }
            }

            $profile->data = $allData;
            $profile->save();

            // Refresh the grouped data
            $this->selectProfile($this->selectedProfile['id']);
            session()->flash('message', 'Extracted field deleted successfully!');
        }
    }

    public function deleteDocumentGroup($documentTitle)
    {
        if (!$this->selectedProfile) return;

        $profile = UserProfile::find($this->selectedProfile['id']);
        if ($profile && $profile->user_id === auth()->id()) {
            $allData = $profile->data ?? [];

            // Remove all document records with this title
            $filteredData = array_filter($allData, function($item) use ($documentTitle) {
                if (is_array($item) && isset($item['extracted_data']) &&
                    isset($item['extracted_data']['Title']) &&
                    $item['extracted_data']['Title'] === $documentTitle) {
                    return false; // Remove this item
                } elseif (is_array($item) && isset($item['filename']) &&
                         isset($item['extracted_data']['Title']) &&
                         $item['extracted_data']['Title'] === $documentTitle) {
                    return false; // Remove this item
                }
                return true; // Keep this item
            });

            $profile->data = array_values($filteredData); // Re-index array
            $profile->save();

            // Refresh the grouped data
            $this->selectProfile($this->selectedProfile['id']);
            session()->flash('message', 'Document group deleted successfully!');
        }
    }

    public function resetFieldForm()
    {
        $this->editingField = null;
        $this->editingDocumentTitle = null;
        $this->originalFieldKey = null;
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
