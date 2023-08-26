<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\OrderModel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = OrderModel::all();
            return response()->json([
                'data' => $data,
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
    public function getUnpaid($id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $id)
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $data = OrderModel::where('user_id', $user->id)->get();
                return response()->json([
                    'data' => $data,
                ], Response::HTTP_OK);
            }
            return response()->json([
                'message' => 'Intruder',
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
}