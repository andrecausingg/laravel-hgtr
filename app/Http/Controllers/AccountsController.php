<?php

namespace App\Http\Controllers;

use App\Models\AccountsModel;
use App\Models\AuthModel;
use App\Models\LogsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $accounts = AccountsModel::all();
            $totalAccounts = $accounts->count();
            $totalUsers = $accounts->where('role', 'USER')->count();
            $totalAdmin = $accounts->where('role', 'ADMIN')->count();
        
            return response()->json([
                'accounts' => $accounts,
                'totalAccounts' => $totalAccounts,
                'totalUsers' => $totalUsers,
                'totalAdmin' => $totalAdmin
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
            // Validate Email and Password
            $request->validate([
                'email' => 'required|string|email|max:255', 
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required|min:8',
                'role' => 'required|string',
                'status' => 'required|string'
            ]);

            // Exist Email Verified then Send Error 'Email Already Exist'
            $user = AccountsModel::where('email', $request->input('email'))
            ->where('status', 'VERIFIED')
            ->first();
            if($user){
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Email already exist.',
                ], Response::HTTP_CREATED);
            }

            // Fetch User I.D
            $user = AuthModel::where('session_login', $request->input('session'))
            ->where('status', 'VERIFIED')
            ->first();
            $userAction = 'CREATE';
            $details = "Create Account: with this email: " . $request->input('email');
            // Create Log
            LogsModel::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_action' => $userAction,
                'details' => $details,
                'created_at' => now()
            ]);

            // Create the user
            AccountsModel::create([
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => $request->input('role'),
                'status' => $request->input('status'),
                'ip_address' => $request->ip(),
                'verified_at' => now()
            ]);

            // Return a success response with CORS headers
            return response()->json([
                'message' => 'Created'
            ], Response::HTTP_CREATED);
        
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
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editRoleStatus($id)
    {
        try {
            // Find the Account by ID in the database
            $account = AccountsModel::find($id);
    
            if (!$account) {
                return response()->json([
                    'message' => 'Not found'
                ], Response::HTTP_OK);
            }

            // Extract role and status from the account
            $role = $account->role;
            $status = $account->status;
            $email =  $account->email;
    
            return response()->json([
                'message' => 'Role and status successfully fetched.',
                'role' => $role,
                'status' => $status,
                'email' => $email
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
     * Update the specified resource in storage.
     */
    public function updateRoleStatus(Request $request, $id)
    {
        try {
            // Find the Account by ID in the database
            $account = AccountsModel::find($id);
            if (!$account) {
                return response()->json([
                    'message' => 'Account not found'
                ], Response::HTTP_OK);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
            ->where('status', 'VERIFIED')
            ->first();
            if ($user) {

                // Validate the Input
                $validatedData = $request->validate([
                    'role' => 'string',
                    'status' => 'string'
                ]);

                // Update account properties
                $account->fill($validatedData);
                $changes = [];

                if ($account->isDirty('role')) {
                    $changes[] = 'Role changed from "' . $account->getOriginal('role') . '" to "' . $account->role . '".';
                }if ($account->isDirty('status')) {
                    $changes[] = 'Status changed from "' . $account->getOriginal('status') . '" to "' . $account->status . '".';
                }if (empty($changes)) {
                    return response()->json([
                        'message' => 'No changes to update.'
                    ], Response::HTTP_OK);
                }

                if ($account->save()) {
                    $userAction = 'UPDATE';
                    $details = 'Updated an Account with the following changes: ' . implode(' ', $changes);

                    // Create Log
                    $create = LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => now()
                    ]);

                    if($create){
                        return response()->json([
                            'message' => 'Updated'
                        ], Response::HTTP_OK);
                    }
                }
            } else {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_NOT_FOUND);
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

    /**
     * Show the form for editing the specified resource.
     */
    public function editEmail($id)
    {
        try {
            // Find the Account by ID in the database
            $account = AccountsModel::find($id);
    
            if (!$account) {
                return response()->json([
                    'message' => 'Not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Extract role and status from the account
            $email = $account->email;
    
            return response()->json([
                'message' => 'Email successfully fetched.',
                'email' => $email,
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
     * Update the specified resource in storage.
     */
    public function updateEmail(Request $request, $id)
    {
        try {
            // Find the Account by ID in the database
            $account = AccountsModel::find($id);
            if (!$account) {
                return response()->json([
                    'message' => 'Account not found.'
                ], Response::HTTP_OK);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
            ->where('status', 'VERIFIED')
            ->first();
            if ($user){
                if($account->email == $request->input('email')){
                    return response()->json([
                        'message' => 'Email already Exist.'
                    ], Response::HTTP_OK);
                }
    
                // Validate the Input
                $validatedData = $request->validate([
                    'email' => 'required|string|email|max:255', 
                ]);
                // Update account properties
                $account->fill($validatedData);
                $changes = [];
                if ($account->isDirty('email')) {
                    $changes[] = 'Email changed from "' . $account->getOriginal('email') . '" to "' . $account->email . '".';
                }if (empty($changes)) {
                    return response()->json([
                        'message' => 'No changes to update.',
                    ], Response::HTTP_OK);
                }

                if ($account->save()){
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
     * Update the specified resource in storage.
     */
    public function updatePassword(Request $request, $id)
    {
        try {
            // Find the Account by ID in the database
            $data = AccountsModel::find($id);
            if (!$data) {
                return response()->json([
                    'message' => 'Account not found.'
                ], Response::HTTP_OK); // Use proper HTTP status code for resource not found
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
            ->where('status', 'VERIFIED')
            ->first();
            if ($user) {
                // Check if the old password matches the provided password
                if (Hash::check($request->input('password'), $data->password)) {
                    return response()->json([
                        'message' => 'Your old password must not be the same as your new password.'
                    ], Response::HTTP_OK); // Use proper HTTP status code for bad request
                }
            
                // Validate the Input
                $validatedData = $request->validate([
                    'password' => 'required|min:8|confirmed',
                    'password_confirmation' => 'required|min:8',
                ]);

                // Hash the new password
                $data->password = Hash::make($validatedData['password']);
                if($data->save()){
                    $userAction = 'UPDATE';
                    $details = 'Password has been changed with this email: ' . $data->email;
    
                    // Create Log
                    $create = LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => now()
                    ]);

                    if($create){
                        return response()->json([
                            'message' => 'Updated'
                        ], Response::HTTP_OK);
                    }
                }
            }else{
                return response()->json([
                    'message' => 'Intruder'
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Find the Id in the database
            $data = AccountsModel::find($id);
            if (!$data) {
                return response()->json([
                    'message' => 'Data Not Found.'
                ], Response::HTTP_NOT_FOUND);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
            ->where('status', 'VERIFIED')
            ->first();
            if ($user) {
                if ($data->delete()) {
                    $userAction = 'DELETE';
                    $details = 'This email has been deleted: ' . $data->email; // Corrected concatenation
    
                    // Create Log
                    $create = LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => now()
                    ]);

                    if($create){
                        return response()->json([
                            'message' => 'Deleted'
                        ], Response::HTTP_OK);
                    }
                }
            }else{
                return response()->json([
                    'message' => 'Intruder'
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
}
