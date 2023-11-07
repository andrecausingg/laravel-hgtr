<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $data = ProductModel::all();
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $request->validate([
                    'role' => 'nullable|string',
                    'image' => 'required|image|mimes:jpeg,png,jpg|max:8192',
                    'name' => 'required|string|nullable|max:255',
                    'price' => 'required|numeric|min:1',
                    'quantity' => 'required|numeric|min:1',
                    'category' => 'required|string|nullable|max:255',
                    'color' => 'required|string|max:255',
                    'size' => 'required|string|max:255',
                    'discount' => 'nullable|between:0,100',
                    'description' => 'nullable|string',
                    'promo' => 'nullable|string',
                ]);

                $image = $request->file('image');
                $imageActualExt = $image->getClientOriginalExtension();
                do {
                    $filename = uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt;
                } while (ProductModel::where('image', $filename)->exists());
                // Store the image on the 'public' disk with the generated filename
                Storage::disk('public')->put($filename, file_get_contents($image));

                do {
                    $uuid = Str::uuid();
                } while (ProductModel::where('group_id', $uuid)->exists());
                // Create Todo List
                $created = ProductModel::create([
                    'user_id' => $user->id,
                    'group_id' => $uuid,
                    'role' => 'MAIN',
                    'image' => $filename,
                    'name' => $request->input('name'),
                    'price' => $request->input('price'),
                    'quantity' => $request->input('quantity'),
                    'category' => $request->input('category'),
                    'color' => $request->input('color'),
                    'size' => $request->input('size'),
                    'discount' => $request->input('discount'),
                    'description' => $request->input('description'),
                    'promo' => $request->input('promo'),
                ]);

                if ($created) {
                    $userAction = 'CREATED';
                    $details = 'Created Product Information with Group ID: ' . $uuid . "\n" .
                        'Role: MAIN' . "\n" .
                        'Image Name: ' . $filename . "\n" .
                        'Name: ' . $request->input('name') . "\n" .
                        'Price: ' . $request->input('price') . "\n" .
                        'Quantity: ' . $request->input('quantity') . "\n" .
                        'Category: ' . $request->input('category') . "\n" .
                        'Color: ' . $request->input('color') . "\n" .
                        'Size: ' . $request->input('size') . "\n" .
                        'Discount: ' . $request->input('discount') . "\n" .
                        'Description: ' . $request->input('description') . "\n" .
                        'Promo: ' . $request->input('promo') . "\n";


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

    public function addProduct(Request $request)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                // Fetch Group I.D
                $product = ProductModel::where('id', $request->input('id'))->first();
                if ($product) {
                    $request->validate([
                        'role' => 'nullable|string',
                        'image' => 'required|image|mimes:jpeg,png,jpg|max:8192',
                        'name' => 'required|string|nullable|max:255',
                        'price' => 'required|numeric|min:0',
                        'quantity' => 'required|numeric|min:1',
                        'category' => 'required|string|nullable|max:255',
                        'color' => 'required|string|max:255',
                        'size' => 'required|string|max:255',
                        'discount' => 'nullable|numeric|between:0,100',
                        'description' => 'nullable|string',
                        'promo' => 'nullable|string',
                    ]);
                    $image = $request->file('image');
                    $imageActualExt = $image->getClientOriginalExtension();
                    do {
                        $filename = uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt;
                    } while (ProductModel::where('image', $filename)->exists());
                    // Store the image on the 'public' disk with the generated filename
                    Storage::disk('public')->put($filename, file_get_contents($image));

                    do {
                        $uuid = Str::uuid();
                    } while (ProductModel::where('image', $filename)->exists());
                    // Create Todo List
                    $created = ProductModel::create([
                        'group_id' => $product->group_id,
                        'image' => $filename,
                        'name' => $request->input('name'),
                        'price' => $request->input('price'),
                        'quantity' => $request->input('quantity'),
                        'category' => $request->input('category'),
                        'color' => $request->input('color'),
                        'size' => $request->input('size'),
                        'discount' => $request->input('discount'),
                        'description' => $request->input('description'),
                        'promo' => $request->input('promo'),
                    ]);
                    if ($created) {
                        $userAction = 'CREATE';
                        $details = 'Add Product Information with Group ID: ' . $uuid . "\n" .
                            'Role: ' . "\n" .
                            'Image Name: ' . $filename . "\n" .
                            'Name: ' . $request->input('name') . "\n" .
                            'Price: ' . $request->input('price') . "\n" .
                            'Quantity: ' . $request->input('quantity') . "\n" .
                            'Category: ' . $request->input('category') . "\n" .
                            'Color: ' . $request->input('color') . "\n" .
                            'Size: ' . $request->input('size') . "\n" .
                            'Discount: ' . $request->input('discount') . "\n" .
                            'Description: ' . $request->input('description') . "\n" .
                            'Promo: ' . $request->input('promo') . "\n";
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        try {
            $data = ProductModel::where('group_id', $id)->get(); // Use where to filter by group_id
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $data = ProductModel::find($id);
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
                    'image' => 'image|mimes:jpeg,png,jpg,gif|max:8192',
                    'price' => 'required|numeric|min:0',
                    'quantity' => 'required|numeric|min:0',
                    'color' => 'required|string|max:255',
                    'size' => 'required|string|max:255',
                    'discount' => 'nullable|numeric|between:0,100',
                    'description' => 'nullable|string|min:1'
                ]);

                // Update account properties
                $data->fill($validatedData);
                $changes = [];

                if ($data->isDirty('image')) {
                    $changes[] = 'Image changed from "' . $data->getOriginal('image') . '" to "' . $data->image . '".';
                }
                if ($data->isDirty('price')) {
                    $changes[] = 'Price changed from "' . $data->getOriginal('price') . '" to "' . $data->price . '".';
                }
                if ($data->isDirty('color')) {
                    $changes[] = 'Color changed from "' . $data->getOriginal('color') . '" to "' . $data->color . '".';
                }
                if ($data->isDirty('size')) {
                    $changes[] = 'Size changed from "' . $data->getOriginal('size') . '" to "' . $data->size . '".';
                }
                if ($data->isDirty('discount')) {
                    $changes[] = 'Discount changed from "' . $data->getOriginal('discount') . '" to "' . $data->discount . '".';
                }
                if ($data->isDirty('description')) {
                    $changes[] = 'Description changed from "' . $data->getOriginal('description') . '" to "' . $data->description . '".';
                }
                if (empty($changes)) {
                    return response()->json([
                        'message' => 'No changes to update.'
                    ], Response::HTTP_OK);
                }

                if ($request->hasFile('image')) {
                    $storage = Storage::disk('public');

                    // Delete old image
                    if ($storage->exists($data->image)) {
                        $storage->delete($data->image);
                    }

                    $image = $request->file('image');
                    $imageActualExt = $image->getClientOriginalExtension();

                    do {
                        $filename = uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt;
                    } while (ProductModel::where('image', $filename)->exists());

                    // Store the image on the 'public' disk with the generated filename
                    $storage->put($filename, file_get_contents($image));
                    $data->image = $filename;
                }

                if ($data->save()) {
                    $userAction = 'UPDATE';
                    $details = 'Updated an Product with the following changes: ' . implode(' ', $changes);
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

    public function updateAll(Request $request)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            // Validate the Input
            $validatedData = $request->validate([
                'productIds' => 'required|array',
                'price' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|between:0,100',
                'promo' => 'nullable'
            ]);

            // Fetch products with the given IDs
            $data = ProductModel::whereIn('id', $validatedData['productIds'])->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'No products found for the given IDs'
                ], Response::HTTP_OK);
            }

            $changes = [];

            // Iterate through products and update their properties
            foreach ($data as $product) {
                if (isset($validatedData['price'])) {
                    $product->price = $validatedData['price'];
                    $changes[] = 'Price changed from "' . $product->getOriginal('price') . '" to "' . $validatedData['price'] . '".';
                }
                if (isset($validatedData['quantity'])) {
                    $product->quantity = $validatedData['quantity'];
                    $changes[] = 'Quantity changed from "' . $product->getOriginal('quantity') . '" to "' . $validatedData['quantity'] . '".';
                }
                if (isset($validatedData['discount'])) {
                    $product->discount = $validatedData['discount'];
                    $changes[] = 'Discount changed from "' . $product->getOriginal('discount') . '" to "' . $validatedData['discount'] . '".';
                }
                if (isset($validatedData['promo'])) {
                    $product->promo = $validatedData['promo'];
                    $changes[] = 'Promo changed from "' . $product->getOriginal('promo') . '" to "' . $validatedData['promo'] . '".';
                }

                $product->save();
            }

            if (empty($changes)) {
                return response()->json([
                    'message' => 'No changes to update.'
                ], Response::HTTP_OK);
            }

            $userAction = 'UPDATE PRODUCT';
            $details = 'Updated product(s) with the following changes: ' . implode(', ', $changes);
            // Create Log
            LogsModel::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_action' => $userAction,
                'details' => $details,
                'created_at' => now() // You can use Carbon::now() if needed
            ]);

            return response()->json([
                'message' => 'Updated'
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
    public function destroy(Request $request, $id)
    {
        try {
            // Find the product in the database
            $data = ProductModel::find($id);

            if (!$data) {
                return response()->json([
                    'message' => 'Data Not Found'
                ], Response::HTTP_OK);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                if ($data->role == 'MAIN') {
                    $mainProductsCount = ProductModel::where('group_id', $data->group_id)->count();

                    if ($mainProductsCount === 1) {
                        // If the product is not 'MAIN', simply delete it
                        $storage = Storage::disk('public');
                        // Delete old image
                        if ($storage->exists($data->image)) {
                            $storage->delete($data->image);
                        }
                        if ($data->delete()) {
                            $userAction = 'DELETE';
                            $details = 'Deleted Product Information with Group ID: ' . $data->group_id . "\n" .
                                'Role: ' . $data->role . "\n" .
                                'Image Name: ' . $data->image . "\n" .
                                'Name: ' . $data->name . "\n" .
                                'Price: ' . $data->price . "\n" .
                                'Quantity: ' . $data->quantity . "\n" .
                                'Category: ' . $data->category . "\n" .
                                'Color: ' . $data->colors . "\n" .
                                'Size: ' . $data->size . "\n" .
                                'Discount: ' . $data->discount . "\n" .
                                'Description: ' . $data->description . "\n";

                            // Create Log
                            $create = LogsModel::create([
                                'user_id' => $user->id,
                                'ip_address' => $request->ip(),
                                'user_action' => $userAction,
                                'details' => $details,
                                'created_at' => Carbon::now()
                            ]);

                            if ($create) {
                                return response()->json([
                                    'message' => 'Deleted'
                                ], Response::HTTP_OK);
                            }
                        }

                    } else {
                        // Update a single product with the same group_id to have role 'MAIN'
                        $affectedRows = ProductModel::where('group_id', $data->group_id)
                            ->where('id', '!=', $id)
                            ->limit(1) // Limit the update to one row
                            ->update(['role' => 'MAIN']);

                        if ($affectedRows) {
                            // Storage Public
                            $storage = Storage::disk('public');
                            // Delete old image
                            if ($storage->exists($data->image)) {
                                $storage->delete($data->image);
                            }

                            if ($data->delete()) {
                                $userAction = 'DELETE';
                                $details = 'Deleted Product Information with Group ID: ' . $data->group_id . "\n" .
                                    'Role: ' . $data->role . "\n" .
                                    'Image Name: ' . $data->image . "\n" .
                                    'Name: ' . $data->name . "\n" .
                                    'Price: ' . $data->price . "\n" .
                                    'Quantity: ' . $data->quantity . "\n" .
                                    'Category: ' . $data->category . "\n" .
                                    'Color: ' . $data->colors . "\n" .
                                    'Size: ' . $data->size . "\n" .
                                    'Discount: ' . $data->discount . "\n" .
                                    'Description: ' . $data->description . "\n";

                                // Create Log
                                $create = LogsModel::create([
                                    'ip_address' => $request->ip(),
                                    'user_action' => $userAction,
                                    'details' => $details,
                                    'created_at' => Carbon::now()
                                ]);

                                if ($create) {
                                    return response()->json([
                                        'message' => 'Deleted'
                                    ], Response::HTTP_OK);
                                }
                            }
                        }
                    }
                } else {
                    // If the product is not 'MAIN', simply delete it
                    $storage = Storage::disk('public');
                    // Delete old image
                    if ($storage->exists($data->image)) {
                        $storage->delete($data->image);
                    }

                    if ($data->delete()) {
                        $userAction = 'DELETE';
                        $details = 'Deleted Product Information with Group ID: ' . $data->group_id . "\n" .
                            'Role: ' . $data->role . "\n" .
                            'Image Name: ' . $data->image . "\n" .
                            'Name: ' . $data->name . "\n" .
                            'Price: ' . $data->price . "\n" .
                            'Quantity: ' . $data->quantity . "\n" .
                            'Category: ' . $data->category . "\n" .
                            'Color: ' . $data->colors . "\n" .
                            'Size: ' . $data->size . "\n" .
                            'Discount: ' . $data->discount . "\n" .
                            'Description: ' . $data->description . "\n";

                        // Create Log
                        $create = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => Carbon::now()
                        ]);

                        if ($create) {
                            return response()->json([
                                'message' => 'Deleted'
                            ], Response::HTTP_OK);
                        }
                    }
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

    /**
     * Remove All the specified resource from storage.
     */
    public function destroyAll(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                // Delete records based on group_id
                $deletedCount = ProductModel::where('group_id', $id)->delete();

                if ($deletedCount > 0) {
                    // Log the deletion action
                    $userAction = 'DELETE';
                    $details = 'Deleted ' . $deletedCount . ' records with Group I.D: ' . $id;

                    // Create Log
                    $create = LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => Carbon::now()
                    ]);

                    if ($create) {
                        return response()->json([
                            'message' => 'Deleted'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'No records found with the specified group_id'
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

    // Client
    public function displaySelectedProduct(string $id)
    {
        //
        try {
            $data = ProductModel::where('group_id', $id)->get(); // Use where to filter by group_id
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
}