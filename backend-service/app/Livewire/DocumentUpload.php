<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\UserProfile;
use App\Services\TogetherAIService;
use Illuminate\Support\Facades\Storage;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public $document;
    public $selectedProfile;
    public $isProcessing = false;
    public $processingStatus = '';
    public $uploadedDocuments = [];

    protected $listeners = ['profileUpdated' => 'loadUserProfiles'];

    protected $rules = [
        'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        'selectedProfile' => 'required|exists:user_profiles,id',
    ];

    public function mount()
    {
        $this->loadUserProfiles();
    }

    public function loadUserProfiles()
    {
        $this->uploadedDocuments = auth()->user()->userProfiles()
            ->with('user')
            ->get()
            ->map(function ($profile) {
                $allData = $profile->data ?? [];

                // Filter out only document objects (arrays with filename/uploaded_at keys)
                $documents = array_filter($allData, function ($item) {
                    return is_array($item) && isset($item['filename']) && isset($item['uploaded_at']);
                });

                return [
                    'profile' => $profile,
                    'documents' => array_values($documents), // Re-index array
                    'count' => count($documents),
                ];
            })
            ->toArray();
    }

    public function updatedDocument()
    {
        $this->validateOnly('document');
    }

    public function uploadDocument()
    {
        $this->validate();

        $this->isProcessing = true;
        $this->processingStatus = 'Uploading document...';

        try {
            // Store the file temporarily
            $path = $this->document->store('temp-documents', 'public');

            $this->processingStatus = 'Processing with AI...';

            // Get the selected profile
            $profile = UserProfile::find($this->selectedProfile);

            // Process with AI service
            $aiService = app(TogetherAIService::class);
            $extractedData = $aiService->processDocument($this->document->getRealPath());

            // Add to profile data
            $currentData = $profile->data ?? [];
            $currentData[] = [
                'filename' => $this->document->getClientOriginalName(),
                'uploaded_at' => now()->toISOString(),
                'extracted_data' => $extractedData,
                'file_path' => $path,
            ];

            $profile->update(['data' => $currentData]);

            // Clean up temp file
            Storage::disk('public')->delete($path);

            $this->processingStatus = 'Document processed successfully!';
            $this->document = null;
            $this->loadUserProfiles();

            session()->flash('message', 'Document uploaded and processed successfully!');

        } catch (\Exception $e) {
            $this->processingStatus = 'Error: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        $profiles = auth()->user()->userProfiles()->active()->get();

        return view('livewire.document-upload', [
            'profiles' => $profiles,
        ]);
    }
}
