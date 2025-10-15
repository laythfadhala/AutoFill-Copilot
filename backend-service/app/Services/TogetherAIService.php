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
        - Output only raw valid JSON, no markdown or text, no explanations.
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
     * Clean markdown code blocks from AI response and extract JSON
     */
    private function cleanMarkdownCodeBlocks(string $content): string
    {
        // Find the first '{' and last '}' to extract JSON content
        $startPos = strpos($content, '{');
        $endPos = strrpos($content, '}');

        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $jsonContent = substr($content, $startPos, $endPos - $startPos + 1);
            return trim($jsonContent);
        }

        // Fallback: remove markdown code blocks if JSON extraction fails
        $content = preg_replace('/^```(?:json)?\s*$/m', '', $content);
        $content = preg_replace('/^```\s*$/m', '', $content);

        // Trim whitespace
        return trim($content);
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
                return [
                    'error' => 'AI service not configured. Please set TOGETHER_API_KEY in your .env file.',
                    'success' => false,
                    'error_type' => 'configuration'
                ];
            }

            // Check if file exists
            if (!file_exists($filePath)) {
                return [
                    'error' => 'File not found: ' . basename($filePath),
                    'success' => false,
                    'error_type' => 'file_not_found'
                ];
            }

            // Get MIME type
            $mimeType = mime_content_type($filePath);
            Log::info('Processing file', ['file' => basename($filePath), 'mime_type' => $mimeType]);

            // Extract text based on file type
            $text = $this->extractText($filePath, $mimeType);

            if (empty($text)) {
                return [
                    'error' => 'Could not extract text from file. The file might be corrupted, empty, or in an unsupported format.',
                    'success' => false,
                    'error_type' => 'text_extraction_failed'
                ];
            }

            // Send to AI API
            $response = $this->callTogetherAI($text);

            return [
                'success' => true,
                'data' => $response,
                'raw_content' => $response
            ];

        } catch (Exception $e) {
            Log::error('Document processing failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return [
                'error' => 'Document processing failed: ' . $e->getMessage(),
                'success' => false,
                'error_type' => 'processing_error'
            ];
        }
    }

    /**
     * Call TogetherAI API with extracted text
     */
    private function callTogetherAI(string $text): string
    {
        $prompt = self::EXTRACTION_PROMPT . "\n" . $text;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($this->apiUrl, [
            'model' => $this->defaultModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.2
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Clean markdown code blocks from response
            $content = $this->cleanMarkdownCodeBlocks($content);

            Log::info('AI response received', ['length' => strlen($content)]);
            return $content;
        } else {
            Log::error('AI API request failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new Exception('AI API request failed: ' . $response->status());
        }
    }

    /**
     * Extract text from file based on MIME type
     */
    private function extractText(string $filePath, string $mimeType): string
    {
        if ($mimeType === 'application/pdf') {
            return $this->extractTextFromPdf($filePath);
        } elseif (str_starts_with($mimeType, 'image/')) {
            return $this->extractTextFromImage($filePath);
        } elseif ($mimeType === 'text/plain') {
            return file_get_contents($filePath);
        } else {
            Log::warning('Unsupported file type', ['mime_type' => $mimeType]);
            return '';
        }
    }

    /**
     * Extract text from PDF using pdftoppm and tesseract
     */
    private function extractTextFromPdf(string $filePath): string
    {
        try {
            Log::info('Converting PDF to images for OCR');

            // Check if required tools are available
            if (!$this->isCommandAvailable('pdftoppm')) {
                throw new Exception('PDF processing tool (pdftoppm) is not installed. Please install poppler-utils package.');
            }

            if (!$this->isCommandAvailable('tesseract')) {
                throw new Exception('OCR tool (tesseract) is not installed. Please install tesseract-ocr package.');
            }

            // Check if file is readable
            if (!is_readable($filePath)) {
                throw new Exception('PDF file is not readable or accessible.');
            }

            // Check file size (PDFs over 50MB might cause issues)
            $fileSize = filesize($filePath);
            if ($fileSize > 50 * 1024 * 1024) { // 50MB
                throw new Exception('PDF file is too large (over 50MB). Please use a smaller file or compress the PDF.');
            }

            // Create temp directory
            $tmpDir = sys_get_temp_dir() . '/pdf_' . uniqid();
            if (!mkdir($tmpDir, 0755, true)) {
                throw new Exception('Could not create temporary directory for PDF processing.');
            }

            // Convert PDF to images with error checking
            $cmd = "pdftoppm -r 200 -gray -aa no \"$filePath\" \"$tmpDir/page\" -png 2>&1";
            $output = shell_exec($cmd);

            if ($output !== null && stripos($output, 'error') !== false) {
                throw new Exception('PDF conversion failed. The PDF might be corrupted or password-protected. Error: ' . trim($output));
            }

            $images = glob("$tmpDir/*.png");
            if (empty($images)) {
                throw new Exception('PDF conversion produced no images. The PDF might be empty or corrupted.');
            }

            $text = '';
            $processedPages = 0;

            foreach ($images as $img) {
                if (!file_exists($img)) continue;

                Log::info('OCR processing page', ['page' => basename($img)]);
                $pageText = shell_exec("tesseract \"$img\" stdout -l eng+deu+ara 2>&1");

                // Check for tesseract errors
                if ($pageText && stripos($pageText, 'error') !== false) {
                    Log::warning('Tesseract error on page', ['page' => basename($img), 'error' => $pageText]);
                    continue; // Skip this page but continue with others
                }

                $text .= $pageText . "\n";
                $processedPages++;
            }

            // Cleanup
            array_map('unlink', glob("$tmpDir/*"));
            rmdir($tmpDir);

            if (empty(trim($text))) {
                throw new Exception('No text could be extracted from the PDF. It might contain only images without OCR text, or the quality might be too poor.');
            }

            Log::info('PDF processing completed', ['pages_processed' => $processedPages, 'text_length' => strlen($text)]);
            return trim($text);

        } catch (Exception $e) {
            Log::error('PDF processing failed', ['error' => $e->getMessage(), 'file' => basename($filePath)]);

            // Cleanup temp directory if it exists
            if (isset($tmpDir) && is_dir($tmpDir)) {
                array_map('unlink', glob("$tmpDir/*"));
                @rmdir($tmpDir);
            }

            throw $e; // Re-throw to be caught by caller
        }
    }

    /**
     * Check if a command is available on the system
     */
    private function isCommandAvailable(string $command): bool
    {
        $which = shell_exec("which $command 2>/dev/null");
        return !empty(trim($which));
    }

    /**
     * Extract text from image using tesseract
     */
    private function extractTextFromImage(string $filePath): string
    {
        try {
            Log::info('Running OCR on image');

            // Check if tesseract is available
            if (!$this->isCommandAvailable('tesseract')) {
                throw new Exception('OCR tool (tesseract) is not installed. Please install tesseract-ocr package.');
            }

            // Check if file is readable
            if (!is_readable($filePath)) {
                throw new Exception('Image file is not readable or accessible.');
            }

            // Check file size (images over 20MB might cause issues)
            $fileSize = filesize($filePath);
            if ($fileSize > 20 * 1024 * 1024) { // 20MB
                throw new Exception('Image file is too large (over 20MB). Please use a smaller image or compress it.');
            }

            $text = shell_exec("tesseract \"$filePath\" stdout -l eng+deu+ara 2>&1");

            // Check for tesseract errors
            if ($text && stripos($text, 'error') !== false) {
                throw new Exception('OCR processing failed. The image might be corrupted or in an unsupported format. Error: ' . trim($text));
            }

            $trimmedText = trim($text ?: '');

            if (empty($trimmedText)) {
                throw new Exception('No text could be extracted from the image. It might not contain readable text, or the quality might be too poor.');
            }

            Log::info('Image OCR completed', ['text_length' => strlen($trimmedText)]);
            return $trimmedText;

        } catch (Exception $e) {
            Log::error('Image OCR failed', ['error' => $e->getMessage(), 'file' => basename($filePath)]);
            throw $e; // Re-throw to be caught by caller
        }
    }
    public function generateCompletion(string $extractedText, array $options = []): array
    {
        try {
            $response = $this->callTogetherAI($extractedText);
            return [
                'success' => true,
                'content' => $response,
                'usage' => null,
                'model' => $this->defaultModel,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function analyzeForm(array $formData): array
    {
        return $this->generateCompletion(json_encode($formData));
    }

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
            return false;
        }
    }

    /**
     * Process AI response (simplified)
     */
    private function processAiResponse(array $aiResponse): array
    {
        if (!isset($aiResponse['success']) || !$aiResponse['success']) {
            return [
                'success' => false,
                'error' => $aiResponse['error'] ?? 'AI service error',
                'raw_content' => $aiResponse['content'] ?? ''
            ];
        }

        return [
            'success' => true,
            'data' => $aiResponse['content'] ?? '',
            'usage' => $aiResponse['usage'] ?? null,
            'model' => $aiResponse['model'] ?? null
        ];
    }
}
