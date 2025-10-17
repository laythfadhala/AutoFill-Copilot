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
    public $processingStatus = '';
    public $uploadedDocuments = [];
    public $jobStatuses = []; // Track job statuses
    public $currentBatchId = null; // Track current batch

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
    }

    public function updatedDocuments()
    {
        $this->validateOnly('documents');
    }

    public function uploadDocument()
    {
        $this->validate();

        $this->isProcessing = true;
        $totalFiles = count($this->documents);
        $queuedFiles = 0;
        $errors = [];
        $this->jobStatuses = []; // Reset job statuses

        // Prepare jobs for batch processing
        $jobs = [];
        foreach ($this->documents as $index => $document) {
            try {
                $this->processingStatus = "Validating file " . ($index + 1) . " of {$totalFiles}: {$document->getClientOriginalName()}";

                // Pre-validate file
                $validationError = $this->validateUploadedFile($document);
                if ($validationError) {
                    $errors[] = $validationError;
                    continue;
                }

                $this->processingStatus = "Queueing file " . ($index + 1) . " of {$totalFiles} for processing: {$document->getClientOriginalName()}";

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
            $this->processingStatus = "Successfully queued {$queuedFiles} of {$totalFiles} files for processing!";
            if (!empty($errors)) {
                $this->processingStatus .= " Some files had errors.";
            }
            session()->flash('message', "Successfully queued {$queuedFiles} of {$totalFiles} documents for background processing!");
        } else {
            $this->processingStatus = 'No files were queued successfully.';
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
            $totalJobs = $batch->totalJobs;

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
            $this->processingStatus = 'All files processed!';
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

    /**
     * Validate uploaded file before processing
     */
    private function validateUploadedFile($document): ?string
    {
        $filename = $document->getClientOriginalName();
        $mimeType = $document->getMimeType();
        $size = $document->getSize();

        // Check file size
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($size > $maxSize) {
            return "File '{$filename}' is too large ({$this->formatBytes($size)}). Maximum allowed size is {$this->formatBytes($maxSize)}.";
        }

        // Check MIME type
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($mimeType, $allowedMimes)) {
            return "File '{$filename}' has unsupported format '{$mimeType}'. Only PDF, JPG, and PNG files are allowed.";
        }

        // For PDFs, check if it's not empty
        if ($mimeType === 'application/pdf' && $size < 100) {
            return "File '{$filename}' appears to be an empty or corrupted PDF.";
        }

        // For images, check basic image properties
        if (str_starts_with($mimeType, 'image/')) {
            try {
                $imageInfo = getimagesize($document->getRealPath());
                if (!$imageInfo) {
                    return "File '{$filename}' is not a valid image file.";
                }
                // Check if image has reasonable dimensions
                if ($imageInfo[0] < 10 || $imageInfo[1] < 10) {
                    return "File '{$filename}' has invalid image dimensions.";
                }
            } catch (Exception $e) {
                return "File '{$filename}' could not be validated as an image.";
            }
        }

        return null; // No validation errors
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
