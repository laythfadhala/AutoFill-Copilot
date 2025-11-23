<?php

namespace App\Http\Controllers\Api;

use App\Enums\TokenAction;
use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Services\TogetherAIService;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    /**
     * Fill form fields with AI-generated data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fill(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'url' => 'required|url',
                'title' => 'required|string|max:512',
                'forms' => 'required|array',
                'forms.*.id' => 'required',
                'forms.*.action' => 'nullable|url',
                'forms.*.method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE',
                'forms.*.fields' => 'array',
                'forms.*.fields.*.name' => 'nullable|string|max:512',
                'forms.*.fields.*.type' => 'nullable|string|max:50',
                'forms.*.fields.*.placeholder' => 'nullable|string|max:512',
                'forms.*.fields.*.label' => 'nullable|string|max:512',
                'forms.*.fields.*.options' => 'nullable|array',
                'forms.*.fields.*.options.*.value' => 'nullable|string|max:512',
                'forms.*.fields.*.options.*.text' => 'nullable|string|max:512',
                'forms.*.fields.*.options.*.selected' => 'nullable|boolean',
                'timestamp' => 'required|date',
                'profile_id' => 'nullable|integer|exists:user_profiles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $formData = $request->all();

            // Log the form filling request
            Log::info('Form filling requested', [
                'user_id' => auth()->id(),
                'url' => $formData['url'],
                'title' => $formData['title'],
                'forms_count' => count($formData['forms']),
                'timestamp' => $formData['timestamp'],
                'profile_id' => $formData['profile_id'] ?? null,
            ]);

            // Get user profile data - use specified profile or default active profile
            $userProfile = null;
            if (isset($formData['profile_id'])) {
                $userProfile = UserProfile::where('user_id', auth()->id())
                    ->where('id', $formData['profile_id'])
                    ->where('is_active', true)
                    ->first();
            } else {
                $userProfile = UserProfile::where('user_id', auth()->id())
                    ->where('is_active', true)
                    ->where('is_default', true)
                    ->first();
            }

            $profileData = null;
            if ($userProfile && $userProfile->data) {
                $profileData = $userProfile->data;
                Log::info('Using user profile data for form filling', [
                    'profile_id' => $userProfile->id,
                    'profile_name' => $userProfile->name,
                    'data_fields' => count($profileData),
                    'is_default' => $userProfile->is_default,
                ]);
            } else {
                Log::info('No active profile found, using AI generation only');
            }

            // Calculate total fields for metadata
            $totalFields = collect($formData['forms'])->sum(function ($form) {
                return count($form['fields']);
            });

            // Use AI service to fill the forms with user profile data
            $aiService = new TogetherAIService;
            $result = $aiService->fillForm($formData, $profileData);

            // Check if AI service returned an error
            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI service error',
                    'error' => $result['error'],
                    'error_type' => $result['error_type'] ?? 'unknown',
                ], 500);
            }

            $filledData = $result['data'];
            $aiUsage = $result['usage'];

            // Consume actual tokens used by AI
            $tokenUsage = TokenService::consumeActualTokens(
                auth()->user(),
                TokenAction::FORM_FILL,
                $aiUsage,
                [
                    'url' => $formData['url'],
                    'forms_count' => count($formData['forms']),
                    'fields_count' => $totalFields,
                    'profile_used' => $profileData ? true : false,
                    'profile_id' => $userProfile ? $userProfile->id : null,
                ]
            );

            $tokensUsed = $tokenUsage->tokens_used;

            // Structure the response with filled form data
            $response = [
                'success' => true,
                'message' => $profileData
                    ? 'Form filled successfully using your profile data and AI assistance'
                    : 'Form filled successfully with AI-generated data',
                'data' => [
                    'url' => $formData['url'],
                    'title' => $formData['title'],
                    'forms_filled' => count($formData['forms']),
                    'total_fields' => $totalFields,
                    'tokens_used' => $tokensUsed,
                    'filled_data' => $filledData,
                    'profile_used' => $profileData ? true : false,
                    'profile_id' => $userProfile ? $userProfile->id : null,
                    'profile_name' => $userProfile ? $userProfile->name : null,
                    'processed_at' => now()->toISOString(),
                ],
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Form filling error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while filling form data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
