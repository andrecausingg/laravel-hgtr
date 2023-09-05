<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\UserInfoModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{

    public function index()
    {
        try {
            // Get the order records with their associated userInfo
            $orders = OrderModel::get();

            // Initialize an array to store user information for each order
            $orderData = [];

            foreach ($orders as $order) {
                // Fetch the user information using user_id
                $userInfo = UserInfoModel::where('user_id', $order->user_id)->first();

                // Add order and user information to the array
                $orderData[] = [
                    'order' => $order,
                    'userInfo' => $userInfo
                ];
            }

            return response()->json([
                'data' => $orderData
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

    // Display To pay Component | CLIENT
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

    // Add to Cart | CLIENT
    public function addToCart(Request $request)
    {
        try {
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $request->validate([
                    'color' => 'required|string|max:255',
                    'size' => 'required|string|max:255',
                    'quantity' => 'required|numeric|min:1',
                    'group_id' => 'required|string',
                ]);

                $product = ProductModel::where('color', $request->input('color'))
                    ->where('size', $request->input('size'))
                    ->where('group_id', $request->input('group_id'))
                    ->first();

                if ($product && $product->quantity >= 1) {
                    do {
                        $uuidGroupId = Str::uuid();
                    } while (OrderModel::where('group_id', $uuidGroupId)->exists());

                    do {
                        $uuidOrderId = Str::uuid();
                    } while (OrderModel::where('order_id', $uuidOrderId)->exists());

                    // Add new Item on Cart then updating the shipping fee
                    $checkExistUnpaid = OrderModel::where('user_id', $user->id)
                        ->where('role', 'MAIN')
                        ->where('status', 'UNPAID')
                        ->first();

                    if ($checkExistUnpaid) {
                        // Same product on Cart just update the total price and quantity
                        $checkSameOrder = OrderModel::where('user_id', $user->id)
                            ->where('color', $request->input('color'))
                            ->where('size', $request->input('size'))
                            ->where('category', $product->category)
                            ->where('name', $product->name)
                            ->where('status', 'UNPAID')
                            ->first(); // Use first() instead of exists()
                        if ($checkSameOrder) {
                            // Declare
                            $quantity = (int) $request->input('quantity');

                            // Compute the total Price Now by Check on the product table
                            $discountedPrice = $product->price * (1 - ($product->discount / 100));
                            $totalPrice = $discountedPrice * $quantity;

                            // Fetch the value same order
                            $totalPriceDb = $checkSameOrder->total_price;
                            $totalQuantityDb = $checkSameOrder->quantity;

                            // Finalt total Quantity and Price
                            $finalTotalPrice = $totalPrice += $totalPriceDb;
                            $finalTotalQuantity = $quantity += $totalQuantityDb;

                            // Saving
                            $checkSameOrder->total_price = $finalTotalPrice;
                            $checkSameOrder->quantity = $finalTotalQuantity;
                            if ($checkSameOrder->save()) {
                                // Fetch the total Quantity
                                $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->get();

                                $totalQuantity = $fetchAllQuantityAndCalculateShippingFee->sum('quantity');

                                // Calculate the Shipping Fee
                                function calculateShippingFee($totalQuantity)
                                {
                                    $shippingFee = 100; // Base shipping fee
                                    $rangeSize = 5; // Size of each range
                                    $feeIncrement = 100; // Fee increment for each range

                                    // Calculate the range index based on the quantity
                                    $rangeIndex = ceil($totalQuantity / $rangeSize);

                                    // Calculate the shipping fee based on the range index and quantity
                                    $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                                    return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                                }

                                // Saving Now
                                $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->where('role', 'MAIN')
                                    ->first();
                                $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                if ($updateShippingFeeNow->save()) {
                                    return response()->json([
                                        'message' => 'Created'
                                    ], Response::HTTP_OK);
                                }
                            }
                        } else {
                            // Add new item on cart with the same Group  I.D
                            $quantity = (int) $request->input('quantity');
                            $discountedPrice = $product->price * (1 - ($product->discount / 100));
                            $totalPrice = $discountedPrice * $quantity;

                            $created = OrderModel::create([
                                'user_id' => $user->id,
                                'group_id' => $checkExistUnpaid->group_id,
                                'order_id' => $uuidOrderId,
                                'product_group_id' => $product->group_id,
                                'role' => '',
                                'category' => $product->category,
                                'name' => $product->name,
                                'image' => $product->image,
                                'size' => $product->size,
                                'color' => $product->color,
                                'quantity' => $quantity,
                                'discount' => $product->discount,
                                'description' => $product->description,
                                'product_price' => $product->price,
                                'shipping_fee' => 0.00,
                                'total_price' => $totalPrice,
                                'status' => 'UNPAID'
                            ]);

                            // Logs
                            if ($created) {
                                $userAction = 'CREATED';
                                $details = 'Created Product Information with Group ID: ' . $product->group_id . "\n" .
                                    'Order ID: ' . $uuidOrderId . "\n" .
                                    'Product Group ID: ' . $product->group_id . "\n" .
                                    'Role: MAIN' . "\n" .
                                    'Category: ' . $product->category . "\n" .
                                    'Product Name: ' . $product->name . "\n" .
                                    'Image Name: ' . $product->image . "\n" .
                                    'Size: ' . $product->size . "\n" .
                                    'Color: ' . $product->color . "\n" .
                                    'Quantity: ' . $quantity . "\n" .
                                    'Discount: ' . $product->discount . "\n" .
                                    'Description: ' . $product->description . "\n" .
                                    'Product Price: ' . $product->price . "\n" .
                                    'Total Price: ' . $totalPrice . "\n" .
                                    'Status: ' . 'UNPAID' . "\n";
                                // Create Log
                                $create = LogsModel::create([
                                    'user_id' => $user->id,
                                    'ip_address' => $request->ip(),
                                    'user_action' => $userAction,
                                    'details' => $details,
                                    'created_at' => now()
                                ]);

                                // Calculate Shipping Fee Always
                                if ($created) {
                                    // Fetch the total Quantity
                                    $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->get();

                                    $totalQuantity = $fetchAllQuantityAndCalculateShippingFee->sum('quantity');


                                    function calculateShippingFee($totalQuantity)
                                    {
                                        $shippingFee = 100; // Base shipping fee
                                        $rangeSize = 5; // Size of each range
                                        $feeIncrement = 100; // Fee increment for each range

                                        // Calculate the range index based on the quantity
                                        $rangeIndex = ceil($totalQuantity / $rangeSize);

                                        // Calculate the shipping fee based on the range index and quantity
                                        $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                                        return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                                    }

                                    $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->where('role', 'MAIN')
                                        ->first();
                                    $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                    if ($updateShippingFeeNow->save()) {
                                        return response()->json([
                                            'message' => 'Created'
                                        ], Response::HTTP_OK);
                                    }
                                }
                            }
                        }
                    } else {
                        // Same Add to Cart just update the total price and quantity
                        $checkSameOrder = OrderModel::where('user_id', $user->id)
                            ->where('color', $request->input('color'))
                            ->where('size', $request->input('size'))
                            ->where('category', $product->category)
                            ->where('name', $product->name)
                            ->where('status', 'UNPAID')
                            ->first(); // Use first() instead of exists()

                        if ($checkSameOrder) {
                            // Declare
                            $quantity = (int) $request->input('quantity');

                            // Compute the total Price Now by Check on the product table
                            $discountedPrice = $product->price * (1 - ($product->discount / 100));
                            $totalPrice = $discountedPrice * $quantity;

                            // Fetch the value same order
                            $totalPriceDb = $checkSameOrder->total_price;
                            $totalQuantityDb = $checkSameOrder->quantity;

                            // Finalt total Quantity and Price
                            $finalTotalPrice = $totalPrice += $totalPriceDb;
                            $finalTotalQuantity = $quantity += $totalQuantityDb;

                            // Saving
                            $checkSameOrder->total_price = $finalTotalPrice;
                            $checkSameOrder->quantity = $finalTotalQuantity;
                            if ($checkSameOrder->save()) {
                                // Fetch the total Quantity
                                $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->get();

                                $totalQuantity = $fetchAllQuantityAndCalculateShippingFee->sum('quantity');


                                // Calculate the Shipping Fee
                                function calculateShippingFee($totalQuantity)
                                {
                                    $shippingFee = 100; // Base shipping fee
                                    $rangeSize = 5; // Size of each range
                                    $feeIncrement = 100; // Fee increment for each range

                                    // Calculate the range index based on the quantity
                                    $rangeIndex = ceil($totalQuantity / $rangeSize);

                                    // Calculate the shipping fee based on the range index and quantity
                                    $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                                    return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                                }

                                // Saving Now
                                $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->where('role', 'MAIN')
                                    ->first();
                                $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                if ($updateShippingFeeNow->save()) {
                                    return response()->json([
                                        'message' => 'Created'
                                    ], Response::HTTP_OK);
                                }
                            }
                        } else {
                            // Fresh Create
                            // Calculate Shipping Fee
                            $quantity = (int) $request->input('quantity');
                            $baseShippingFee = 100;
                            $numGroups = floor($quantity / 5);
                            $shippingFees = ($numGroups * $baseShippingFee) + $baseShippingFee;

                            // Calculate total Price
                            $discountedPrice = $product->price * (1 - ($product->discount / 100));
                            $totalPrice = $discountedPrice * $quantity;
                            $created = OrderModel::create([
                                'user_id' => $user->id,
                                'group_id' => $uuidGroupId,
                                'order_id' => $uuidOrderId,
                                'product_group_id' => $product->group_id,
                                'role' => 'MAIN',
                                'category' => $product->category,
                                'name' => $product->name,
                                'image' => $product->image,
                                'size' => $product->size,
                                'color' => $product->color,
                                'quantity' => $quantity,
                                'discount' => $product->discount,
                                'description' => $product->description,
                                'product_price' => $product->price,
                                'shipping_fee' => $shippingFees,
                                'total_price' => $totalPrice,
                                'status' => 'UNPAID'
                            ]);

                            if ($created) {
                                // Delete if not role MAIN
                                $userAction = 'CREATED';
                                $details = 'Created Product Information with Group ID: ' . $product->group_id . "\n" .
                                    'Order ID: ' . $uuidOrderId . "\n" .
                                    'Product Group ID: ' . $product->group_id . "\n" .
                                    'Role: MAIN' . "\n" .
                                    'Category: ' . $product->category . "\n" .
                                    'Product Name: ' . $product->name . "\n" .
                                    'Image Name: ' . $product->image . "\n" .
                                    'Size: ' . $product->size . "\n" .
                                    'Color: ' . $product->color . "\n" .
                                    'Quantity: ' . $quantity . "\n" .
                                    'Discount: ' . $product->discount . "\n" .
                                    'Description: ' . $product->description . "\n" .
                                    'Product Price: ' . $product->price . "\n" .
                                    'Shipping Fee: ' . $shippingFees . "\n" .
                                    'Total Price: ' . $totalPrice . "\n" .
                                    'Status: ' . 'UNPAID' . "\n";
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
                                        'message' => 'Created'
                                    ], Response::HTTP_OK);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Selected product is unavailable or out of stock.'
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

    // Edit on To pay component Display Specific product | CLIENT
    public function edit($id)
    {
        try {
            $products = ProductModel::all();
            $order = OrderModel::where('id', $id)->first(); // Use '->first()' to retrieve a single
            return response()->json([
                'products' => $products,
                'order' => $order,
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

    // Destroy Item on TO PAY | CLIENT
    public function destroy(Request $request, $id)
    {
        try {
            // Find the product in the database
            $data = OrderModel::find($id);

            if (!$data) {
                return response()->json([
                    'message' => 'Data Not Found'
                ], Response::HTTP_OK);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_OK);
            }

            if ($data->role == 'MAIN') {
                // Delete if the role is MAIN and one only on group_od
                $count = OrderModel::where('group_id', $data->group_id)->count();
                if ($count === 1) {
                    // Delete if not role MAIN
                    $userAction = 'DELETE';
                    $details = 'Deleted Product Information with Group ID: ' . $data->group_id . "\n" .
                        'Order ID: ' . $data->order_id . "\n" .
                        'Product Group ID: ' . $data->product_group_id . "\n" .
                        'Category: ' . $data->category . "\n" .
                        'Name: ' . $data->name . "\n" .
                        'Image Name: ' . $data->image . "\n" .
                        'Size: ' . $data->size . "\n" .
                        'Color: ' . $data->color . "\n" .
                        'Quantity: ' . $data->quantity . "\n" .
                        'Discount: ' . $data->discount . "\n" .
                        'Description: ' . $data->description . "\n" .
                        'Product Price: ' . $data->product_price . "\n" .
                        'Total Price: ' . $data->total_price . "\n" .
                        'Status: ' . $data->status . "\n";

                    // Create Log
                    $create = LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => now()
                    ]);

                    if ($create) {
                        if ($data->delete()) {
                            return response()->json([
                                'message' => 'Deleted'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    // Update a single product with the same group_id to have role 'MAIN' and Shipping fee
                    $affectedRows = OrderModel::where('group_id', $data->group_id)
                        ->where('id', '!=', $id)
                        ->limit(1)
                        ->update(['role' => 'MAIN', 'shipping_fee' => $data->shipping_fee]);

                    if ($affectedRows) {
                        // Create Log for the update
                        $userAction = 'DELETE';
                        $details = 'Deleted Product Information with Group ID: ' . $data->group_id . "\n" .
                            'Order ID: ' . $data->order_id . "\n" .
                            'Product Group ID: ' . $data->product_group_id . "\n" .
                            'Role: MAIN' . "\n" .
                            'Category: ' . $data->category . "\n" .
                            'Name: ' . $data->name . "\n" .
                            'Image Name: ' . $data->image . "\n" .
                            'Size: ' . $data->size . "\n" .
                            'Color: ' . $data->color . "\n" .
                            'Quantity: ' . $data->quantity . "\n" .
                            'Discount: ' . $data->discount . "\n" .
                            'Description: ' . $data->description . "\n" .
                            'Product Price: ' . $data->product_price . "\n" .
                            'Shipping Fee: ' . $data->shipping_fee . "\n" .
                            'Total Price: ' . $data->total_price . "\n" .
                            'Status: ' . $data->status . "\n";

                        // Create Log
                        $create = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        if ($create) {
                            if ($data->delete()) {
                                $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->get();
                                $totalQuantity = 0;

                                foreach ($fetchAllQuantityAndCalculateShippingFee as $order) {
                                    $totalQuantity += $order->quantity;
                                }

                                function calculateShippingFee($totalQuantity)
                                {
                                    $shippingFee = 100; // Base shipping fee
                                    $rangeSize = 5; // Size of each range
                                    $feeIncrement = 100; // Fee increment for each range

                                    // Calculate the range index based on the quantity
                                    $rangeIndex = ceil($totalQuantity / $rangeSize);

                                    // Calculate the shipping fee based on the range index and quantity
                                    $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                                    return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                                }

                                $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->where('role', 'MAIN')
                                    ->first();
                                $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                if ($updateShippingFeeNow->save()) {
                                    return response()->json([
                                        'message' => 'Deleted'
                                    ], Response::HTTP_OK);
                                }
                            }
                        }
                    }
                }
            } else {
                // Delete if not role MAIN
                $userAction = 'DELETE';
                $details = 'Deleted Product Information with Group ID: ' . $data->group_id . "\n" .
                    'Order ID: ' . $data->order_id . "\n" .
                    'Product Group ID: ' . $data->product_group_id . "\n" .
                    'Category: ' . $data->category . "\n" .
                    'Name: ' . $data->name . "\n" .
                    'Image Name: ' . $data->image . "\n" .
                    'Size: ' . $data->size . "\n" .
                    'Color: ' . $data->color . "\n" .
                    'Quantity: ' . $data->quantity . "\n" .
                    'Discount: ' . $data->discount . "\n" .
                    'Description: ' . $data->description . "\n" .
                    'Product Price: ' . $data->product_price . "\n" .
                    'Total Price: ' . $data->total_price . "\n" .
                    'Status: ' . $data->status . "\n";

                // Create Log
                $create = LogsModel::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_action' => $userAction,
                    'details' => $details,
                    'created_at' => now()
                ]);

                // Calculate Shipping Fee Always
                if ($create) {
                    if ($data->delete()) {
                        $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                            ->where('status', 'UNPAID')
                            ->get();
                        $totalQuantity = 0;

                        foreach ($fetchAllQuantityAndCalculateShippingFee as $order) {
                            $totalQuantity += $order->quantity;
                        }

                        function calculateShippingFee($totalQuantity)
                        {
                            $shippingFee = 100; // Base shipping fee
                            $rangeSize = 5; // Size of each range
                            $feeIncrement = 100; // Fee increment for each range

                            // Calculate the range index based on the quantity
                            $rangeIndex = ceil($totalQuantity / $rangeSize);

                            // Calculate the shipping fee based on the range index and quantity
                            $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                            return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                        }

                        $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                            ->where('status', 'UNPAID')
                            ->where('role', 'MAIN')
                            ->first();
                        $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                        if ($updateShippingFeeNow->save()) {
                            return response()->json([
                                'message' => 'Deleted'
                            ], Response::HTTP_OK);
                        }
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

    //CHECK OUT ON TO PAY | CLIENT
    public function checkOut(Request $request)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $request->validate([
                    'payment' => 'required|string',
                ]);

                // Retrieving unpaid products for the user
                $data = OrderModel::where('user_id', $user->id)->where('status', 'UNPAID')->get();

                // Updating product status and payment method
                foreach ($data as $order) {
                    $order->status = "TO SHIP / TO PROCESS";
                    $order->payment_method = $request->input('payment');
                    $order->check_out_at = now();
                    $order->save();
                }

                // Creating a log for the checkout
                $userAction = 'CHECK OUT';
                $details = 'Checked out products with Order ID: ' . $data->pluck('order_id')->implode(', ');

                $create = LogsModel::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_action' => $userAction,
                    'details' => $details,
                    'created_at' => now()
                ]);

                // Checking if the log was created successfully
                if ($create) {
                    return response()->json([
                        'message' => 'Checkout',
                    ], Response::HTTP_OK);
                }
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

    // DISPLAY CANCEL ON TO SHIP | CLIENT
    public function getCancelItemOnCart(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $order = OrderModel::find($id);
                return response()->json([
                    'data' => $order
                ], Response::HTTP_OK);
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

    // CANCEL ON TO SHIP | CLIENT
    public function cancelItemOnCart(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();
            if ($user) {
                $request->validate([
                    'reasonCancel' => 'required|string',
                ]);

                // Retrieve the order to cancel
                $data = OrderModel::where('user_id', $user->id)
                    ->where('id', $id)
                    ->first();

                if ($data) { // Check if the order was found
                    if ($data->role == 'MAIN') {
                        // Cancel if the role is MAIN and one only on group_od
                        $count = OrderModel::where('group_id', $data->group_id)->count();
                        if ($count == 1) {
                            // Cancelled imidate if role is not MAIN
                            $data->status = 'CANCELLED';
                            $data->cancel_at = now();
                            $data->reason_cancel = $request->input('reasonCancel');
                            if ($data->save()) {
                                $userAction = 'CANCELLED';
                                $details = 'Cancelled Product Information with Group ID: ' . $data->group_id . "\n" .
                                    'Order ID: ' . $data->order_id . "\n" .
                                    'Product Group ID: ' . $data->product_group_id . "\n" .
                                    'Role: ' . $data->role . "\n" .
                                    'Category: ' . $data->category . "\n" .
                                    'Name: ' . $data->name . "\n" .
                                    'Image Name: ' . $data->image . "\n" .
                                    'Size: ' . $data->size . "\n" .
                                    'Color: ' . $data->color . "\n" .
                                    'Quantity: ' . $data->quantity . "\n" .
                                    'Discount: ' . $data->discount . "\n" .
                                    'Description: ' . $data->description . "\n" .
                                    'Product Price: ' . $data->product_price . "\n" .
                                    'Shipping Fee: ' . $data->shipping_fee . "\n" .
                                    'Total Price: ' . $data->total_price . "\n" .
                                    'Reason to Cancel' . $request->input('reasonCancel') . "\n";

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
                                        'message' => 'Cancelled'
                                    ], Response::HTTP_OK);
                                }
                            }
                        } else {
                            // Pass the role main to the others
                            $affectedRows = OrderModel::where('group_id', $data->group_id)
                                ->where('id', '!=', $id)
                                ->where('user_id', $user->id)
                                ->update([
                                    'role' => 'MAIN',
                                    'shipping_fee' => $data->shipping_fee,
                                ]);

                            if ($affectedRows) {
                                // Remove self Role and shipping Fee then change status to CANCELLED
                                $affectedRowsSelf = OrderModel::where('group_id', $data->group_id)
                                    ->where('id', '=', $id)
                                    ->where('user_id', $user->id)
                                    ->update([
                                        'role' => '',
                                        'shipping_fee' => 0.00,
                                        'status' => 'CANCELLED',
                                        'reason_cancel' => $request->input('reasonCancel'),
                                        'cancel_at' => now()
                                    ]);

                                if ($affectedRowsSelf) {
                                    $userAction = 'CANCELLED';
                                    $details = 'Cancelled Product Information with Group ID: ' . $data->group_id . "\n" .
                                        'Order ID: ' . $data->order_id . "\n" .
                                        'Product Group ID: ' . $data->product_group_id . "\n" .
                                        'Role: ' . $data->role . "\n" .
                                        'Category: ' . $data->category . "\n" .
                                        'Name: ' . $data->name . "\n" .
                                        'Image Name: ' . $data->image . "\n" .
                                        'Size: ' . $data->size . "\n" .
                                        'Color: ' . $data->color . "\n" .
                                        'Quantity: ' . $data->quantity . "\n" .
                                        'Discount: ' . $data->discount . "\n" .
                                        'Description: ' . $data->description . "\n" .
                                        'Product Price: ' . $data->product_price . "\n" .
                                        'Shipping Fee: ' . $data->shipping_fee . "\n" .
                                        'Total Price: ' . $data->total_price . "\n" .
                                        'Reason to Cancel' . $request->input('reasonCancel') . "\n";

                                    // Create Log
                                    $create = LogsModel::create([
                                        'user_id' => $user->id,
                                        'ip_address' => $request->ip(),
                                        'user_action' => $userAction,
                                        'details' => $details,
                                        'created_at' => now()
                                    ]);

                                    if ($create) {
                                        // Updating the Total Shipping fee now
                                        // Fetch the total Quantity
                                        $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                            ->where('status', 'TO SHIP / TO PROCESS')
                                            ->get();

                                        $totalQuantity = 0;
                                        foreach ($fetchAllQuantityAndCalculateShippingFee as $order) {
                                            $totalQuantity += $order->quantity;
                                        }

                                        // Calculate the Shipping Fee
                                        function calculateShippingFee($totalQuantity)
                                        {
                                            $shippingFee = 100; // Base shipping fee
                                            $rangeSize = 5; // Size of each range
                                            $feeIncrement = 100; // Fee increment for each range

                                            // Calculate the range index based on the quantity
                                            $rangeIndex = ceil($totalQuantity / $rangeSize);

                                            // Calculate the shipping fee based on the range index and quantity
                                            $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                                            return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                                        }

                                        // Saving Now
                                        $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                                            ->where('status', 'TO SHIP / TO PROCESS')
                                            ->where('role', 'MAIN')
                                            ->first();
                                        $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);

                                        if ($updateShippingFeeNow->save()) {
                                            return response()->json([
                                                'message' => 'Cancelled'
                                            ], Response::HTTP_OK);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Cancelled imidate if role is not MAIN
                        $data->status = 'CANCELLED';
                        $data->cancel_at = now();
                        $data->reason_cancel = $request->input('reasonCancel');
                        if ($data->save()) {
                            $userAction = 'CANCELLED';
                            $details = 'Cancelled Product Information with Group ID: ' . $data->group_id . "\n" .
                                'Order ID: ' . $data->order_id . "\n" .
                                'Product Group ID: ' . $data->product_group_id . "\n" .
                                'Role: ' . $data->role . "\n" .
                                'Category: ' . $data->category . "\n" .
                                'Name: ' . $data->name . "\n" .
                                'Image Name: ' . $data->image . "\n" .
                                'Size: ' . $data->size . "\n" .
                                'Color: ' . $data->color . "\n" .
                                'Quantity: ' . $data->quantity . "\n" .
                                'Discount: ' . $data->discount . "\n" .
                                'Description: ' . $data->description . "\n" .
                                'Product Price: ' . $data->product_price . "\n" .
                                'Shipping Fee: ' . $data->shipping_fee . "\n" .
                                'Total Price: ' . $data->total_price . "\n" .
                                'Reason to Cancel' . $request->input('reasonCancel') . "\n";

                            // Create Log
                            $create = LogsModel::create([
                                'user_id' => $user->id,
                                'ip_address' => $request->ip(),
                                'user_action' => $userAction,
                                'details' => $details,
                                'created_at' => now()
                            ]);

                            if ($create) {
                                // Updating the Total Shipping fee now
                                // Fetch the total Quantity
                                $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'TO SHIP / TO PROCESS')
                                    ->get();

                                $totalQuantity = 0;
                                foreach ($fetchAllQuantityAndCalculateShippingFee as $order) {
                                    $totalQuantity += $order->quantity;
                                }

                                // Calculate the Shipping Fee
                                function calculateShippingFee($totalQuantity)
                                {
                                    $shippingFee = 100; // Base shipping fee
                                    $rangeSize = 5; // Size of each range
                                    $feeIncrement = 100; // Fee increment for each range

                                    // Calculate the range index based on the quantity
                                    $rangeIndex = ceil($totalQuantity / $rangeSize);

                                    // Calculate the shipping fee based on the range index and quantity
                                    $shippingFee += ($rangeIndex - 1) * $feeIncrement;

                                    return number_format($shippingFee, 2); // Format the shipping fee with two decimal places
                                }

                                // Saving Now
                                $updateShippingFeeNow = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'TO SHIP / TO PROCESS')
                                    ->where('role', 'MAIN')
                                    ->first();
                                $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);

                                if ($updateShippingFeeNow->save()) {
                                    return response()->json([
                                        'message' => 'Cancelled'
                                    ], Response::HTTP_OK);
                                }
                            }
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

    // RETURN ON TO RECEIVED | CLIENT
    public function return (Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                // Retrieve the order to cancel
                $data = OrderModel::where('user_id', $user->id)
                    ->where('status', 'SHIPPING')
                    ->where('id', $id)
                    ->first();

                if ($data) {
                    $request->validate([
                        'returnReason' => 'required|string|max:255',
                        'image1' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                        'image2' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                        'image3' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                        'image4' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                        'description' => 'string|max:255',
                        'returnSolution' => 'required|string|max:255',
                    ]);

                    // Generate unique filenames for images
                    $image1 = $request->file('image1');
                    $image2 = $request->file('image2');
                    $image3 = $request->file('image3');
                    $image4 = $request->file('image4');

                    $imageActualExt1 = $image1->getClientOriginalExtension();
                    $imageActualExt2 = $image2 ? $image2->getClientOriginalExtension() : '';
                    $imageActualExt3 = $image3 ? $image3->getClientOriginalExtension() : '';
                    $imageActualExt4 = $image4 ? $image4->getClientOriginalExtension() : '';

                    $filename1 = uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt1;
                    $filename2 = $image2 ? uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt2 : '';
                    $filename3 = $image3 ? uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt3 : '';
                    $filename4 = $image4 ? uniqid() . "_" . time() . "_" . mt_rand() . "." . $imageActualExt4 : '';

                    // Store the images on the 'public' disk with the generated filenames
                    Storage::disk('public')->put($filename1, file_get_contents($image1));
                    if ($image2)
                        Storage::disk('public')->put($filename2, file_get_contents($image2));
                    if ($image3)
                        Storage::disk('public')->put($filename3, file_get_contents($image3));
                    if ($image4)
                        Storage::disk('public')->put($filename4, file_get_contents($image4));

                    // Delete if not role MAIN
                    $userAction = 'RETURN';
                    $details = 'Return Product Information with Group ID: ' . $data->group_id . "\n" .
                        'Order ID: ' . $data->id . "\n" . // Changed $data to $data->id
                        'Product Group ID: ' . $data->group_id . "\n" .
                        'Role: ' . $data->role . "\n" .
                        'Category: ' . $data->category . "\n" .
                        'Product Name: ' . $data->name . "\n" .
                        'Image Name: ' . $data->image . "\n" .
                        'Size: ' . $data->size . "\n" .
                        'Color: ' . $data->color . "\n" .
                        'Quantity: ' . $data->quantity . "\n" . // Changed $data to $data->quantity
                        'Discount: ' . $data->discount . "\n" .
                        'Description: ' . $data->description . "\n" .
                        'Product Price: ' . $data->price . "\n" .
                        'Shipping Fee: ' . $data->shipping_fee . "\n" . // Changed $data to $data->shipping_fee
                        'Total Price: ' . $data->total_price . "\n" . // Changed $data to $data->total_price
                        'Status: ' . $data->status . "\n" .
                        'Reason: ' . $request->input('returnReason') . "\n" .
                        'Description: ' . $request->input('description') . "\n" .
                        'Solution: ' . $request->input('returnSolution') . "\n";

                    // Create Log
                    $created = LogsModel::create([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_action' => $userAction,
                        'details' => $details,
                        'created_at' => now(),
                    ]);

                    if ($created) {
                        $data->status = "RETURN REFUND / TO RESPOND";
                        $data->return_reason = $request->input('returnReason');
                        $data->return_description = $request->input('description');
                        $data->return_solution = $request->input('returnSolution');

                        $data->return_image1 = $filename1;
                        $data->return_image2 = $filename2;
                        $data->return_image3 = $filename3;
                        $data->return_image4 = $filename4;
                        $data->return_at = now();

                        if ($data->save()) {
                            return response()->json([
                                'message' => 'Created',
                            ], Response::HTTP_OK);
                        }
                    }
                }
            } else {
                return response()->json([
                    'message' => 'Intruder',
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

    public function completedClient(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::where('id', $id)
                ->where('status', 'SHIPPING')
                ->where('user_id', $user->id)
                ->first(); // Use first() to retrieve the order

                if ($order) {
                    $order->status = 'COMPLETED';
                    $order->completed_at = now();

                    if ($order->save()) {
                        $userAction = 'COMPLETE';
                        $details = "Complete Ship this product Information with Group ID: {$order->group_id}\n" .
                            "Order ID: {$order->order_id}\n" .
                            "Product Group ID: {$order->product_group_id}\n" .
                            "Role: {$order->role}\n" .
                            "Category: {$order->category}\n" .
                            "Name: {$order->name}\n" .
                            "Image Name: {$order->image}\n" .
                            "Size: {$order->size}\n" .
                            "Color: {$order->color}\n" .
                            "Quantity: {$order->quantity}\n" .
                            "Discount: {$order->discount}\n" .
                            "Description: {$order->description}\n" .
                            "Product Price: {$order->product_price}\n" .
                            "Shipping Fee: {$order->shipping_fee}\n" .
                            "Total Price: {$order->total_price}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Complete'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            } else {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_UNAUTHORIZED);
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

    // MARK AS DONE PER ITEM | ADMIN
    public function markAsDonePerItem(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::where('id', $id)
                    ->where('status', 'TO SHIP / TO PROCESS')
                    ->first();

                if ($order) {
                    $order->status = 'TO SHIP / PROCESSED';
                    $order->mark_as_done_at = now();

                    if ($order->save()) {
                        $userAction = 'MARK AS DONE';
                        $details = "Mark as done this product Information with Group ID: {$order->group_id}\n" .
                            "Order ID: {$order->order_id}\n" .
                            "Product Group ID: {$order->product_group_id}\n" .
                            "Role: {$order->role}\n" .
                            "Category: {$order->category}\n" .
                            "Name: {$order->name}\n" .
                            "Image Name: {$order->image}\n" .
                            "Size: {$order->size}\n" .
                            "Color: {$order->color}\n" .
                            "Quantity: {$order->quantity}\n" .
                            "Discount: {$order->discount}\n" .
                            "Description: {$order->description}\n" .
                            "Product Price: {$order->product_price}\n" .
                            "Shipping Fee: {$order->shipping_fee}\n" .
                            "Total Price: {$order->total_price}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Marked as done'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            } else {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_UNAUTHORIZED);
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

    // MARK AS DONE ALL | ADMIN
    public function markAsDoneAllItem(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'TO SHIP / TO PROCESS')
                    ->update([
                        'status' => 'TO SHIP / PROCESSED',
                        'mark_as_done_at' => now()
                    ]);

                if ($affectedRows > 0) {
                    $userAction = 'MARK AS DONE ALL';
                    $details = "Marked as done for orders with Group ID: {$id}";

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
                            'message' => 'Marked as done for all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'No orders found with the given criteria'
                    ], Response::HTTP_NOT_FOUND);
                }
            } else {
                return response()->json([
                    'message' => 'Intruder'
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

    // SHIP ALL | ADMIN
    public function shipAll(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'TO SHIP / PROCESSED')
                    ->update([
                        'status' => 'SHIPPING',
                        'ship_at' => now()
                    ]);

                if ($affectedRows > 0) {
                    $userAction = 'SHIP ALL';
                    $details = "Ship all orders with Group ID: {$id}";

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
                            'message' => 'Ship all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'No orders found with the given criteria'
                    ], Response::HTTP_NOT_FOUND);
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

    // COMPLETE PER ITEM | ADMIN
    public function completePerItem(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::find($id);

                if ($order) {
                    $order->status = 'COMPLETED';
                    $order->completed_at = now();

                    if ($order->save()) {
                        $userAction = 'COMPLETE';
                        $details = "Complete Ship this product Information with Group ID: {$order->group_id}\n" .
                            "Order ID: {$order->order_id}\n" .
                            "Product Group ID: {$order->product_group_id}\n" .
                            "Role: {$order->role}\n" .
                            "Category: {$order->category}\n" .
                            "Name: {$order->name}\n" .
                            "Image Name: {$order->image}\n" .
                            "Size: {$order->size}\n" .
                            "Color: {$order->color}\n" .
                            "Quantity: {$order->quantity}\n" .
                            "Discount: {$order->discount}\n" .
                            "Description: {$order->description}\n" .
                            "Product Price: {$order->product_price}\n" .
                            "Shipping Fee: {$order->shipping_fee}\n" .
                            "Total Price: {$order->total_price}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Complete'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            } else {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_UNAUTHORIZED);
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

    // FAILED PER ITEM | ADMIN
    public function failedPerItem(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::find($id);

                if ($order) {
                    $order->status = 'FAILED';
                    $order->failed_at = now();

                    if ($order->save()) {
                        $userAction = 'FAIL';
                        $details = "Fail this product Information with Group ID: {$order->group_id}\n" .
                            "Order ID: {$order->order_id}\n" .
                            "Product Group ID: {$order->product_group_id}\n" .
                            "Role: {$order->role}\n" .
                            "Category: {$order->category}\n" .
                            "Name: {$order->name}\n" .
                            "Image Name: {$order->image}\n" .
                            "Size: {$order->size}\n" .
                            "Color: {$order->color}\n" .
                            "Quantity: {$order->quantity}\n" .
                            "Discount: {$order->discount}\n" .
                            "Description: {$order->description}\n" .
                            "Product Price: {$order->product_price}\n" .
                            "Shipping Fee: {$order->shipping_fee}\n" .
                            "Total Price: {$order->total_price}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Fail'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_NOT_FOUND);
                }
            } else {
                return response()->json([
                    'message' => 'Intruder'
                ], Response::HTTP_UNAUTHORIZED);
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

    // COMPLETE ALL ITEM | ADMIN
    public function completeAll(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'SHIPPING')
                    ->update([
                        'status' => 'COMPLETED',
                        'completed_at' => now()
                    ]);

                if ($affectedRows > 0) {
                    $userAction = 'COMPLETE ALL';
                    $details = "Complete all orders with Group ID: {$id}";

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
                            'message' => 'Complete all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'No orders found with the given criteria'
                    ], Response::HTTP_NOT_FOUND);
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

    // Fail ALL ITEM | ADMIN
    public function failAll(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session'))
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'SHIPPING')
                    ->update([
                        'status' => 'FAILED',
                        'failed_at' => now()
                    ]);

                if ($affectedRows > 0) {
                    $userAction = 'FAIL ALL';
                    $details = "Fail all orders with Group ID: {$id}";

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
                            'message' => 'Fail all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'No orders found with the given criteria'
                    ], Response::HTTP_NOT_FOUND);
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