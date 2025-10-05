<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    /**
     * Display user profiles (optionally filtered by user).
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserProfile::with('user:id,name,email');

        // Filter by user if specified
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by type if specified
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $profiles = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $profiles,
        ]);
    }

    /**
     * Store a newly created user profile.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:personal,business,shipping,work',
            'data' => 'required|array',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // If this is set as default, unset other defaults for this user
        if ($validated['is_default'] ?? false) {
            UserProfile::where('user_id', $validated['user_id'])
                ->update(['is_default' => false]);
        }

        $profile = UserProfile::create($validated);
        $profile->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'User profile created successfully',
            'data' => $profile,
        ], 201);
    }

    /**
     * Display the specified user profile.
     */
    public function show(UserProfile $userProfile): JsonResponse
    {
        $userProfile->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $userProfile,
        ]);
    }

    /**
     * Update the specified user profile.
     */
    public function update(Request $request, UserProfile $userProfile): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:personal,business,shipping,work',
            'data' => 'sometimes|array',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        // If this is being set as default, unset other defaults for this user
        if (($validated['is_default'] ?? false) && !$userProfile->is_default) {
            UserProfile::where('user_id', $userProfile->user_id)
                ->where('id', '!=', $userProfile->id)
                ->update(['is_default' => false]);
        }

        $userProfile->update($validated);
        $userProfile->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'User profile updated successfully',
            'data' => $userProfile,
        ]);
    }

    /**
     * Remove the specified user profile.
     */
    public function destroy(UserProfile $userProfile): JsonResponse
    {
        $userProfile->delete();

        return response()->json([
            'success' => true,
            'message' => 'User profile deleted successfully',
        ]);
    }

    /**
     * Get default profile for a user.
     */
    public function getDefault(User $user): JsonResponse
    {
        $defaultProfile = $user->userProfiles()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if (!$defaultProfile) {
            return response()->json([
                'success' => false,
                'message' => 'No default profile found for this user',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $defaultProfile,
        ]);
    }
}
