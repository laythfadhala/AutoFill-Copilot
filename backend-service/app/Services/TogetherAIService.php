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
    private string $apiKey;
    private string $defaultModel;

    public function __construct()
    {
        $this->apiUrl = config('services.together.url', 'https://api.together.xyz/v1/chat/completions');
        $this->apiKey = config('services.together.key');
        $this->defaultModel = config('services.together.model', 'meta-llama/Llama-3.3-70B-Instruct-Turbo-Free');
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
}
