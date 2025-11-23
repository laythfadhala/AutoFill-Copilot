<?php

namespace App\Jobs;

use App\Enums\TokenAction;
use App\Models\UserProfile;
use App\Services\TextExtraction\TextExtractionService;
use App\Services\TogetherAIService;
use App\Services\TokenService;
use App\Traits\FlattensJson;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocument implements ShouldBeUnique, ShouldQueue
{
    use Batchable, FlattensJson, Queueable;

    // this is the duration (in seconds) for which the job should be considered unique
    public $uniqueFor = 1000;

    protected $filePath;

    protected $originalFilename;

    protected $profileId;

    protected $userId;

    private TextExtractionService $textExtractor;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $originalFilename, int $profileId, int $userId)
    {
        $this->filePath = $filePath;
        $this->originalFilename = $originalFilename;
        $this->profileId = $profileId;
        $this->userId = $userId;
        $this->textExtractor = app(TextExtractionService::class);
    }

    /**
     * Get the unique identifier for the job.
     */
    public function uniqueId()
    {
        return $this->profileId.'-'.md5($this->filePath);
    }

    /**
     * Process a document file and extract data using AI
     *
     * @param  string  $filePath  Path to the uploaded file
     * @return array Extracted data from the document
     */
    private function processTextFromDocument(string $filePath): array
    {
        try {
            // Check if file exists
            if (! file_exists($filePath)) {
                return [
                    'error' => 'File not found: '.basename($filePath),
                    'success' => false,
                    'error_type' => 'file_not_found',
                ];
            }

            // Get MIME type
            $mimeType = mime_content_type($filePath);
            Log::info('Processing file', ['file' => basename($filePath), 'mime_type' => $mimeType]);

            // Check if MIME type is supported
            if (! $this->textExtractor->supportsMimeType($mimeType)) {
                return [
                    'error' => 'Unsupported file type: '.$mimeType.'. Supported types: PDF, images, and plain text files.',
                    'success' => false,
                    'error_type' => 'unsupported_file_type',
                ];
            }

            // Extract text using the text extraction service
            $text = $this->textExtractor->extractText($filePath, $mimeType);

            if (empty($text)) {
                return [
                    'error' => 'Could not extract text from file. The file might be corrupted, empty, or in an unsupported format.',
                    'success' => false,
                    'error_type' => 'text_extraction_failed',
                ];
            }

            // Send to AI API
            $aiService = app(TogetherAIService::class);
            $result = $aiService->extractDataFrom($text);

            return [
                'success' => true,
                'data' => $result['content'],
                'usage' => $result['usage'],
                'raw_content' => $result['content'],
            ];

        } catch (Exception $e) {
            Log::error('Document processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Document processing failed: '.$e->getMessage(),
                'success' => false,
                'error_type' => 'processing_error',
            ];
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get the profile
            $profile = UserProfile::find($this->profileId);
            if (! $profile) {
                Log::error('Profile not found for document processing', ['profile_id' => $this->profileId]);

                return;
            }

            // Get the full path to the stored file
            $fullPath = Storage::disk('public')->path($this->filePath);

            // Process with AI service
            $aiResponse = $this->processTextFromDocument($fullPath);

            // Check for processing errors
            if (isset($aiResponse['error'])) {
                Log::warning('Document processing error in job', [
                    'file' => $this->originalFilename,
                    'error' => $aiResponse['error'],
                    'error_type' => $aiResponse['error_type'] ?? 'unknown',
                ]);
                // Clean up temp file
                Storage::disk('public')->delete($this->filePath);

                return;
            }

            // Consume actual tokens used by AI
            if (isset($aiResponse['usage'])) {
                $user = \App\Models\User::find($this->userId);
                if ($user) {
                    TokenService::consumeActualTokens(
                        $user,
                        TokenAction::DOCUMENT_PROCESSING,
                        $aiResponse['usage'],
                        [
                            'filename' => $this->originalFilename,
                            'file_size_kb' => filesize($fullPath) / 1024,
                            'profile_id' => $this->profileId,
                        ]
                    );
                }
            }

            // Parse the JSON response data
            $jsonData = null;
            if (isset($aiResponse['data']) && is_string($aiResponse['data'])) {
                $jsonData = json_decode($aiResponse['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Invalid JSON response from AI in job', [
                        'file' => $this->originalFilename,
                        'json_error' => json_last_error_msg(),
                        'raw_content' => substr($aiResponse['data'], 0, 500),
                    ]);
                    $jsonData = ['error' => 'Invalid JSON response from AI', 'raw_content' => $aiResponse['data']];
                }
            }

            // Create document record with metadata and extracted data
            $documentRecord = [
                'filename' => $this->originalFilename,
                'uploaded_at' => now()->toISOString(),
                'file_path' => $this->filePath,
                'extracted_data' => $this->flattenJsonRecursive($jsonData ?? []),
            ];

            // Use database transaction with row locking to prevent race conditions
            DB::transaction(function () use ($documentRecord) {
                $profile = UserProfile::where('id', $this->profileId)->lockForUpdate()->first();
                $currentData = $profile->data ?? [];
                $currentData[$documentRecord['filename']] = $documentRecord;
                $profile->update(['data' => $currentData]);
            });

            // Clean up temp file after successful processing
            Storage::disk('public')->delete($this->filePath);

            Log::info('Document processed successfully', [
                'filename' => $this->originalFilename,
                'profile_id' => $this->profileId,
                'user_id' => $this->userId,
            ]);

        } catch (\Exception $e) {
            Log::error('Document processing job failed', [
                'file' => $this->originalFilename,
                'profile_id' => $this->profileId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up temp file on error
            if (isset($this->filePath)) {
                Storage::disk('public')->delete($this->filePath);
            }

            throw $e;
        }
    }
}
