<?php

namespace App\Http\Controllers;

use App\Models\VisitorModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Stevebauman\Location\Facades\Location;


class VisitorsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Query all visitors
            $allVisitors = VisitorModel::all();
            $totalVisitorsCount = $allVisitors->count();

            // Query visitors for today's date
            $todaysVisitors = VisitorModel::whereDate('created_at', Carbon::now())->get();

            return response()->json([
                'total_visitors' => $totalVisitorsCount,
                'todays_visitors' => $todaysVisitors,
                'visitors' => $allVisitors
            ], Response::HTTP_OK); // Change the status code to 200 (OK)
        } catch (\Exception $e) {
            // Handle exceptions and return an error response with CORS headers
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();

            // Create a JSON error response
            $response = [
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $errorMessage,
                ],
            ];

            // Add additional error details if available
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $response['error']['details'] = $e->errors();
            }

            // Return the JSON error response with CORS headers and an appropriate HTTP status code
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR)->header('Content-Type', 'application/json');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $ipAddress = $request->ip();

            // Find or create a visitor record by IP address
            $visitor = VisitorModel::firstOrNew(['ip_address' => $ipAddress]);

            // Check if the visitor was recorded today
            $today = Carbon::now()->format('Y-m-d');

            if ($visitor->recorded_date !== $today) {
                // Record the visitor for today
                $visitor->recorded_date = $today;
                $visitor->save();

                return response()->json([
                    'message' => 'Visitor information recorded successfully',
                    'visitor' => $visitor,
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Visitor already recorded today',
                    'visitor' => $visitor,
                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            // Handle exceptions and return an error response with CORS headers
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();

            // Create a JSON error response
            $response = [
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => $errorMessage,
                ],
            ];

            // Add additional error details if available
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $response['error']['details'] = $e->errors();
            }

            // Return the JSON error response with CORS headers and an appropriate HTTP status code
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR)->header('Content-Type', 'application/json');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}