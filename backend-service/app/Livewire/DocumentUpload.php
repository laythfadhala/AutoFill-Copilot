<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\UserProfile;
use App\Services\TogetherAIService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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

    protected $rules = [
        'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max per file (matches PHP config)
        'documents' => 'required|array|max:10', // Up to 10 files
        'selectedProfile' => 'required|exists:user_profiles,id',
    ];

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

    public function updatedDocuments()
    {
        try {
            $this->validateOnly('documents');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Document validation failed on update', [
                'errors' => $e->errors(),
                'documents_count' => count($this->documents ?? [])
            ]);
            // Don't re-throw here, let Livewire show the errors
        }
    }

    public function uploadDocument()
    {
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors for debugging
            Log::warning('Document upload validation failed', [
                'errors' => $e->errors(),
                'documents_count' => count($this->documents ?? []),
                'selected_profile' => $this->selectedProfile
            ]);

            // Re-throw to let Livewire handle the validation errors
            throw $e;
        }

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
                ->onQueue('documents')
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
        
        // Update session with current status
        session(['current_job_statuses' => $this->jobStatuses]);
        
        // Check if all jobs are completed
        $allCompleted = !empty($this->jobStatuses) && collect($this->jobStatuses)->every(fn($job) => $job['status'] === 'completed');
        if ($allCompleted) {
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

    /**
     * Format error messages for user-friendly display
     */
    private function formatErrorMessage(array $aiResponse, string $filename): string
    {
        $error = $aiResponse['error'] ?? 'Unknown error';
        $errorType = $aiResponse['error_type'] ?? 'unknown';

        $userFriendlyMessages = [
            'configuration' => "AI service is not configured. Please contact the administrator to set up the TOGETHER_API_KEY.",
            'file_not_found' => "File '{$filename}' could not be found during processing.",
            'text_extraction_failed' => "Could not extract text from '{$filename}'. The file might be corrupted, empty, or in an unsupported format.",
            'processing_error' => "Failed to process '{$filename}': {$error}",
        ];

        return $userFriendlyMessages[$errorType] ?? "Error processing '{$filename}': {$error}";
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
        $maxSize = 10 * 1024 * 1024; // 10MB
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
