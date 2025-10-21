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
    private const DOCUMENT_EXTRACTION_PROMPT = <<<PROMPT
        Extract only factual data from the document and return a single valid, compact JSON object.
        Keep strictly all:
        - Names, addresses, notes, grades, dates, numbers, IDs, tax data, recipients, costs, rates, percentages, amounts, money, contact, banking or any other important details.
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
    public function cleanMarkdownCodeBlocks(string $content): string
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
     * Call TogetherAI API with extracted text
     */
    public function extractDataFrom(string $text): string
    {
        if (!$this->apiKey) {
            return [
                'error' => 'AI service not configured. Please set TOGETHER_API_KEY in your .env file.',
                'success' => false,
                'error_type' => 'configuration'
            ];
        }

        $prompt = self::DOCUMENT_EXTRACTION_PROMPT . "\n" . $text;

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
}
