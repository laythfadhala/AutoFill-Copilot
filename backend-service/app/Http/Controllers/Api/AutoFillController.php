<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\FormMapping;
use App\Services\TogetherAIService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class AutoFillController extends Controller
{
    private TogetherAIService $aiService;

    public function __construct(TogetherAIService $aiService)
    {
        $this->aiService = $aiService;
    }
    /**
     * Main autofill endpoint - analyzes form and returns suggested data
     *
     * This is the core autofill functionality that:
     * 1. Analyzes form fields using intelligent pattern matching
     * 2. Maps fields to user profile data
     * 3. Creates/updates form mappings in the database
     * 4. Returns suggested values for form filling
     */
    public function autofill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'form_fields' => 'required|array',
            'form_fields.*.name' => 'required|string',
            'form_fields.*.type' => 'sometimes|string',
            'form_fields.*.id' => 'sometimes|string',
            'form_fields.*.class' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $userId = $request->user()->id;
        $domain = parse_url($request->url, PHP_URL_HOST);
        $formFields = $request->form_fields;

        // Get user's default profile data
        $profileData = $this->getUserProfileData($userId);

        if (!$profileData) {
            return response()->json([
                'success' => false,
                'message' => 'No active profile found. Please create a profile first.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check for existing form mapping
        $mapping = FormMapping::where('user_id', $userId)
            ->where('domain', $domain)
            ->first();

        if ($mapping) {
            // Use existing mapping and track usage
            $fieldMappings = $this->applyExistingMapping($formFields, $mapping->field_mappings, $profileData);

            // Track usage
            $mapping->increment('usage_count');
            $mapping->update(['last_used_at' => now()]);
        } else {
            // Create new intelligent mapping
            $fieldMappings = $this->createIntelligentMapping($formFields, $profileData);

            // Save the mapping for future use
            FormMapping::create([
                'user_id' => $userId,
                'domain' => $domain,
                'field_mappings' => $this->extractFieldMappings($fieldMappings),
                'form_config' => [
                    'auto_submit' => false,
                    'confirm_before_fill' => true,
                ],
                'usage_count' => 1,
                'last_used_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Form analysis complete',
            'data' => [
                'field_mappings' => $fieldMappings,
                'profile_data' => $profileData,
                'domain' => $domain,
                'mapped_fields_count' => count(array_filter($fieldMappings, fn($m) => $m['suggested_value'] !== null))
            ]
        ]);
    }

    /**
     * Analyze form fields without filling
     */
    public function analyzeForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_fields' => 'required|array',
            'form_fields.*.name' => 'required|string',
            'form_fields.*.type' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $formFields = $request->form_fields;
        $analysis = [];

        foreach ($formFields as $field) {
            $fieldType = $this->detectFieldType($field);
            $analysis[] = [
                'field_name' => $field['name'],
                'detected_type' => $fieldType,
                'fillable' => $fieldType !== 'unknown',
                'confidence' => $this->getFieldTypeConfidence($field, $fieldType)
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Form analysis complete',
            'data' => [
                'form_fields' => $analysis,
                'total_fields' => count($formFields),
                'fillable_fields' => count(array_filter($analysis, fn($a) => $a['fillable']))
            ]
        ]);
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    /**
     * Get user's profile data (default profile or demo data)
     */
    private function getUserProfileData($userId)
    {
        $profile = UserProfile::where('user_id', $userId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($profile) {
            return $profile->data;
        }

        // Return demo data if no profile exists
        return [
            'firstName' => 'Demo',
            'lastName' => 'User',
            'fullName' => 'Demo User',
            'email' => 'demo@test.com',
            'phone' => '555-123-4567',
            'address' => '123 Demo Street',
            'city' => 'Demo City',
            'state' => 'CA',
            'zipCode' => '12345',
            'country' => 'United States',
            'company' => 'AutoFill Copilot Inc.',
            'jobTitle' => 'Software Developer'
        ];
    }

    /**
     * Create intelligent field mapping using pattern matching
     */
    private function createIntelligentMapping($formFields, $profileData)
    {
        $mappings = [];

        foreach ($formFields as $field) {
            $fieldType = $this->detectFieldType($field);
            $suggestedValue = $this->getSuggestedValue($fieldType, $profileData);

            $mappings[] = [
                'field_name' => $field['name'],
                'field_id' => $field['id'] ?? null,
                'detected_type' => $fieldType,
                'suggested_value' => $suggestedValue,
                'confidence' => $this->getFieldTypeConfidence($field, $fieldType)
            ];
        }

        return $mappings;
    }

    /**
     * Apply existing mapping to form fields
     */
    private function applyExistingMapping($formFields, $existingMapping, $profileData)
    {
        $mappings = [];

        foreach ($formFields as $field) {
            // Find existing mapping by field name
            $existingField = collect($existingMapping)->firstWhere('field_name', $field['name']);

            if ($existingField) {
                $suggestedValue = $this->getSuggestedValue($existingField['detected_type'], $profileData);
                $mappings[] = array_merge($existingField, [
                    'suggested_value' => $suggestedValue
                ]);
            } else {
                // New field, create mapping
                $fieldType = $this->detectFieldType($field);
                $mappings[] = [
                    'field_name' => $field['name'],
                    'field_id' => $field['id'] ?? null,
                    'detected_type' => $fieldType,
                    'suggested_value' => $this->getSuggestedValue($fieldType, $profileData),
                    'confidence' => $this->getFieldTypeConfidence($field, $fieldType)
                ];
            }
        }

        return $mappings;
    }

    /**
     * Detect field type based on field attributes
     */
    private function detectFieldType($field)
    {
        $fieldName = strtolower($field['name'] ?? '');
        $fieldId = strtolower($field['id'] ?? '');
        $fieldType = strtolower($field['type'] ?? 'text');

        // Combine all identifiers
        $identifiers = implode(' ', [$fieldName, $fieldId]);

        // Pattern matching for field types
        if (str_contains($identifiers, 'email') || $fieldType === 'email') {
            return 'email';
        }
        if (str_contains($identifiers, 'first') && str_contains($identifiers, 'name')) {
            return 'firstName';
        }
        if (str_contains($identifiers, 'last') && str_contains($identifiers, 'name')) {
            return 'lastName';
        }
        if (str_contains($identifiers, 'name') && !str_contains($identifiers, 'user')) {
            return 'fullName';
        }
        if (str_contains($identifiers, 'phone') || str_contains($identifiers, 'tel') || $fieldType === 'tel') {
            return 'phone';
        }
        if (str_contains($identifiers, 'address') || str_contains($identifiers, 'street')) {
            return 'address';
        }
        if (str_contains($identifiers, 'city')) {
            return 'city';
        }
        if (str_contains($identifiers, 'state') || str_contains($identifiers, 'province')) {
            return 'state';
        }
        if (str_contains($identifiers, 'zip') || str_contains($identifiers, 'postal')) {
            return 'zipCode';
        }
        if (str_contains($identifiers, 'country')) {
            return 'country';
        }
        if (str_contains($identifiers, 'company') || str_contains($identifiers, 'organization')) {
            return 'company';
        }
        if (str_contains($identifiers, 'job') || str_contains($identifiers, 'title') || str_contains($identifiers, 'position')) {
            return 'jobTitle';
        }

        return 'unknown';
    }

    /**
     * Get suggested value for field type from profile data
     */
    private function getSuggestedValue($fieldType, $profileData)
    {
        return match($fieldType) {
            'firstName' => $profileData['firstName'] ?? null,
            'lastName' => $profileData['lastName'] ?? null,
            'fullName' => $profileData['fullName'] ?? null,
            'email' => $profileData['email'] ?? null,
            'phone' => $profileData['phone'] ?? null,
            'address' => $profileData['address'] ?? null,
            'city' => $profileData['city'] ?? null,
            'state' => $profileData['state'] ?? null,
            'zipCode' => $profileData['zipCode'] ?? null,
            'country' => $profileData['country'] ?? null,
            'company' => $profileData['company'] ?? null,
            'jobTitle' => $profileData['jobTitle'] ?? null,
            default => null
        };
    }

    /**
     * Get confidence score for field type detection
     */
    private function getFieldTypeConfidence($field, $detectedType)
    {
        if ($detectedType === 'unknown') return 0;

        $fieldName = strtolower($field['name'] ?? '');
        $fieldId = strtolower($field['id'] ?? '');

        // Higher confidence for exact matches
        if (str_contains($fieldName, $detectedType) || str_contains($fieldId, $detectedType)) {
            return 90;
        }

        // Medium confidence for partial matches
        return 70;
    }

    /**
     * Extract field mappings for database storage
     */
    private function extractFieldMappings($fieldMappings)
    {
        return collect($fieldMappings)->map(function ($mapping) {
            return [
                'field_name' => $mapping['field_name'],
                'field_id' => $mapping['field_id'] ?? null,
                'detected_type' => $mapping['detected_type'],
                'confidence' => $mapping['confidence']
            ];
        })->toArray();
    }

    /**
     * AI-powered form analysis endpoint
     */
    public function analyzeWithAi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_fields' => 'required|array',
            'form_fields.*.name' => 'required|string',
            'form_fields.*.type' => 'sometimes|string',
            'form_fields.*.placeholder' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $formFields = $request->form_fields;

        // Use AI service to analyze form fields
        $aiResult = $this->aiService->analyzeForm($formFields);

        if (!$aiResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'AI analysis failed',
                'error' => $aiResult['error']
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'success' => true,
            'analysis' => $aiResult['content'],
            'usage' => $aiResult['usage'] ?? null,
            'model' => $aiResult['model']
        ]);
    }
}
