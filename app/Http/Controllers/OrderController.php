<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{

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

    // Client To pay Component
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

    // Client Add to Cart
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
                    'quantity' => 'required|min:1',
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

    // Client Edit on To pay component Display Specific product
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

            if ($user) {
                if ($data->role == 'MAIN') {
                    $mainProductsCount = OrderModel::where('group_id', $data->group_id)->count();
                    // Delete if role MAIN and one only
                    if ($mainProductsCount === 1) {
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
                            // Updating the Total Shipping fee now
                            // Fetch the total Quantity
                            $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                ->where('status', 'UNPAID')
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
                                ->where('status', 'UNPAID')
                                ->where('role', 'MAIN')
                                ->first();
                            $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);

                            if ($updateShippingFeeNow->save()) {
                                if ($data->delete()) {
                                    return response()->json([
                                        'message' => 'Deleted'
                                    ], Response::HTTP_OK);
                                }
                            }
                        }
                    } else {
                        // Update a single product with the same group_id to have role 'MAIN' and Shipping fee
                        $affectedRows = OrderModel::where('group_id', $data->group_id)
                            ->where('id', '!=', $id)
                            ->limit(1)
                            ->update(['role' => 'MAIN', 'shipping_fee' => $data->shipping_fee]); // Combine both columns in one array


                        if ($affectedRows) {
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
                                // Updating the Total Shipping fee now
                                // Fetch the total Quantity
                                $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
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
                                    ->where('status', 'UNPAID')
                                    ->where('role', 'MAIN')
                                    ->first();
                                $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);

                                if ($updateShippingFeeNow->save()) {
                                    if ($data->delete()) {
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

                    if ($create) {
                        // Updating the Total Shipping fee now
                        // Fetch the total Quantity
                        $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                            ->where('status', 'UNPAID')
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
                            ->where('status', 'UNPAID')
                            ->where('role', 'MAIN')
                            ->first();
                        $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);

                        if ($updateShippingFeeNow->save()) {
                            if ($data->delete()) {
                                return response()->json([
                                    'message' => 'Deleted'
                                ], Response::HTTP_OK);
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

}