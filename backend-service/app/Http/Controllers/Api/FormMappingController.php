<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FormMapping;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FormMappingController extends Controller
{
    /**
     * Display form mappings (optionally filtered by user or domain).
     */
    public function index(Request $request): JsonResponse
    {
        $query = FormMapping::with('user:id,name,email');

        // Filter by user if specified
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by domain if specified
        if ($request->has('domain')) {
            $query->where('domain', 'like', '%' . $request->domain . '%');
        }

        // Order by usage count and last used
        $query->orderByDesc('usage_count')->orderByDesc('last_used_at');

        $mappings = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $mappings,
        ]);
    }

    /**
     * Store a newly created form mapping.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'domain' => 'required|string|max:255',
            'form_selector' => 'nullable|string',
            'field_mappings' => 'required|array',
            'form_config' => 'nullable|array',
        ]);

        // Initialize usage tracking
        $validated['usage_count'] = 0;
        $validated['last_used_at'] = null;

        $mapping = FormMapping::create($validated);
        $mapping->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Form mapping created successfully',
            'data' => $mapping,
        ], 201);
    }

    /**
     * Display the specified form mapping.
     */
    public function show(FormMapping $formMapping): JsonResponse
    {
        $formMapping->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'data' => $formMapping,
        ]);
    }

    /**
     * Update the specified form mapping.
     */
    public function update(Request $request, FormMapping $formMapping): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'sometimes|string|max:255',
            'form_selector' => 'nullable|string',
            'field_mappings' => 'sometimes|array',
            'form_config' => 'nullable|array',
        ]);

        $formMapping->update($validated);
        $formMapping->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Form mapping updated successfully',
            'data' => $formMapping,
        ]);
    }

    /**
     * Remove the specified form mapping.
     */
    public function destroy(FormMapping $formMapping): JsonResponse
    {
        $formMapping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Form mapping deleted successfully',
        ]);
    }

    /**
     * Get form mappings by domain for a specific user.
     */
    public function getByDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'domain' => 'required|string',
        ]);

        $mappings = FormMapping::where('user_id', $validated['user_id'])
            ->where('domain', $validated['domain'])
            ->orderByDesc('usage_count')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $mappings,
        ]);
    }

    /**
     * Track usage of a form mapping.
     */
    public function trackUsage(FormMapping $formMapping): JsonResponse
    {
        $formMapping->increment('usage_count');
        $formMapping->update(['last_used_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Usage tracked successfully',
            'data' => [
                'usage_count' => $formMapping->usage_count,
                'last_used_at' => $formMapping->last_used_at,
            ],
        ]);
    }

    /**
     * Get popular domains for a user.
     */
    public function getPopularDomains(User $user): JsonResponse
    {
        $popularDomains = FormMapping::where('user_id', $user->id)
            ->select('domain')
            ->selectRaw('SUM(usage_count) as total_usage')
            ->selectRaw('COUNT(*) as mapping_count')
            ->groupBy('domain')
            ->orderByDesc('total_usage')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $popularDomains,
        ]);
    }
}
