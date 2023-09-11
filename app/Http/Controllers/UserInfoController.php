<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\UserInfoModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
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
            $decryptedData = [];

            foreach ($data as $item) {
                $decryptedItem = [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'first_name' => Crypt::decrypt($item->first_name),
                    // Decrypt 'first_name' column
                    'middle_name' => Crypt::decrypt($item->middle_name),
                    // Decrypt 'middle_name' column
                    'last_name' => Crypt::decrypt($item->last_name),
                    // Decrypt 'last_name' column
                    'contact_num' => Crypt::decrypt($item->contact_num),
                    // Decrypt 'contact_num' column
                    'address_1' => Crypt::decrypt($item->address_1),
                    // Decrypt 'address_1' column
                    'address_2' => Crypt::decrypt($item->address_2),
                    // Decrypt 'address_2' column
                    'region_code' => Crypt::decrypt($item->region_code),
                    // Decrypt 'region_code' column
                    'province_code' => Crypt::decrypt($item->province_code),
                    // Decrypt 'province_code' column
                    'city_or_municipality_code' => Crypt::decrypt($item->city_or_municipality_code),
                    // Decrypt 'city_or_municipality_code' column
                    'region_name' => Crypt::decrypt($item->region_name),
                    // Decrypt 'region_name' column
                    'province_name' => Crypt::decrypt($item->province_name),
                    // Decrypt 'province_name' column
                    'city_or_municipality_name' => Crypt::decrypt($item->city_or_municipality_name),
                    // Decrypt 'city_or_municipality_name' column
                    'barangay' => Crypt::decrypt($item->barangay),
                    // Decrypt 'barangay' column
                    'description_location' => Crypt::decrypt($item->description_location),
                    // Decrypt 'description_location' column
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];

                $decryptedData[] = $decryptedItem;
            }

            return response()->json([
                'data' => $decryptedData
            ], Response::HTTP_OK);
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

                // Encrypt sensitive fields except user_id
                $encryptedFields = $validatedData;
                unset($encryptedFields['user_id']); // Remove user_id from encryption
                foreach ($encryptedFields as $key => $value) {
                    $encryptedFields[$key] = Crypt::encrypt($value);
                }

                // Combine the encrypted and non-encrypted data
                $encryptedData = $validatedData;
                foreach ($encryptedFields as $key => $value) {
                    $encryptedData[$key] = $value;
                }

                // Create the user info
                $created = UserInfoModel::create($encryptedData);

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
                $userInfo = UserInfoModel::where('user_id', $user->id)->first();

                // Define an array of fields to decrypt
                $fieldsToDecrypt = [
                    'first_name',
                    'middle_name',
                    'last_name',
                    'contact_num',
                    'address_1',
                    'address_2',
                    'region_code',
                    'province_code',
                    'city_or_municipality_code',
                    'region_name',
                    'province_name',
                    'city_or_municipality_name',
                    'barangay',
                    'description_location'
                ];

                $decryptedData = [];

                // Loop through the fields and decrypt each one
                foreach ($fieldsToDecrypt as $field) {
                    $decryptedData[$field] = Crypt::decrypt($userInfo->$field);
                }

                // Include 'id' and 'user_id' in the decrypted data
                $decryptedData['id'] = $userInfo->id;
                $decryptedData['user_id'] = $userInfo->user_id;

                return response()->json([
                    'data' => $decryptedData
                ], Response::HTTP_OK);
            } else {
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_FORBIDDEN);
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
                $userInfo = UserInfoModel::where('user_id', $user->id)->first();

                // Define an array of fields to decrypt
                $fieldsToDecrypt = [
                    'first_name',
                    'middle_name',
                    'last_name',
                    'contact_num',
                    'address_1',
                    'address_2',
                    'region_code',
                    'province_code',
                    'city_or_municipality_code',
                    'region_name',
                    'province_name',
                    'city_or_municipality_name',
                    'barangay',
                    'description_location'
                ];

                $decryptedData = [];

                // Loop through the fields and decrypt each one
                foreach ($fieldsToDecrypt as $field) {
                    $decryptedData[$field] = Crypt::decrypt($userInfo->$field);
                }

                // Include 'id' and 'user_id' in the decrypted data
                $decryptedData['id'] = $userInfo->id;
                $decryptedData['user_id'] = $userInfo->user_id;

                return response()->json([
                    'data' => $decryptedData
                ], Response::HTTP_OK);
            } else {
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_FORBIDDEN);
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
                    'contact_num' => 'required|string|max:255|min:11',
                    'address_1' => 'required|string|max:255',
                    'address_2' => 'nullable|string|max:255',
                    'region_code' => 'required|max:255',
                    'province_code' => 'required|max:255',
                    'city_or_municipality_code' => 'required|max:255',
                    'region_name' => 'required|string|max:255',
                    'province_name' => 'required|string|max:255',
                    'city_or_municipality_name' => 'required|string|max:255',
                    'barangay' => 'required|string|max:255',
                    'description_location' => 'nullable|string',
                ]);

                // Encrypt sensitive data
                $validatedData['first_name'] = Crypt::encrypt($validatedData['first_name']);
                $validatedData['middle_name'] = Crypt::encrypt($validatedData['middle_name']);
                $validatedData['last_name'] = Crypt::encrypt($validatedData['last_name']);
                $validatedData['contact_num'] = Crypt::encrypt($validatedData['contact_num']);
                $validatedData['address_1'] = Crypt::encrypt($validatedData['address_1']);
                $validatedData['address_2'] = Crypt::encrypt($validatedData['address_2']);
                $validatedData['region_code'] = Crypt::encrypt($validatedData['region_code']);
                $validatedData['province_code'] = Crypt::encrypt($validatedData['province_code']);
                $validatedData['city_or_municipality_code'] = Crypt::encrypt($validatedData['city_or_municipality_code']);
                $validatedData['region_name'] = Crypt::encrypt($validatedData['region_name']);
                $validatedData['province_name'] = Crypt::encrypt($validatedData['province_name']);
                $validatedData['city_or_municipality_name'] = Crypt::encrypt($validatedData['city_or_municipality_name']);
                $validatedData['barangay'] = Crypt::encrypt($validatedData['barangay']);
                $validatedData['description_location'] = Crypt::encrypt($validatedData['description_location']);
                // ... (encrypt other sensitive fields)

                // Update account properties
                $data->fill($validatedData);
                $changes = [];

                if ($data->isDirty('first_name')) {
                    $changes[] = '-First-Name-changed-from-"' . $data->getOriginal('first_name') . '"-to-"' . $validatedData['first_name'];
                }
                if ($data->isDirty('middle_name')) {
                    $changes[] = '-Middle-Name-changed-from-"' . $data->getOriginal('middle_name') . '"-to-"' . $validatedData['middle_name'];
                }
                if ($data->isDirty('last_name')) {
                    $changes[] = '-Last-Name-changed-from-"' . $data->getOriginal('last_name') . '"-to-"' . $validatedData['last_name'];
                }
                if ($data->isDirty('contact_num')) {
                    $changes[] = '-Contact-Number-changed-from-"' . $data->getOriginal('contact_num') . '"-to-"' . $validatedData['contact_num'];
                }
                if ($data->isDirty('address_1')) {
                    $changes[] = '-Address-1-changed-from-"' . $data->getOriginal('address_1') . '"-to-"' . $validatedData['address_1'];
                }
                if ($data->isDirty('address_2')) {
                    $changes[] = '-Address-2-changed-from-"' . $data->getOriginal('address_2') . '"-to-"' . $validatedData['address_2'];
                }
                if ($data->isDirty('region_code')) {
                    $changes[] = '-Region-Code-changed-from-"' . $data->getOriginal('region_code') . '"-to-"' . $validatedData['region_code'];
                }
                if ($data->isDirty('province_code')) {
                    $changes[] = '-Province-Code-changed-from-"' . $data->getOriginal('province_code') . '"-to-"' . $validatedData['province_code'];
                }
                if ($data->isDirty('city_or_municipality_code')) {
                    $changes[] = '-City-or-Municipality-Code-changed-from-"' . $data->getOriginal('city_or_municipality_code') . '"-to-"' . $validatedData['city_or_municipality_code'];
                }
                if ($data->isDirty('region_name')) {
                    $changes[] = '-Region-Name-changed-from-"' . $data->getOriginal('region_name') . '"-to-"' . $validatedData['region_name'];
                }
                if ($data->isDirty('province_name')) {
                    $changes[] = '-Province-Name-changed-from-"' . $data->getOriginal('province_name') . '"-to-"' . $validatedData['province_name'];
                }
                if ($data->isDirty('city_or_municipality_name')) {
                    $changes[] = '-City-or-Municipality-Name-changed-from-"' . $data->getOriginal('city_or_municipality_name') . '"-to-"' . $validatedData['city_or_municipality_name'];
                }
                if ($data->isDirty('barangay')) {
                    $changes[] = '-Barangay-changed-from-"' . $data->getOriginal('barangay') . '"-to-"' . $validatedData['barangay'];
                }
                if ($data->isDirty('description_location')) {
                    $changes[] = '-Description-location-changed-from-"' . $data->getOriginal('description_location') . '"-to-"' . $validatedData['description_location'];
                }


                // Save the updated data
                if ($data->save()) {
                    if ($user) {
                        $userAction = 'UPDATE USER INFO';
                        $details = 'Updated-an-Account-with-the-following-changes-' . implode('-', $changes);

                        // Create Log
                        LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => Carbon::now()
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