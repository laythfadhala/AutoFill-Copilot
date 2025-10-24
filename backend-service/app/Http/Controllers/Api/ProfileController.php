<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get all profiles for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $profiles = UserProfile::where('user_id', auth()->id())
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'is_default']);

            return response()->json([
                'success' => true,
                'data' => $profiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profiles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
