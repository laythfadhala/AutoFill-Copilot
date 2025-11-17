<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Jobs\ProcessDocument;
use Illuminate\Support\Facades\Bus;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public $documents = [];
    public $selectedProfile;
    public $isProcessing = false;
    public $uploadedDocuments = [];
    public $jobStatuses = []; // Track job statuses
    public $currentBatchId = null; // Track current batch
    public $isTokenLimitReached = false;
    public $isDocumentLimitReached = false;
    public $documentCount = 0;
    public $maxDocuments = null;


    protected $listeners = ['profileUpdated' => 'loadUserProfiles'];

    protected function rules()
    {
        return [
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max per file (matches PHP config)
            'documents' => 'required|array|max:10', // Up to 10 files
            'selectedProfile' => 'required|exists:user_profiles,id,user_id,' . auth()->id(),
        ];
    }

    public function mount()
    {
        $this->loadUserProfiles();
        $this->updateDocumentLimitStatus();

        // Restore batch information from session if it exists
        $this->restoreBatchStatus();
    }

    private function restoreBatchStatus()
    {
        $batchId = session('current_document_batch_id');
        if ($batchId) {
            $this->currentBatchId = $batchId;
            $this->jobStatuses = session('current_job_statuses', []);
            $this->isProcessing = true; // Assume processing until we check

            // Immediately check status to update
            $this->checkJobStatuses();
        }
    }

    public function loadUserProfiles()
    {
        $profiles = auth()->user()->userProfiles()->with('user')->get();
        if (!$profiles->isEmpty()) {
            $this->uploadedDocuments = $profiles->map(function ($profile) {
                $allData = $profile->data ?? [];

                // Filter out only document objects (arrays with filename/uploaded_at keys)
                $documents = array_filter($allData, function ($item) {
                    return is_array($item) && isset($item['filename']) && isset($item['uploaded_at']);
                });

                return [
                    'profile_id' => $profile->id,
                    'profile_name' => $profile->name,
                    'profile_type' => $profile->type,
                    'documents' => array_values($documents), // Re-index array
                    'count' => count($documents),
                ];
            })->toArray();
        } else {
            $this->uploadedDocuments = [];
        }

        $this->updateDocumentLimitStatus();
    }

    private function updateDocumentLimitStatus()
    {
        $user = auth()->user();
        $this->documentCount = $user->getDocumentCount();
        $this->maxDocuments = $user->getMaxDocuments();
        $this->isDocumentLimitReached = $user->isDocumentLimitReached();
    }

    public function processDocument()
    {
        $this->validate();

        $user = auth()->user();

        // Check document limit before processing
        $documentsToUpload = count($this->documents);
        if (!$user->canUploadDocument($documentsToUpload)) {
            $maxDocuments = $user->getMaxDocuments();
            $currentCount = $user->getDocumentCount();
            $remaining = $maxDocuments - $currentCount;

            session()->flash('error', "Document limit reached. You can only upload {$remaining} more document(s) on the free plan. You currently have {$currentCount} of {$maxDocuments} documents. Upgrade to a paid plan for unlimited documents.");
            return;
        }

        $this->isProcessing = true;
        $totalFiles = count($this->documents);
        $queuedFiles = 0;
        $errors = [];
        // Don't reset job statuses - append to existing ones to keep old uploads in the queue UI
        // $this->jobStatuses = []; // Reset job statuses

        // Prepare jobs for batch processing
        $jobs = [];
        foreach ($this->documents as $index => $document) {
            try {
                // Store the file temporarily for the job to process
                $storedPath = $document->store('temp-documents', 'public');

                // Add job to batch
                $jobs[] = new ProcessDocument($storedPath, $document->getClientOriginalName(), $this->selectedProfile, auth()->id());

                // Track job status
                $this->jobStatuses[] = [
                    'filename' => $document->getClientOriginalName(),
                    'status' => 'queued',
                    'was_saved' => false,
                    'queued_at' => now()->toISOString(),
                ];

                $queuedFiles++;

            } catch (\Exception $e) {
                $errors[] = "Error queueing {$document->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        // Dispatch jobs as a batch if we have any
        if (!empty($jobs)) {
            $batch = Bus::batch($jobs)
                ->name('document-processing-' . now()->timestamp)
                ->onQueue('default')
                ->dispatch();

            // Store batch ID for tracking
            $this->currentBatchId = $batch->id;

            // Persist batch information in session for page reloads
            session([
                'current_document_batch_id' => $batch->id,
                'current_job_statuses' => $this->jobStatuses
            ]);
        }

        // Set final status
        if ($queuedFiles > 0) {
            session()->flash('success', "Successfully queued {$queuedFiles} of {$totalFiles} documents for background processing!");
        }

        // Show errors if any
        if (!empty($errors)) {
            session()->flash('error', implode('<br>', $errors));
        }

        $this->documents = [];
        $this->loadUserProfiles();

        $this->isProcessing = false;
    }

    public function checkJobStatuses()
    {
        if (empty($this->jobStatuses) || !$this->currentBatchId) {
            return;
        }

        // Get batch progress
        $batch = Bus::findBatch($this->currentBatchId);
        if (!$batch) {
            // Batch completed and was cleaned up
            foreach ($this->jobStatuses as &$jobStatus) {
                if ($jobStatus['status'] !== 'completed') {
                    $jobStatus['status'] = 'completed';
                    $jobStatus['completed_at'] = now()->toISOString();
                }
            }
        } else {
            // Update status based on batch progress
            $completedJobs = $batch->processedJobs();

            for ($i = 0; $i < count($this->jobStatuses); $i++) {
                if ($i < $completedJobs) {
                    $this->jobStatuses[$i]['status'] = 'completed';
                    if (!isset($this->jobStatuses[$i]['completed_at'])) {
                        $this->jobStatuses[$i]['completed_at'] = now()->toISOString();
                    }
                } elseif ($batch->finished()) {
                    $this->jobStatuses[$i]['status'] = 'completed';
                    if (!isset($this->jobStatuses[$i]['completed_at'])) {
                        $this->jobStatuses[$i]['completed_at'] = now()->toISOString();
                    }
                } else {
                    $this->jobStatuses[$i]['status'] = 'processing';
                }
            }
        }

        // Refresh uploaded documents to show new ones
        $this->loadUserProfiles();

        // Update statuses based on saved data
        $profileData = collect($this->uploadedDocuments)->firstWhere('profile_id', $this->selectedProfile);
        if ($profileData) {
            $savedFilenames = collect($profileData['documents'])->pluck('filename')->toArray();
            foreach ($this->jobStatuses as &$jobStatus) {
                if (in_array($jobStatus['filename'], $savedFilenames)) {
                    $jobStatus['status'] = 'completed';
                    $jobStatus['was_saved'] = true;
                    if (!isset($jobStatus['completed_at'])) {
                        $jobStatus['completed_at'] = now()->toISOString();
                    }
                } elseif (($jobStatus['was_saved'] ?? false)) {
                    $jobStatus['status'] = 'deleted';
                } elseif (!$batch || $batch->finished()) {
                    $jobStatus['status'] = 'failed';
                } else {
                    $jobStatus['status'] = 'processing';
                }
            }
        }

        // Update session with current status
        session(['current_job_statuses' => $this->jobStatuses]);

        // Check if all jobs are processed
        $allProcessed = !$batch || $batch->finished();
        if ($allProcessed) {
            $this->isProcessing = false;
            // Clear session data when processing is complete
            session()->forget(['current_document_batch_id', 'current_job_statuses']);
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
