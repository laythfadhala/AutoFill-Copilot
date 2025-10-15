<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TogetherAIService
{
    /**
     * Unified extraction prompt for document factual data
     */
    private const EXTRACTION_PROMPT = <<<PROMPT
        Extract only factual data from the document and return a single valid, compact JSON object.
        Keep strictly all:
        - Names, addresses, dates, numbers, IDs, tax data, recipients, costs, rates, percentages, amounts, money, and contact or banking details.
        Remove completely:
        - Any paragraphs or sentences explaining reasons, laws, legal rights, appeals, data protection, privacy, or instructions.
        Formatting rules:
        - Response must be in the same language as the document including the keys.
        - Response must always have 'Title' of the document.
        - Flatten the structure into one dimension key:value.
        - Output only raw JSON, no markdown or text, no explanations.
        PROMPT;
    private string $apiUrl;
    private ?string $apiKey;
    private string $defaultModel;

    public function __construct()
    {
        $this->apiUrl = config('services.together.url', 'https://api.together.xyz/v1/chat/completions');
        $this->apiKey = config('services.together.key');
        $this->defaultModel = config('services.together.model', 'meta-llama/Llama-3.3-70B-Instruct-Turbo-Free');

        if (!$this->apiKey) {
            Log::warning('TogetherAI API key not configured');
        }
    }

    /**
     * Generate a chat completion using the AI model with a unified prompt for all file types
     * @param string $extractedText Text extracted from the file (OCR or raw)
     * @param array $options Optional model options
     */
    public function generateCompletion(
        string $extractedText,
        array $options = []
    ): array {
        if (!$this->apiKey) {
            return [
                'success' => false,
                'error' => 'AI service not configured - API key missing'
            ];
        }

        try {
            $prompt = self::EXTRACTION_PROMPT . $extractedText;

            $payload = [
                'model' => $options['model'] ?? $this->defaultModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'temperature' => $options['temperature'] ?? 0.2,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('AI completion generated successfully', [
                    'model' => $payload['model'],
                    'prompt_length' => strlen($prompt),
                    'response_length' => strlen($data['choices'][0]['message']['content'] ?? ''),
                ]);

                return [
                    'success' => true,
                    'content' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? null,
                    'model' => $data['model'] ?? $payload['model'],
                ];
            } else {
                Log::error('AI service request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'AI service request failed: ' . $response->status(),
                ];
            }
        } catch (Exception $e) {
            Log::error('AI service exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'AI service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze form data using AI with optimized settings for predictability
     */
    public function analyzeForm(array $formData): array
    {
        $prompt = $this->buildFormAnalysisPrompt($formData);

        return $this->generateCompletion($prompt, [
            'max_tokens' => 500,
            'temperature' => 0.1, // Very low for maximum consistency
            'top_p' => 0.1,       // Very focused token selection
            'top_k' => 10,        // Limit to top 10 tokens
            'seed' => 42,         // Fixed seed for reproducible results
        ]);
    }

    /**
     * Build a prompt for form analysis
     */
    private function buildFormAnalysisPrompt(array $formData): string
    {
        $fields = json_encode($formData, JSON_PRETTY_PRINT);

        return <<<PROMPT
            Analyze the following form fields and suggest appropriate data to fill them. Consider common form patterns and provide realistic, context-appropriate values.

            Form fields:
            {$fields}

            Please provide suggestions in JSON format with field names as keys and suggested values as values. Only include fields that can be reasonably filled with user data.
            PROMPT;
    }

    /**
     * Check if the AI service is available
     */
    public function isAvailable(): bool
    {
        if (!$this->apiKey) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(5)->get('https://api.together.xyz/v1/models');

            return $response->successful();
        } catch (Exception $e) {
            Log::warning('AI service availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process a document file and extract data using AI
     * @param string $filePath Path to the uploaded file
     * @return array Extracted data from the document
     */
    public function processDocument(string $filePath): array
    {
        try {
            // Check if API key is available
            if (!$this->apiKey) {
                Log::warning('TogetherAI API key not configured');
                return ['error' => 'AI service not configured. Please set TOGETHER_API_KEY in your .env file.'];
            }

            // Get MIME type
            $mimeType = mime_content_type($filePath);

            // Prepare data depending on file type
            if ($mimeType === 'application/pdf') {
                Log::info('Processing PDF file', ['file' => basename($filePath)]);
                $extractedText = $this->extractTextFromPdf($filePath);

                if (empty($extractedText)) {
                    return ['error' => 'Could not extract text from PDF'];
                }

                return $this->generateCompletion($extractedText);

            } elseif (str_starts_with($mimeType, 'image/')) {
                Log::info('Processing image file', ['file' => basename($filePath)]);
                $ocrText = $this->extractTextFromImage($filePath);

                if (empty($ocrText)) {
                    return ['error' => 'Could not extract text from image'];
                }

                return $this->generateCompletion($ocrText);

            } elseif ($mimeType === 'text/plain') {
                Log::info('Processing text file', ['file' => basename($filePath)]);
                $text = file_get_contents($filePath);

                if (empty($text)) {
                    return ['error' => 'Could not read text file'];
                }

                return $this->generateCompletion($text);

            } else {
                Log::warning('Unsupported file type', ['mime_type' => $mimeType, 'file' => basename($filePath)]);
                return ['error' => 'Unsupported file type: ' . $mimeType];
            }

        } catch (Exception $e) {
            Log::error('Document processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Document processing failed: ' . $e->getMessage()];
        }
    }

    /**
     * Extract text from various file types
     * @param string $filePath Path to the file
     * @return string Extracted text
     */
    private function extractTextFromFile(string $filePath): string
    {
        $mimeType = mime_content_type($filePath);

        if ($mimeType === 'application/pdf') {
            return $this->extractTextFromPdf($filePath);
        } elseif (str_starts_with($mimeType, 'image/')) {
            return $this->extractTextFromImage($filePath);
        } elseif ($mimeType === 'text/plain') {
            return file_get_contents($filePath);
        } else {
            return 'Document: ' . basename($filePath);
        }
    }

    /**
     * Extract text from PDF files using pdftoppm and tesseract OCR
     * @param string $filePath Path to PDF file
     * @return string Extracted text
     */
    private function extractTextFromPdf(string $filePath): string
    {
        try {
            Log::info('Converting PDF to images for OCR', ['file' => basename($filePath)]);

            // Create temporary directory for images
            $tmpImgDir = sys_get_temp_dir() . '/pdf_images_' . uniqid();
            if (!mkdir($tmpImgDir, 0755, true)) {
                throw new Exception('Could not create temporary directory for PDF processing');
            }

            // Convert each PDF page to PNG at 200 DPI (matching the workflow)
            $output = shell_exec("pdftoppm -r 200 -gray -aa no \"$filePath\" \"$tmpImgDir/page\" -png 2>/dev/null");
            if ($output === null) {
                Log::warning('pdftoppm command failed or not found');
                // Fallback to basic text extraction
                return $this->extractTextFromPdfFallback($filePath);
            }

            Log::info('Running OCR on PDF images');

            $text = '';
            $imageFiles = glob("$tmpImgDir/*.png");

            foreach ($imageFiles as $img) {
                if (!file_exists($img)) continue;

                Log::info('OCR processing page', ['page' => basename($img)]);

                // Run tesseract with eng+deu+ara languages (matching the workflow)
                // Force UTF-8 output
                $pageText = shell_exec("tesseract \"$img\" stdout -l eng+deu+ara 2>/dev/null | iconv -f utf-8 -t utf-8 -c");

                if ($pageText) {
                    $text .= $pageText . "\n";
                }
            }

            // Clean up temporary directory
            $this->cleanupDirectory($tmpImgDir);

            if (empty(trim($text))) {
                Log::warning('No text extracted from PDF via OCR');
                return $this->extractTextFromPdfFallback($filePath);
            }

            // Ensure UTF-8 encoding
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            return trim($text);

        } catch (Exception $e) {
            Log::error('PDF OCR processing failed', ['error' => $e->getMessage()]);
            return $this->extractTextFromPdfFallback($filePath);
        }
    }

    /**
     * Fallback PDF text extraction (basic implementation)
     */
    private function extractTextFromPdfFallback(string $filePath): string
    {
        try {
            $content = file_get_contents($filePath);

            // Simple text extraction - look for text between streams
            if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
                return implode(' ', $matches[1]);
            }

            return 'PDF Document: ' . basename($filePath);

        } catch (Exception $e) {
            Log::warning('PDF fallback extraction failed', ['error' => $e->getMessage()]);
            return 'PDF Document: ' . basename($filePath);
        }
    }

    /**
     * Extract text from image files using tesseract OCR
     * @param string $filePath Path to image file
     * @return string Extracted text
     */
    private function extractTextFromImage(string $filePath): string
    {
        try {
            Log::info('Running OCR on image file', ['file' => basename($filePath)]);

            // Run tesseract with eng+deu languages (matching the workflow)
            // Force UTF-8 output
            $ocrText = shell_exec("tesseract \"$filePath\" stdout -l eng+deu 2>/dev/null | iconv -f utf-8 -t utf-8 -c");

            if ($ocrText && trim($ocrText) !== '') {
                Log::info('OCR text extracted successfully');
                // Ensure UTF-8 encoding
                $ocrText = mb_convert_encoding($ocrText, 'UTF-8', 'auto');
                return trim($ocrText);
            } else {
                Log::warning('No text extracted from image via OCR');
                return '';
            }

        } catch (Exception $e) {
            Log::error('Image OCR processing failed', ['error' => $e->getMessage()]);
            return '';
        }
    }

    /**
     * Clean up temporary directory
     */
    private function cleanupDirectory(string $dir): void
    {
        try {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($dir);
            }
        } catch (Exception $e) {
            Log::warning('Failed to cleanup temporary directory', ['dir' => $dir, 'error' => $e->getMessage()]);
        }
    }
}
