<?php

namespace App\Http\Controllers;

use App\Mail\ResendVerificationMail;
use App\Mail\ResetPasswordMail;
use App\Mail\VerificationMail;
use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\UserInfoModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    //
    public function logIn(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|max:255',
                'password' => 'required',
            ]);

            $user = AuthModel::where('email', $request->input('email'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user && Hash::check($request->input('password'), $user->password)) {
                $role = $user->role;

                $verificationToken = Str::uuid(); // Generate a UUID token
                $user->update(['session_login' => $verificationToken]);

                $userAction = 'LOG IN';
                $details = 'USER LOGGED IN FROM THIS IP ADDRESS: ' . $request->ip();
                // Create Log
                $create = LogsModel::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_action' => $userAction,
                    'details' => $details,
                    'created_at' => now()
                ]);

                if ($create) {
                    if ($user->role == "USER") {
                        $newUser = UserInfoModel::where('user_id', $user->id)->doesntExist();
                        if ($newUser) {
                            // Return a success response with CORS headers
                            return response()->json([
                                'message' => 'New User.',
                                'sessionLogin' => $verificationToken,
                                'status' => true,
                                'role' => $role
                            ], Response::HTTP_OK);
                        } else {
                            // Return a success response with CORS headers
                            return response()->json([
                                'message' => 'Logged in successfully.',
                                'sessionLogin' => $verificationToken,
                                'status' => true,
                                'role' => $role
                            ], Response::HTTP_OK);
                        }
                    }
                }

                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Logged in successfully.',
                    'sessionLogin' => $verificationToken,
                    'status' => true,
                    'role' => $role
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'message' => 'Invalid credentials or user not found.'
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

    public function signUp(Request $request)
    {
        try {
            // Declare Value
            $verificationNumber = mt_rand(100000, 999999);
            $verificationToken = Str::uuid(); // Generate a UUID token
            $role = 'USER';
            $status = 'NOT VERIFIED';
            $ipAdress = $request->ip();

            // Validate Email and Password
            $request->validate([
                'email' => 'required|string|email|max:255',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required|min:8'
            ]);

            // Extract the name from the email address
            $emailParts = explode('@', $request->input('email'));
            $name = $emailParts[0];

            // Exist Email Not Verified then Send Code
            $user = AuthModel::where('email', $request->input('email'))
                ->where('status', 'NOT VERIFIED')
                ->first();
            if ($user) {
                // Update the user record with the new verification number
                $user->verification_num = $verificationNumber;
                $user->session_verify_email = $verificationToken;
                $user->save();

                // Send the verification number to the user's email
                Mail::to($request->input('email'))->send(new VerificationMail($verificationNumber, $name));

                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Sent new code.',
                    'sessionVerifyEmail' => $verificationToken
                ], Response::HTTP_CREATED);
            }

            // Exist Email Verified then Send Error 'User Already Exist'
            $user = AuthModel::where('email', $request->input('email'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'User already exist.',
                ], Response::HTTP_CREATED);
            }

            // Create the user
            AuthModel::create([
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => $role,
                'status' => $status,
                'ip_address' => $ipAdress,
                'verification_num' => $verificationNumber,
                'session_verify_email' => $verificationToken
            ]);

            // Send the verification number to the user's email
            Mail::to($request->input('email'))->send(new VerificationMail($verificationNumber, $name));

            // Return a success response with CORS headers
            return response()->json([
                'message' => 'Created',
                'sessionVerifyEmail' => $verificationToken
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

    public function resendCode(Request $request)
    {
        try {
            $verificationNumber = mt_rand(100000, 999999);

            $request->validate([
                'sessionVerifyEmail' => 'required|string',
            ]);

            $user = AuthModel::where('session_verify_email', $request->input('sessionVerifyEmail'))
                ->where('status', 'NOT VERIFIED')
                ->first();

            if ($user) {
                $email = $user->email;

                // Extract the name from the email address
                $emailParts = explode('@', $email);
                $name = $emailParts[0];

                // Update the user record with the new verification number
                $user->verification_num = $verificationNumber;
                $user->save();

                // Send the verification number to the user's email
                Mail::to($email)->send(new ResendVerificationMail($verificationNumber, $name));

                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Sent new code.'
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'User not found or already verified.'
                ], Response::HTTP_NOT_FOUND);
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

    public function verifyEmail(Request $request)
    {
        try {
            $verificationNumber = mt_rand(100000, 999999);
            $verificationToken = Str::uuid(); // Generate a UUID token
            $status = 'VERIFIED';

            $request->validate([
                'verificationCode' => 'required|int|min:6',
                'sessionVerifyEmail' => 'required|string'
            ]);

            $user = AuthModel::where('session_verify_email', $request->input('sessionVerifyEmail'))
                ->where('status', 'NOT VERIFIED')
                ->where('verification_num', $request->input('verificationCode'))
                ->first();

            if ($user) {
                // Update the user record with the new verification number
                $user->verification_num = $verificationNumber;
                $user->session_verify_email = $verificationToken;
                $user->status = $status;
                $user->verified_at = now();
                $user->save();

                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Email verified.'
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'Wrong Verification Code.'
                ], Response::HTTP_CREATED);
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

    public function checkVerKeyAndEmail(Request $request)
    {
        try {
            $user = AuthModel::where('email', $request->input('email'))
                ->where('session_pass_reset', $request->input('verificationKey'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                return response()->json([
                    'message' => 'Good.'
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'Bad.'
                ], Response::HTTP_CREATED);
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

    public function resetPassword(Request $request)
    {
        try {
            $verificationToken = Str::uuid(); // Generate a UUID token

            $request->validate([
                'email' => 'required|string|email|max:255',
            ]);
            $user = AuthModel::where('email', $request->input('email'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                // Update the user record with the new verification number
                $user->session_pass_reset = $verificationToken;
                $user->update_pass_reset_at = now();
                $user->save();

                // Send the verification number to the user's email
                Mail::to($user->email)->send(new ResetPasswordMail($verificationToken, $user->email));

                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Password Reset Link Sent to Your Email.',
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'User not found.'
                ], Response::HTTP_CREATED);
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

    public function updatePassword(Request $request)
    {
        try {
            $verificationToken = Str::uuid(); // Generate a UUID token

            $request->validate([
                'verificationKey' => 'required|string',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required|min:8',
            ]);

            $user = AuthModel::where('session_pass_reset', $request->input('verificationKey'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $user->password = Hash::make($request->input('password'));
                $user->session_pass_reset = $verificationToken;
                $user->updated_at = now();
                $user->save();

                // Return a success response with CORS headers
                return response()->json([
                    'message' => 'Password updated.'
                ], Response::HTTP_CREATED);
            } else {
                return response()->json([
                    'message' => 'User not found or not verified.'
                ], Response::HTTP_NOT_FOUND);
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

    public function updateNewEmailClient(Request $request)
    {
        try {
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                if (Hash::check($request->input('currentPassword'), $user->password)) {
                    // Validate the input
                    $validatedData = $request->validate([
                        'newEmail' => 'required|string|email|max:255|unique:users_tbl,email', // Assuming 'users' is the table name
                        // Change 'newEmail' validation rule
                        'currentPassword' => 'required|min:8',
                    ]);

                    // Update account properties
                    $user->email = $validatedData['newEmail']; // Update email address
                    $changes = [];

                    if ($user->isDirty('email')) { // Check for email change
                        $changes[] = 'Email changed from "' . $user->getOriginal('email') . '" to "' . $user->email . '".';
                    }

                    if (empty($changes)) {
                        return response()->json([
                            'message' => 'No changes to update.'
                        ], Response::HTTP_OK);
                    }

                    if ($user->save()) {
                        $userAction = 'UPDATE';
                        $details = 'Updated an Email with the following change: ' . implode(' ', $changes);
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
                } else {
                    return response()->json([
                        'message' => 'Invalid current password.'
                    ], Response::HTTP_OK);
                }
            } else {
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

    public function updatePasswordClient(Request $request)
    {
        try {
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                if (Hash::check($request->input('currentPassword'), $user->password)) {
                    // Validate the input
                    $validatedData = $request->validate([
                        'currentPassword' => 'required|min:8',
                        'newPassword' => 'required|min:8',
                    ]);

                    // Update account properties
                    $user->password = Hash::make($request->input('newPassword'));

                    if ($user->save()) {
                        $userAction = 'UPDATE';
                        $details = 'Password has changed.';

                        // Create Log
                        LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        return response()->json([
                            'message' => 'Password updated successfully.'
                        ], Response::HTTP_OK);
                    } else {
                        return response()->json([
                            'message' => 'Failed to update password.'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'Invalid current password.'
                    ], Response::HTTP_OK);
                }
            } else {
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
}