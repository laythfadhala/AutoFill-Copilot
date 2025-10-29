<?php

namespace App\Services;

use App\Traits\SanitizesJsonStrings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TogetherAIService
{
    use SanitizesJsonStrings;
    /**
     * Unified extraction prompt for document factual data
     */
    private const DOCUMENT_EXTRACTION_PROMPT = <<<PROMPT
        Extract all factual data from the document and return a single valid, compact JSON object.
        Keep strictly all:
        - Names, addresses, notes, grades, dates, numbers, IDs, tax data, recipients, costs, rates, percentages, amounts, money, contact, banking or any other important details.
        Remove completely:
        - Any paragraphs or sentences explaining reasons, laws, legal rights, appeals, data protection, privacy, or instructions.
        Formatting rules:
        - Do not omit any key:value pair, include all extracted data in the output json.
        - Response must be in the same language as the document including the keys.
        - Response must always have 'Title' of the document.
        - Flatten the structure into one dimension key:value.
        - Output only flattened raw valid JSON, no markdown or text, no explanations.
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
            Log::error('TogetherAI API key not configured');
            return [
                'error' => 'AI service not configured. Please set TOGETHER_API_KEY in your .env file.',
                'success' => false,
                'error_type' => 'configuration'
            ];
        }
    }

    /**
     * Call TogetherAI API with extracted text
     */
    public function extractDataFrom(string $text): string
    {
        $prompt = self::DOCUMENT_EXTRACTION_PROMPT . "\n" . $text;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->apiUrl, [
            'model' => $this->defaultModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 10000,
            'temperature' => 0.2
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Clean markdown code blocks from response
            $content = $this->sanitizeResponse($content);

            Log::info('AI response received', ['length' => strlen($content)]);
            return $content;
        } else {
            Log::error('AI API request failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new Exception('AI API request failed with status ' . $response->status() . ': ' . $response->body());
        }
    }

    /**
     * Fill form fields with user profile data and AI assistance
     */
    public function fillForm(array $formData, ?array $userProfileData = null): array
    {
        // Build the prompt with form field information and user data
        $prompt = "Fill the following form fields using the user's data provided below. If a field can be filled from the user data, use that exact information.\n\n";

        // Add user profile data if available
        if ($userProfileData && !empty($userProfileData)) {
            $prompt .= "USER PROFILE DATA:\n";
            foreach ($userProfileData as $key => $value) {
                if (is_array($value)) {
                    $prompt .= "- {$key}: " . json_encode($value) . "\n";
                } else {
                    $prompt .= "- {$key}: {$value}\n";
                }
            }
            $prompt .= "\n";
        }

        $prompt .= "FORM FIELDS TO FILL:\n";

        foreach ($formData['forms'] as $form) {
            $prompt .= "\nForm: " . ($form['action'] ?? 'Unknown') . "\n";
            foreach ($form['fields'] as $field) {
                $fieldDesc = "- {$field['name']} ({$field['type']})";
                if (!empty($field['label'])) {
                    $fieldDesc .= ": {$field['label']}";
                }
                if (!empty($field['placeholder'])) {
                    $fieldDesc .= " [{$field['placeholder']}]";
                }
                if (!empty($field['options']) && is_array($field['options'])) {
                    $options = array_map(function($opt) {
                        return $opt['text'] ?? $opt['value'];
                    }, $field['options']);
                    $fieldDesc .= " Options: " . implode(', ', $options);
                }
                $prompt .= $fieldDesc . "\n";
            }
        }

        $prompt .= "\nINSTRUCTIONS:\n";
        $prompt .= "- Do not skip or remove any field\n";
        $prompt .= "- Never return a nested JSON, always flat key:value pairs\n";
        $prompt .= "- Use the user's actual data when available and relevant\n";
        $prompt .= "- For select dropdowns: choose the most appropriate option from available choices\n";
        $prompt .= "- For text areas: generate 1-2 sentences of relevant content\n";
        $prompt .= "- Return only valid JSON with field names as keys and filled values as values\n";
        $prompt .= "- No explanations, no markdown, just the JSON object\n";

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
            'max_tokens' => 5000,
            'temperature' => 0.2
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? '';

            // Clean markdown code blocks from response
            $content = $this->sanitizeResponse($content);

            // Parse the JSON response
            $filledData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse AI form filling response', [
                    'content' => $content,
                    'error' => json_last_error_msg()
                ]);
                throw new Exception('Failed to parse AI response as JSON');
            }

            Log::info('Form filled with user profile data and AI assistance', [
                'field_count' => count($filledData),
                'has_profile_data' => !empty($userProfileData)
            ]);

            return $filledData;
        } else {
            Log::error('AI form filling request failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new Exception('AI form filling request failed with status ' . $response->status() . ': ' . $response->body());
        }
    }
}
