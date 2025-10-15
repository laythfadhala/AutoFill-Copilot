<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\UserProfile;
use App\Services\TogetherAIService;

class ProcessDocument implements ShouldQueue
{
    use Queueable, Batchable;

    protected $filePath;
    protected $originalFilename;
    protected $profileId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $originalFilename, int $profileId, int $userId)
    {
        $this->filePath = $filePath;
        $this->originalFilename = $originalFilename;
        $this->profileId = $profileId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get the profile
            $profile = UserProfile::find($this->profileId);
            if (!$profile) {
                Log::error('Profile not found for document processing', ['profile_id' => $this->profileId]);
                return;
            }

            // Get the full path to the stored file
            $fullPath = Storage::disk('public')->path($this->filePath);

            // Process with AI service
            $aiService = app(TogetherAIService::class);
            $aiResponse = $aiService->processDocument($fullPath);

            // Check for processing errors
            if (isset($aiResponse['error'])) {
                Log::warning('Document processing error in job', [
                    'file' => $this->originalFilename,
                    'error' => $aiResponse['error'],
                    'error_type' => $aiResponse['error_type'] ?? 'unknown'
                ]);
                // Clean up temp file
                Storage::disk('public')->delete($this->filePath);
                return;
            }

            // Parse the JSON response data
            $jsonData = null;
            if (isset($aiResponse['data']) && is_string($aiResponse['data'])) {
                $jsonData = json_decode($aiResponse['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Invalid JSON response from AI in job', [
                        'file' => $this->originalFilename,
                        'json_error' => json_last_error_msg(),
                        'raw_content' => substr($aiResponse['data'], 0, 500)
                    ]);
                    $jsonData = ['error' => 'Invalid JSON response from AI', 'raw_content' => $aiResponse['data']];
                }
            }

            // Create document record with metadata and extracted data
            $documentRecord = [
                'filename' => $this->originalFilename,
                'uploaded_at' => now()->toISOString(),
                'file_path' => $this->filePath,
                'extracted_data' => $jsonData,
            ];

            // Use database transaction with row locking to prevent race conditions
            DB::transaction(function () use ($documentRecord) {
                $profile = UserProfile::where('id', $this->profileId)->lockForUpdate()->first();
                $currentData = $profile->data ?? [];
                $currentData[] = $documentRecord;
                $profile->update(['data' => $currentData]);
            });

            // Clean up temp file after successful processing
            Storage::disk('public')->delete($this->filePath);

            Log::info('Document processed successfully', [
                'filename' => $this->originalFilename,
                'profile_id' => $this->profileId,
                'user_id' => $this->userId
            ]);

        } catch (\Exception $e) {
            Log::error('Document processing job failed', [
                'file' => $this->originalFilename,
                'profile_id' => $this->profileId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up temp file on error
            if (isset($this->filePath)) {
                Storage::disk('public')->delete($this->filePath);
            }

            throw $e;
        }
    }
}
