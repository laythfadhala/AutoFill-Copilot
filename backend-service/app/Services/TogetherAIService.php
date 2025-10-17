<?php

namespace App\Services;

use App\Services\TextExtraction\TextExtractionService;
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
        - Output only flattened raw valid JSON, no markdown or text, no explanations, no white spaces.
        PROMPT;
    private string $apiUrl;
    private ?string $apiKey;
    private string $defaultModel;
    private TextExtractionService $textExtractor;

    public function __construct()
    {
        $this->textExtractor = app(TextExtractionService::class);
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
        // Remove ```json or ``` markers
        $content = preg_replace('/^```(?:json)?\s*$/m', '', $content);
        $content = preg_replace('/^```\s*$/m', '', $content);

        // Find the first '{' and last '}' to extract JSON content
        $startPos = strpos($content, '{');
        $endPos = strrpos($content, '}');

        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $jsonContent = substr($content, $startPos, $endPos - $startPos + 1);
            return trim($jsonContent);
        }

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

            // Check if MIME type is supported
            if (!$this->textExtractor->supportsMimeType($mimeType)) {
                return [
                    'error' => 'Unsupported file type: ' . $mimeType . '. Supported types: PDF, images, and plain text files.',
                    'success' => false,
                    'error_type' => 'unsupported_file_type'
                ];
            }

            // Extract text using the text extraction service
            $text = $this->textExtractor->extractText($filePath, $mimeType);

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
            throw new Exception('AI API request failed with status ' . $response->status() . ': ' . $response->body());
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
}
