<?php

namespace App\Http\Controllers;

use App\Models\LogsModel;
use App\Models\UserInfoModel;
use App\Models\AuthModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class LogsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Use meaningful variable names
            $logList = LogsModel::get();
        
            // Initialize an array to store the final data
            $data = [];
        
            foreach ($logList as $logData) {
                $authInfo = AuthModel::where('id', $logData->user_id)->first();
                // $userInfo = UserInfoModel::where('user_id', $logData->user_id)->first();

        
                // Check if userInfo exists before attempting to decrypt
                if ($authInfo) {
                    // Create an array for the current user data in the desired format
                    $fetchData = [
                        'id' => $logData->id,
                        'details' => $logData->details,
                        'user_action' => $logData->user_action,
                        'ip_address' => $logData->ip_address,
                        'created_at' => $logData->created_at,
        
                        'authInfo' => [
                            'email' => $authInfo->email ?? null,
                        ],
                        // 'userInfo' => [
                        //     'first_name' => $userInfo->first_name ? Crypt::decrypt($userInfo->first_name) : null,
                        //     'last_name' => $userInfo->last_name ? Crypt::decrypt($userInfo->last_name) : null,
                        // ],
                    ];
        
                    $data[] = $fetchData;
                }
            }
        
            // Return the user information records in JSON format
            return response()->json([
                'message' => $data
            ], Response::HTTP_OK);
        }catch (\Exception $e) {
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
    public function create()
    {
        //
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
