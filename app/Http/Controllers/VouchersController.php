<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\UserInfoModel;
use App\Models\VouchersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class VouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        try {
            // Retrieve all user information records associated 
            $authData = AuthModel::get();
            $voucherData = VouchersModel::all();

            // Initialize an array to store the final data
            $userData = [];

            foreach ($authData as $auth) {
                // Retrieve user information for the current auth
                $userInfo = UserInfoModel::where('user_id', $auth->id)->first();

                // Create an array for the current auth data in the desired format
                $authData = [
                    'email' => $auth->email,
                    'userInfo' => [
                        'first_name' => Crypt::decrypt($userInfo->first_name),
                        'last_name' => Crypt::decrypt($userInfo->last_name),
                    ],
                ];

                // Add the current User Info data to the final data array
                $userData[] = $authData;
            }

            // Return the user information records in JSON format
            return response()->json([
                'userData' => $userData,
                'voucherData' => $voucherData
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

    public function getUsersOfVoucher()
    {
        try {
            $vouchers = VouchersModel::get();

            // Initialize an array to store the final data
            $data = [];

            foreach ($vouchers as $voucher) {
                // Retrieve user information
                $authInfo = AuthModel::where('id', $voucher->user_id)->where('role', 'USER')->first();

                if ($authInfo) {
                    // User information exists
                    $userInfo = UserInfoModel::where('user_id', $authInfo->id)->first();

                    // Create an array for data in the desired format
                    $authData = [
                        'authInfo' => [
                            'email' => $authInfo->email,
                        ],
                        'userInfo' => [
                            'first_name' => $userInfo ? Crypt::decrypt($userInfo->first_name) : null,
                            'last_name' => $userInfo ? Crypt::decrypt($userInfo->last_name) : null,
                        ],
                        'id' => $voucher->id,
                        'name' => $voucher->name,
                        'status' => $voucher->status,
                        'discount' => $voucher->discount,
                        'activate_at' => $voucher->activate_at,
                        'start_at' => $voucher->start_at,
                        'expire_at' => $voucher->expire_at,
                        'created_at' => $voucher->created_at,
                    ];

                    // Add data to the final data array
                    $data[] = $authData;
                }
            }

            // Return the user information records in JSON format
            return response()->json(['data' => $data], 200);
        } catch (Exception $e) {
            // Handle exceptions if necessary
            return response()->json(['error' => $e->getMessage()], 500);
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

    public function getAllUsersPutVoucher()
    {
        try {
            $auths = AuthModel::where('role', 'USER')->get();
        
            // Initialize an array to store the final data
            $data = [];
        
            foreach ($auths as $auth) {
                // Retrieve user information
                $userInfo = UserInfoModel::where('user_id', $auth->id)->first();
                $voucherInfo = VouchersModel::where('user_id', $auth->id)->first();
        
                // Check if user information is found
                if ($userInfo) {
                    // Create an array for data in the desired format
                    $authData = [
                        'id' => $auth->id,
                        'email' => $auth->email,
                        'userInfo' => [
                            'first_name' => Crypt::decrypt($userInfo->first_name),
                            'last_name' => Crypt::decrypt($userInfo->last_name),
                        ],
                    ];
        
                    // Add data to the final data array
                    $data[] = $authData;
                } else {
                    // Handle the case where user information is not found
                    // You can choose to skip this user or add an empty placeholder as needed
                }
            }
        
            // Return the user information records in JSON format
            return response()->json(['data' => $data, 'success' => true], 200);
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

    public function newEmail(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            $request->validate([
                'user_ids' => 'required|array',
                'name' => 'required|string|max:255',
            ]);

            // Parse input dates using Carbon
            $startAt = Carbon::parse($request->input('start_at'));
            $expireAt = Carbon::parse($request->input('expire_at'));

            // Check if the voucher is expired
            $status = (Carbon::now() > $startAt && Carbon::now() > $expireAt) ? 'EXPIRED' : 'AVAILABLE';

            // Create an array to store the data for each voucher
            $userIds = $request->input('user_ids');
            $voucherData = [];

            // Iterate through user IDs and prepare voucher data
            foreach ($userIds as $userId) {
                $voucherData[] = [
                    'user_id' => $userId,
                    'status' => $status,
                    'name' => $request->input('name'),
                    'discount' => $request->input('discount'),
                    'start_at' => $request->input('start_at'),
                    'expire_at' => $request->input('expire_at'),
                    'created_at' => Carbon::now(),
                ];
            }

            // Insert multiple records in one query
            $created = VouchersModel::insert($voucherData);

            if ($created) {
                $userEmails = AuthModel::whereIn('id', $userIds)->pluck('email')->toArray();

                // ************************************************** //
                // FORMAT TIME  
                // Create Carbon instances and set the time zone to 'Asia/Manila'
                $carbonStartAt = Carbon::parse($request->input('start_at'));
                $carbonExpiredAt = Carbon::parse($request->input('expire_at'));

                // Format the times
                $formattedStartAt = $carbonStartAt->format('F d, Y h:i:s A');
                $formattedExpiredAt = $carbonExpiredAt->format('F d, Y h:i:s A');
                // ************************************************** //

                $userAction = 'CREATED VOUCHER';
                $details = 'Created Voucher for users with emails: ' . implode(', ', $userEmails) . "\n" .
                    'Category: ' . $request->input('category') . "\n" .
                    'Status: AVAILABLE' . "\n" .
                    'Name: ' . $request->input('name') . "\n" .
                    'Discount: ' . $request->input('discount') . "\n" .
                    'Start At: ' . $formattedStartAt . "\n" .
                    'Expired At: ' . $formattedExpiredAt . "\n";

                // Create Log
                $create = LogsModel::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_action' => $userAction,
                    'details' => $details,
                    'created_at' => Carbon::now()
                ]);

                if ($create) {
                    // Return a success response with CORS headers
                    return response()->json([
                        'message' => 'Created'
                    ], Response::HTTP_OK);
                }
            }

            // If the voucher creation fails
            return response()->json([
                'message' => 'Voucher Creation Failed'
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            $voucher = VouchersModel::where('user_id', $user->id)->get(); // Fetch the voucher
            return response()->json([
                'message' => $voucher
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        try {
            // Fetch the voucher
            $voucher = VouchersModel::where('id', $id)->first();
            if (!$voucher) {
                return response()->json([
                    'message' => 'Voucher not found'
                ], Response::HTTP_OK);
            }

            // Initialize an array to store the final data
            $voucherDataAndUserInfo = [];

            // Retrieve Email
            $authInfo = AuthModel::where('id', $voucher->user_id)->first();
            // Retrieve user information
            $userInfo = UserInfoModel::where('user_id', $voucher->user_id)->first();

            // Create an array for the current auth data in the desired format
            $data = [
                'authInfo' => [
                    'email' => $authInfo->email,
                ],
                'userInfo' => [
                    'first_name' => Crypt::decrypt($userInfo->first_name),
                    'last_name' => Crypt::decrypt($userInfo->last_name),
                ],
                'id' => $voucher->id,
                'name' => $voucher->name,
                'discount' => $voucher->discount,
                'start_at' => $voucher->start_at,
                'expire_at' => $voucher->expire_at,
                'status' => $voucher->status,
            ];

            // Add the current User Info data to the final data array
            $voucherDataAndUserInfo[] = $data;

            // Return the user information records in JSON format
            return response()->json([
                'message' => $voucherDataAndUserInfo,
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();
                
            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            // Fetch the voucher
            $voucher = VouchersModel::where('id', $id)->first();
            if (!$voucher) {
                return response()->json([
                    'message' => 'Voucher not found'
                ], Response::HTTP_OK);
            }

            // Validate the Input
            $validatedvoucher = $request->validate([
                'name' => 'required|string|max:255',
                'discount' => $request->input('name') !== 'FREE SHIPPING' ? 'required|numeric|min:0|max:100' : 'nullable',
                'status' => 'required',
            ]);

            // Update account properties
            $voucher->fill($validatedvoucher);
            $changes = [];

            if ($voucher->isDirty('name')) {
                $changes[] = 'Name changed from "' . $voucher->getOriginal('name') . '" to "' . $voucher->name . '".';
            }
            if ($voucher->isDirty('discount')) {
                $changes[] = 'Discount At changed from "' . $voucher->getOriginal('discount') . '" to "' . $voucher->discount . '".';
            }
            if ($voucher->isDirty('status')) {
                $changes[] = 'Status At changed from "' . $voucher->getOriginal('status') . '" to "' . $voucher->status . '".';
            }
            if ($voucher->isDirty('start_at')) {
                $changes[] = 'Start At changed from "' . $voucher->getOriginal('start_at') . '" to "' . $voucher->start_at . '".';
            }
            if ($voucher->isDirty('expire_at')) {
                $changes[] = 'Expire At changed from "' . $voucher->getOriginal('expire_at') . '" to "' . $voucher->expire_at . '".';
            }
            if (empty($changes)) {
                return response()->json([
                    'message' => 'No changes to update.'
                ], Response::HTTP_OK);
            } else {
                if ($voucher->save()) {
                    $userAction = 'UPDATE VOUCHER';
                    $details = 'Updated a Voucher with the following changes: ' . implode(' ', $changes);
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

    public function updateClaimed(Request $request, string $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();
        
            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }
        
            $voucher = VouchersModel::where('user_id', $user->id)
                ->where('id', $id)
                ->first(); // Fetch the voucher
        
            if (!$voucher) {
                return response()->json([
                    'message' => 'Voucher not found'
                ], Response::HTTP_OK);
            }
        
            // Check if the voucher is expired
            if ($voucher->expire_at && now() > $voucher->expire_at) {
                // Update status to EXPIRED
                $voucher->status = 'EXPIRED';
                $voucher->save();
        
                return response()->json([
                    'message' => 'Voucher is expired'
                ], Response::HTTP_OK);
            }
        
            $voucher->status = 'CLAIMED';
            $voucher->activate_at = now();
        
            if ($voucher->save()) {
                $userAction = 'ACTIVATE VOUCHER';
                $details = 'Activate Voucher with Name: ' . $voucher->name . "\n" .
                    'Status: ' . $voucher->status . "\n" .
                    'Discount: ' . $voucher->discount . "\n";
        
                // Create Log
                $create = LogsModel::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_action' => $userAction,
                    'details' => $details,
                    'created_at' => now()
                ]);
        
                if ($create) {
                    return response()->json([
                        'message' => 'Voucher Activated'
                    ], Response::HTTP_OK);
                } else {
                    return response()->json([
                        'message' => 'Failed to create log'
                    ], Response::HTTP_OK);
                }
            } else {
                return response()->json([
                    'message' => 'Failed to activate voucher'
                ], Response::HTTP_OK);
            }
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

    // Client Vouchers
    public function getVouchers(Request $request, string $id)
    {
        try {
            $user = AuthModel::where('session_login', $id ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();
                
            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            // Get the order records with their associated userInfo
            $data = VouchersModel::where('user_id', $user->id)->get();
            return response()->json([
                'message' => $data,
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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        //
        try {
            // Find the product in the database
            $data = VouchersModel::find($id);

            if (!$data) {
                return response()->json([
                    'message' => 'Data Not Found'
                ], Response::HTTP_OK);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            // Fetch Email based on I.D
            $auth = AuthModel::where('id', $data->user_id)
                ->where('status', 'VERIFIED')
                ->first();


            // ************************************************** //
            // FORMAT TIME  
            $timeFields = ['activate_at', 'used_at', 'start_at', 'expire_at'];
            $timezone = 'Asia/Manila';

            // Initialize an array to store the formatted times
            $formattedTimes = [];

            foreach ($timeFields as $field) {
                $carbonTime = Carbon::parse($data->$field, 'UTC')->setTimezone($timezone);
                $formattedTimes[$field] = $carbonTime->format('F d, Y h:i:s A');
            }

            // Access formatted times as needed
            $formattedActivatedAt = $formattedTimes['activate_at'];
            $formattedUsedAt = $formattedTimes['used_at'];
            $formattedStartAt = $formattedTimes['start_at'];
            $formattedExpiredAt = $formattedTimes['expire_at'];
            // ************************************************** //


            $userAction = 'DELETE VOUCHER';
            $details = 'Deleted Voucher Information with Voucher Name: ' . $data->name . "\n" .
                'Status: ' . $data->status . "\n" .
                'Discount: ' . $data->discount . "\n" .
                'Activate At: ' . $formattedActivatedAt . "\n" .
                'Used At: ' . $formattedUsedAt . "\n" .
                'Start At: ' . $formattedStartAt . "\n" .
                'Expired At: ' . $formattedExpiredAt . "\n" .
                'Email: ' . $auth->email . "\n";

            // Create Log
            $create = LogsModel::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_action' => $userAction,
                'details' => $details,
                'created_at' => Carbon::now()
            ]);

            if ($create) {
                if ($data->delete()) {
                    return response()->json([
                        'message' => 'Deleted'
                    ], Response::HTTP_OK);
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
}