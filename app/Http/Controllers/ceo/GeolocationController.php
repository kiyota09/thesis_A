<?php

namespace App\Http\Controllers\ceo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\CeoLocation;
use Exception;

class GeolocationController extends Controller 
{
    /**
     * Display the Geolocation Hub.
     */
    public function index() 
    {
        // Fetch last saved location to show on the map initially
        $lastLocation = CeoLocation::where('user_id', auth()->id())
            ->latest()
            ->first();

        return Inertia::render('Dashboard/CEO/Geolocation', [
            'savedLocation' => $lastLocation
        ]);
    }

    /**
     * Store the high-precision coordinates and range.
     */
    public function store(Request $request) 
    {
        // dd() removed as it breaks Axios/JSON responses
        
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'range_radius' => 'required|integer|min:1',
            'label' => 'nullable|string|max:255',
        ]);

        try {
            $location = CeoLocation::create([
                'user_id' => auth()->id(),
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'range_radius' => $validated['range_radius'],
                'label' => $validated['label'] ?? 'Manual Log',
            ]);

            // Log the success for auditing
            Log::info("CEO Geolocation Archived", [
                'user_id' => auth()->id(),
                'location_id' => $location->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Location and range successfully archived.',
                'data' => $location
            ]);

        } catch (Exception $e) {
            Log::error("Failed to archive CEO Geolocation: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Database failure. Please verify migrations.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}