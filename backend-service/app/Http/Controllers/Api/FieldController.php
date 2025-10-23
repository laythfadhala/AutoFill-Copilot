<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Services\TogetherAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FieldController extends Controller
{
    /**
     * Fill a single form field with AI-generated data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fill(Request $request, TogetherAIService $aiService)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:512',
                'type' => 'nullable|string|max:50',
                'placeholder' => 'nullable|string|max:512',
                'label' => 'nullable|string|max:512',
                'options' => 'nullable|array',
                'options.*.value' => 'nullable|string|max:512',
                'options.*.text' => 'nullable|string|max:512',
                'options.*.selected' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fieldData = $request->all();

            // Log the field filling request
            Log::info('Single field filling requested', [
                'user_id' => auth()->id(),
                'field_name' => $fieldData['name'],
                'field_type' => $fieldData['type'] ?? 'text',
                'field_label' => $fieldData['label'] ?? null
            ]);

            // Get user's default active profile data
            $userProfile = UserProfile::where('user_id', auth()->id())
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();

            $profileData = null;
            if ($userProfile && $userProfile->data) {
                $profileData = $userProfile->data;
                Log::info('Using user profile data for field filling', [
                    'profile_id' => $userProfile->id,
                    'profile_name' => $userProfile->name,
                    'data_fields' => count($profileData)
                ]);
            } else {
                Log::info('No active default profile found, using AI generation only');
            }

            // Create a minimal form structure for the AI service
            $formData = [
                'url' => '', // Not needed for single field
                'title' => '',
                'forms' => [
                    [
                        'id' => 'singleField',
                        'action' => '',
                        'method' => 'GET',
                        'fields' => [$fieldData]
                    ]
                ],
                'timestamp' => now()->toISOString()
            ];

            $filledData = $aiService->fillForm($formData, $profileData);

            // Check if AI service returned an error
            if (isset($filledData['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI service error',
                    'error' => $filledData['error'],
                    'error_type' => $filledData['error_type'] ?? 'unknown'
                ], 500);
            }

            // Extract the filled value for this specific field
            $fieldName = $fieldData['name'];
            $filledValue = $filledData[$fieldName] ?? null;

            if ($filledValue === null) {
                Log::warning('AI service did not provide a value for the field', [
                    'field_name' => $fieldName,
                    'filled_data_keys' => array_keys($filledData)
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to generate appropriate content for this field'
                ], 400);
            }

            // Structure the response to match what the extension expects
            $response = [
                'success' => true,
                'filledValue' => $filledValue,
                'message' => $profileData
                    ? 'Field filled successfully using your profile data and AI assistance'
                    : 'Field filled successfully with AI-generated data'
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Field filling error', [
                'user_id' => auth()->id(),
                'field_name' => $request->input('name'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while filling the field',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
