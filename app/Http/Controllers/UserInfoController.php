<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\UserInfoModel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            $data = UserInfoModel::all();
            return response()->json([
                'data' => $data
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
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                // Set the user_id value for validation
                $request->merge(['user_id' => $user->id]);

                // Validate the request data
                $rules = [
                    'user_id' => 'required|integer',
                    'first_name' => 'required|string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'contact_num' => 'required|nullable|string|max:255|min:11',
                    'address_1' => 'required|nullable|string|max:255',
                    'address_2' => 'nullable|string|max:255',
                    'region_code' => 'nullable|string|max:255',
                    'province_code' => 'nullable|string|max:255',
                    'city_or_municipality_code' => 'nullable|string|max:255',
                    'region_name' => 'required|nullable|string|max:255',
                    'province_name' => 'required|nullable|string|max:255',
                    'city_or_municipality_name' => 'required|nullable|string|max:255',
                    'barangay' => 'required|nullable|string|max:255',
                    'description_location' => 'nullable|string',
                ];

                $validatedData = $request->validate($rules);

                // Create the user info
                $created = UserInfoModel::create($validatedData);

                if ($created) {
                    $userAction = 'CREATED';
                    $details = 'Created User Information';

                    // Create Log
                    LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => now()
                    ]);

                    // Return a success response with CORS headers
                    return response()->json([
                        'message' => 'Created'
                    ], Response::HTTP_OK);
                }
            } else {
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Intruder'
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        try {
            $user = AuthModel::where('session_login', $id)
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $data = UserInfoModel::where('user_id', $user->id)->get();
                return response()->json([
                    'data' => $data
                ], Response::HTTP_OK); // Change the status code to 200 (OK)
            } else {
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Intruder'
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
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
        try {
            $user = AuthModel::where('session_login', $id)
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $data = UserInfoModel::where('user_id', $user->id)->first();
                return response()->json([
                    'data' => $data
                ], Response::HTTP_OK); // Change the status code to 200 (OK)
            } else {
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Intruder'
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
        try {
            $data = UserInfoModel::find($id);
            if (!$data) {
                return response()->json([
                    'messages' => 'Data Not Found'
                ], Response::HTTP_OK);
            }

            // Fetch User I.D
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                // Validate the Input
                $validatedData = $request->validate([
                    'first_name' => 'string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'string|max:255',
                    'contact_num' => 'nullable|string|max:255|min:11',
                    'address_1' => 'nullable|string|max:255',
                    'address_2' => 'nullable|string|max:255',
                    'region_code' => 'nullable|string|max:255',
                    'province_code' => 'nullable|string|max:255',
                    'city_or_municipality_code' => 'nullable|string|max:255',
                    'region_name' => 'nullable|string|max:255',
                    'province_name' => 'nullable|string|max:255',
                    'city_or_municipality_name' => 'nullable|string|max:255',
                    'barangay' => 'nullable|string|max:255',
                    'description_location' => 'nullable|string',
                ]);

                // Update account properties
                $data->fill($validatedData);
                $changes = [];

                if ($data->isDirty('first_name')) {
                    $changes[] = 'First Name changed from "' . $data->getOriginal('first_name') . '" to "' . $data->first_name . '".';
                }
                if ($data->isDirty('middle_name')) {
                    $changes[] = 'Middle Name changed from "' . $data->getOriginal('middle_name') . '" to "' . $data->middle_name . '".';
                }
                if ($data->isDirty('last_name')) {
                    $changes[] = 'Last Name changed from "' . $data->getOriginal('last_name') . '" to "' . $data->last_name . '".';
                }
                if ($data->isDirty('contact_num')) {
                    $changes[] = 'Contact Number changed from "' . $data->getOriginal('contact_num') . '" to "' . $data->contact_num . '".';
                }
                if ($data->isDirty('address_1')) {
                    $changes[] = 'Address 1 changed from "' . $data->getOriginal('address_1') . '" to "' . $data->address_1 . '".';
                }
                if ($data->isDirty('address_2')) {
                    $changes[] = 'Address 2 changed from "' . $data->getOriginal('address_2') . '" to "' . $data->address_2 . '".';
                }
                if ($data->isDirty('address_2')) {
                    $changes[] = 'Region changed from "' . $data->getOriginal('region_name') . '" to "' . $data->region_name . '".';
                }
                if ($data->isDirty('province_name')) {
                    $changes[] = 'Province changed from "' . $data->getOriginal('province_name') . '" to "' . $data->province_name . '".';
                }
                if ($data->isDirty('city_or_municipality_name')) {
                    $changes[] = 'City / Municipality from "' . $data->getOriginal('city_or_municipality_name') . '" to "' . $data->city_or_municipality_name . '".';
                }
                if ($data->isDirty('barangay')) {
                    $changes[] = 'Barangay from "' . $data->getOriginal('barangay') . '" to "' . $data->barangay . '".';
                }
                if ($data->isDirty('barangay')) {
                    $changes[] = 'Description Location from "' . $data->getOriginal('description_location') . '" to "' . $data->description_location . '".';
                }

                if (empty($changes)) {
                    return response()->json([
                        'message' => 'No changes to update.'
                    ], Response::HTTP_OK);
                }

                if ($data->save()) {
                    if ($user) {
                        $userAction = 'UPDATE';
                        $details = 'Updated an Account with the following changes: ' . implode(' ', $changes);

                        // Create Log
                        LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        return response()->json([
                            'message' => 'Updated'
                        ], Response::HTTP_OK);
                    }
                }
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
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}