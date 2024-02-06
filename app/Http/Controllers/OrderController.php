<?php

namespace App\Http\Controllers;

use App\Models\AuthModel;
use App\Models\LogsModel;
use App\Models\OrderModel;
use Illuminate\Support\Str;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Models\UserInfoModel;
use App\Models\VouchersModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{

    public function index()
    {
        try {
            // Fetch all orders from the OrderModel
            $orders = OrderModel::all();

            // Initialize an array to store the final data
            $data = [];

            // Loop through each order
            foreach ($orders as $order) {
                // Check if user_id is available
                if (!$order->user_id) {
                    // Handle the case where user_id is not available
                    // You may log a warning or skip the current iteration based on your requirements
                    continue; // Skip the current iteration and move to the next order
                }

                // Retrieve user information for the current booking
                $userInfo = UserInfoModel::where('user_id', $order->user_id)->first();
                $authInfo = AuthModel::where('id', $order->user_id)->first();

                // Check if user information is available
                if (!$userInfo || !$authInfo) {
                    // Handle the case where user information is not found
                    // You may log a warning, provide default values, or skip the current iteration based on your requirements
                    $defaultUserInfo = [
                        'first_name' => null,
                        'last_name' => null,
                        'contact_num' => null,
                        'address_1' => null,
                        'address_2' => null,
                        'region_code' => null,
                        'province_code' => null,
                        'city_or_municipality_code' => null,
                        'region_name' => null,
                        'province_name' => null,
                        'city_or_municipality_name' => null,
                        'barangay' => null,
                        'description_location' => null,
                        // ... (other fields with default values)
                    ];

                    $defaultAuthInfo = [
                        'email' => null,
                        // ... (other fields with default values)
                    ];

                    // Create an array for the current booking data with default values
                    $bookingData = [
                        'authInfo' => $defaultAuthInfo,
                        'userInfo' => $defaultUserInfo,
                        'id' => null,
                        'user_id' => null,
                        'group_id' => null,
                        'order_id' => null,
                        'product_group_id' => null,
                        'voucher_shipping_id' => null,
                        'voucher_discount_id' => null,
                        'role' => null,
                        'category' => null,
                        'name' => null,
                        'image' => null,
                        'size' => null,
                        'color' => null,
                        'quantity' => null,
                        'discount' => null,
                        'description' => null,
                        'promo' => null,
                        'promo_buy_and_take_count' => null,
                        'voucher_name_shipping' => null,
                        'voucher_name_discount' => null,
                        'voucher_discount' => null,
                        'product_price' => null,
                        'shipping_fee' => null,
                        'total_price' => null,
                        'final_total_price' => null,
                        'payment_method' => null,
                        'status' => null,
                        'reason_cancel' => null,
                        'return_reason' => null,
                        'return_image1' => null,
                        'return_image2' => null,
                        'return_image3' => null,
                        'return_image4' => null,
                        'return_description' => null,
                        'return_solution' => null,
                        'return_shipping_at' => null,
                        'return_accept_at' => null,
                        'return_decline_at' => null,
                        'return_completed_at' => null,
                        'return_failed_at' => null,
                        'check_out_at' => null,
                        'cancel_at' => null,
                        'order_receive_at' => null,
                        'mark_as_done_at' => null,
                        'ship_at' => null,
                        'completed_at' => null,
                        'failed_at' => null,
                        'return_at' => null,
                        'created_at' => null,
                        'updated_at' => null,
                        // ... (other fields with default values)
                    ];

                    // Add the current booking data to the final data array
                    $data[] = $bookingData;

                    // Skip the current iteration and move to the next order
                    continue;
                }

                // Create an array for the current booking data in the desired format
                $bookingData = [
                    'authInfo' => [
                        'email' => $authInfo->email,
                        // ... (other fields from authInfo)
                    ],
                    'userInfo' => [
                        'first_name' => Crypt::decrypt($userInfo->first_name) ?? null,
                        'last_name' => Crypt::decrypt($userInfo->last_name) ?? null,
                        'contact_num' => Crypt::decrypt($userInfo->contact_num) ?? null,
                        'address_1' => Crypt::decrypt($userInfo->address_1) ?? null,
                        'address_2' => Crypt::decrypt($userInfo->address_2) ?? null,
                        'region_code' => Crypt::decrypt($userInfo->region_code) ?? null,
                        'province_code' => Crypt::decrypt($userInfo->province_code) ?? null,
                        'city_or_municipality_code' => Crypt::decrypt($userInfo->city_or_municipality_code) ?? null,
                        'region_name' => Crypt::decrypt($userInfo->region_name) ?? null,
                        'province_name' => Crypt::decrypt($userInfo->province_name) ?? null,
                        'city_or_municipality_name' => Crypt::decrypt($userInfo->city_or_municipality_name) ?? null,
                        'barangay' => Crypt::decrypt($userInfo->barangay) ?? null,
                        'description_location' => Crypt::decrypt($userInfo->description_location) ?? null,
                        // ... (other fields from userInfo)
                    ],
                    'id' => $order->id,
                    'user_id' => $order->user_id,
                    'group_id' => $order->group_id,
                    'order_id' => $order->order_id,
                    'product_group_id' => $order->product_group_id,
                    'voucher_shipping_id' => $order->voucher_shipping_id,
                    'voucher_discount_id' => $order->voucher_discount_id,
                    'role' => $order->role,
                    'category' => $order->category,
                    'name' => $order->name,
                    'image' => $order->image,
                    'size' => $order->size,
                    'color' => $order->color,
                    'quantity' => $order->quantity,
                    'discount' => $order->discount,
                    'description' => $order->description,
                    'promo' => $order->promo,
                    'promo_buy_and_take_count' => $order->promo_buy_and_take_count,
                    'voucher_name_shipping' => $order->voucher_name_shipping,
                    'voucher_name_discount' => $order->voucher_name_discount,
                    'voucher_discount' => $order->voucher_discount,
                    'product_price' => $order->product_price,
                    'shipping_fee' => $order->shipping_fee,
                    'total_price' => $order->total_price,
                    'final_total_price' => $order->final_total_price,
                    'payment_method' => $order->payment_method,
                    'status' => $order->status,
                    'reason_cancel' => $order->reason_cancel,
                    'return_reason' => $order->return_reason,
                    'return_image1' => $order->return_image1,
                    'return_image2' => $order->return_image2,
                    'return_image3' => $order->return_image3,
                    'return_image4' => $order->return_image4,
                    'return_description' => $order->return_description,
                    'return_solution' => $order->return_solution,
                    'return_shipping_at' => $order->return_shipping_at,
                    'return_accept_at' => $order->return_accept_at,
                    'return_decline_at' => $order->return_decline_at,
                    'return_completed_at' => $order->return_completed_at,
                    'return_failed_at' => $order->return_failed_at,
                    'check_out_at' => $order->check_out_at,
                    'cancel_at' => $order->cancel_at,
                    'order_receive_at' => $order->order_receive_at,
                    'mark_as_done_at' => $order->mark_as_done_at,
                    'ship_at' => $order->ship_at,
                    'completed_at' => $order->completed_at,
                    'failed_at' => $order->failed_at,
                    'return_at' => $order->return_at,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    // ... (other fields from $order)
                ];

                // Add the current booking data to the final data array
                $data[] = $bookingData;
            }

            // Return the user information records in JSON format
            return response()->json([
                'data' => $data
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
            $user = AuthModel::where('session_login', $id ?? 'asd')
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


    public function addToCart(Request $request)
    {
        try {
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $request->validate([
                    'quantity' => 'required|numeric|min:1',
                    'group_id' => 'required|string',
                ]);

                $query = ProductModel::where('group_id', $request->input('group_id'));
                if ($request->has('color')) {
                    $query->where('color', $request->input('color'));
                }
                if ($request->has('size')) {
                    $query->where('size', $request->input('size'));
                }
                $product = $query->first();

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
                        // Same group id, size and color on Cart just update the total price, quantity and final price
                        $query = OrderModel::where('user_id', $user->id)
                            ->where('category', $product->category)
                            ->where('name', $product->name)
                            ->where('status', 'UNPAID');
                        if ($request->has('color')) {
                            $query->where('color', $request->input('color'));
                        }
                        if ($request->has('size')) {
                            $query->where('size', $request->input('size'));
                        }
                        $checkSameOrder = $query->first();
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
                                // ************************************ //
                                // Buy 3 for 990 promo
                                // Fetch orders based on the provided criteria
                                $buy3For990 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('category', $product->category)
                                    ->where('name', $product->name)
                                    ->where('promo', 'BUY 3 FOR 990')
                                    ->where('color', $request->input('color'))
                                    ->where('size', $request->input('size'))
                                    ->first(); // Use first() to get a single order

                                if ($buy3For990) {
                                    $quantity = $buy3For990->quantity;

                                    // Calculate the total price based on the promo logic
                                    $divisibleBy3 = intdiv($quantity, 3);
                                    $basePrice = $divisibleBy3 * 990;
                                    $additionalItems = $quantity % 3;
                                    $additionalPrice = $additionalItems * $buy3For990->product_price;

                                    // Update the order's total price
                                    $buy3For990->total_price = $basePrice + $additionalPrice;

                                    // Save the updated order
                                    $buy3For990->save();
                                }
                                // ************************************ //


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

                                // ****************************//
                                // CALCULATING THE FINAL TOTAL PRICE UNPAID
                                $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->first(); // Get the first matching order

                                if ($getAllOderUnpaid) { // Check if there is a matching order
                                    $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->sum('total_price');

                                    // Update the final_total_price of the first matching order
                                    $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                                    $getAllOderUnpaid->save();
                                }
                                // ****************************//

                                // ****************************//
                                // 'BUY 1 TAKE 1' promo
                                // Find the latest inserted order with 'BUY 2 TAKE 1' promo
                                $latestOrderBuy2Take1 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 1 TAKE 1')
                                    ->where('id', $checkSameOrder->id)
                                    ->latest()
                                    ->first();
                                if ($latestOrderBuy2Take1) {
                                    $latestOrderBuy2Take1->promo_buy_and_take_count = $latestOrderBuy2Take1->quantity;
                                    $latestOrderBuy2Take1->save();
                                }
                                // ****************************//

                                // ****************************//
                                // 'BUY 2 TAKE 1' promo
                                // Find the latest inserted order with 'BUY 2 TAKE 1' promo
                                $latestOrderBuy2Take1 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 2 TAKE 1')
                                    ->where('id', $checkSameOrder->id)
                                    ->latest()
                                    ->first();

                                $totalTake = 0;

                                if ($latestOrderBuy2Take1) {
                                    $totalProducts = $latestOrderBuy2Take1->quantity;
                                    $totalTake = intdiv($totalProducts, 2);
                                    $latestOrderBuy2Take1->promo_buy_and_take_count = $totalTake;
                                    $latestOrderBuy2Take1->save();
                                }
                                // ****************************//

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
                                'promo' => $product->promo ?? null,
                                'product_price' => $product->price,
                                'shipping_fee' => 0.00,
                                'total_price' => $totalPrice,
                                'status' => 'UNPAID'
                            ]);

                            // Logs
                            if ($created) {
                                // ************************************ //
                                // Buy 3 for 990 promo
                                // Fetch orders based on the provided criteria
                                $buy3For990 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 3 FOR 990')
                                    ->get();

                                foreach ($buy3For990 as $order) {
                                    $quantity = $order->quantity;

                                    // Calculate the total price based on the promo logic
                                    $divisibleBy3 = intdiv($quantity, 3);
                                    $basePrice = $divisibleBy3 * 990;
                                    $additionalItems = $quantity % 3;
                                    $additionalPrice = $additionalItems * $order->product_price;

                                    // Update the order's total price
                                    $order->total_price = $basePrice + $additionalPrice;

                                    // Save the updated order
                                    $order->save();
                                }
                                // ************************************ //


                                // ****************************//
                                // 'BUY 1 TAKE 1' promo
                                // Find the latest inserted order with 'BUY 1 TAKE 1' promo
                                $latestOrderBuy1Take1 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 1 TAKE 1')
                                    ->latest()
                                    ->first();

                                if ($latestOrderBuy1Take1) {
                                    $quantity = $latestOrderBuy1Take1->quantity;

                                    // Update promo_buy_and_take_count for the latest order with the quantity
                                    $latestOrderBuy1Take1->promo_buy_and_take_count = $quantity;
                                    $latestOrderBuy1Take1->save();
                                }
                                // ****************************//

                                // ****************************//
                                // 'BUY 2 TAKE 1' promo
                                // Find the latest inserted order with 'BUY 2 TAKE 1' promo
                                $latestOrderBuy2Take1 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 2 TAKE 1')
                                    ->latest()
                                    ->first();
                                $totalTake = 0;

                                if ($latestOrderBuy2Take1) {
                                    $totalProducts = $latestOrderBuy2Take1->quantity;
                                    $totalTake = intdiv($totalProducts, 2);
                                    $latestOrderBuy2Take1->promo_buy_and_take_count = $totalTake;
                                    $latestOrderBuy2Take1->save();
                                }
                                // ****************************//

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
                                    'Promo: ' . $product->promo . "\n" .
                                    'Product Price: ' . $product->price . "\n" .
                                    'Total Price: ' . $totalPrice . "\n" .
                                    'Status: ' . 'UNPAID' . "\n";
                                // Create Log
                                $create = LogsModel::create([
                                    'user_id' => $user->id,
                                    'ip_address' => $request->ip(),
                                    'user_action' => $userAction,
                                    'details' => $details,
                                    'created_at' => Carbon::now()
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

                                    // ****************************//
                                    // CALCULATING THE FINAL TOTAL PRICE UNPAID
                                    $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->first(); // Get the first matching order

                                    if ($getAllOderUnpaid) { // Check if there is a matching order
                                        $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                            ->where('status', 'UNPAID')
                                            ->sum('total_price');

                                        // Update the final_total_price of the first matching order
                                        $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                                        $getAllOderUnpaid->save();
                                    }
                                    // ****************************//

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
                        // Same color or size Add to Cart just update the total price and quantity
                        $query = OrderModel::where('user_id', $user->id)
                            ->where('category', $product->category)
                            ->where('name', $product->name)
                            ->where('status', 'UNPAID');
                        if ($request->has('color')) {
                            $query->where('color', $request->input('color'));
                        }
                        if ($request->has('size')) {
                            $query->where('size', $request->input('size'));
                        }
                        $checkSameOrder = $query->first();

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



                                // ****************************//
                                // CALCULATING THE FINAL TOTAL PRICE UNPAID
                                $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->first(); // Get the first matching order

                                if ($getAllOderUnpaid) { // Check if there is a matching order
                                    $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->sum('total_price');

                                    // Update the final_total_price of the first matching order
                                    $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                                    $getAllOderUnpaid->save();
                                }
                                // ****************************//

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
                            $shippingFees = ($numGroups * $baseShippingFee) + ($product->promo !== 'BUY 3 FOR 990 WITH FREE SHIPPING' ? $baseShippingFee : 0);

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
                                'promo' => $product->promo ?? null,
                                'product_price' => $product->price,
                                'shipping_fee' => $shippingFees,
                                'total_price' => $totalPrice,
                                'status' => 'UNPAID'
                            ]);

                            if ($created) {
                                // ************************************ //
                                // Buy 3 for 990 promo
                                // Fetch orders based on the provided criteria
                                $buy3For990 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 3 FOR 990')
                                    ->get();

                                foreach ($buy3For990 as $order) {
                                    $quantity = $order->quantity;

                                    // Calculate the total price based on the promo logic
                                    $divisibleBy3 = intdiv($quantity, 3);
                                    $basePrice = $divisibleBy3 * 990;
                                    $additionalItems = $quantity % 3;
                                    $additionalPrice = $additionalItems * $order->product_price;

                                    // Update the order's total price
                                    $order->total_price = $basePrice + $additionalPrice;

                                    // Save the updated order
                                    $order->save();
                                }
                                // ************************************ //

                                // ************************************ //
                                // Buy 3 for 990 promo with free shipping
                                // Fetch orders based on the provided criteria
                                $buy3For990WithFreeShipping = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 3 FOR 990 WITH FREE SHIPPING')
                                    ->get();

                                foreach ($buy3For990WithFreeShipping as $orderWithFreeShipping) {
                                    $quantity = $orderWithFreeShipping->quantity;

                                    // Calculate the total price based on the promo logic
                                    $divisibleBy3 = intdiv($quantity, 3);
                                    $basePrice = $divisibleBy3 * 990;
                                    $additionalItems = $quantity % 3;
                                    $additionalPrice = $additionalItems * $orderWithFreeShipping->product_price;

                                    // Update the orderWithFreeShipping's total price
                                    $orderWithFreeShipping->total_price = $basePrice + $additionalPrice;

                                    // Save the updated orderWithFreeShipping
                                    $orderWithFreeShipping->save();
                                }
                                // ************************************ //

                                // ****************************//
                                // CALCULATING THE FINAL TOTAL PRICE UNPAID
                                $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->first(); // Get the first matching order

                                if ($getAllOderUnpaid) { // Check if there is a matching order
                                    $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->sum('total_price');

                                    // Update the final_total_price of the first matching order
                                    $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                                    $getAllOderUnpaid->save();
                                }
                                // ****************************//


                                // ****************************//
                                // 'BUY 1 TAKE 1' promo
                                // Find the latest inserted order with 'BUY 1 TAKE 1' promo
                                $latestOrderBuy1Take1 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 1 TAKE 1')
                                    ->latest()
                                    ->first();

                                if ($latestOrderBuy1Take1) {
                                    $quantity = $latestOrderBuy1Take1->quantity;

                                    // Update promo_buy_and_take_count for the latest order with the quantity
                                    $latestOrderBuy1Take1->promo_buy_and_take_count = $quantity;
                                    $latestOrderBuy1Take1->save();
                                }
                                // ****************************//

                                // ****************************//
                                // 'BUY 2 TAKE 1' promo
                                // Find the latest inserted order with 'BUY 2 TAKE 1' promo
                                $latestOrderBuy2Take1 = OrderModel::where('status', 'UNPAID')
                                    ->where('user_id', $user->id)
                                    ->where('promo', 'BUY 2 TAKE 1')
                                    ->latest()
                                    ->first();
                                $totalTake = 0;

                                if ($latestOrderBuy2Take1) {
                                    $totalProducts = $latestOrderBuy2Take1->quantity;
                                    $totalTake = intdiv($totalProducts, 2);
                                    $latestOrderBuy2Take1->promo_buy_and_take_count = $totalTake;
                                    $latestOrderBuy2Take1->save();
                                }
                                // ****************************//


                                $userAction = 'CREATED';
                                $details = 'Add To Cart Product Information with Group ID: ' . $product->group_id . "\n" .
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
                                    'Promo: ' . $product->promo . "\n" .
                                    'Product Price: ' . $product->price . "\n" .
                                    'Status: ' . 'UNPAID' . "\n";

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

    // Update TO PAY ITEM | CLIENT 
    public function updateItemOnCart(Request $request, $id)
    {

        try {
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $request->validate([
                    'quantity' => 'required|numeric|min:1',
                ]);

                $order = OrderModel::where('user_id', $user->id)
                    ->where('id', $id)
                    ->first(); // Retrieve the order from the database

                if ($order) {
                    if ($request->has('size') !== '') {
                        $product = ProductModel::where('group_id', $request->input('group_id'))
                            ->when($request->has('size'), function ($query) use ($request) {
                                return $query->where('size', $request->input('size'));
                            })
                            ->first();
                    } else {
                        $product = ProductModel::where('group_id', $request->input('group_id'))
                            ->when($request->has('color'), function ($query) use ($request) {
                                return $query->where('color', $request->input('color'));
                            })->first();
                    }

                    if ($product && $product->quantity >= 1) {
                        // Declare
                        $quantity = (int) $request->input('quantity');

                        // Compute the total Price Now by Check on the product table
                        $discountedPrice = $product->price * (1 - ($product->discount / 100));
                        $totalPrice = $discountedPrice * $quantity;

                        // Final total Quantity and Price
                        $finalTotalPrice = $totalPrice;
                        $finalTotalQuantity = $quantity;

                        // Saving
                        $order->total_price = $finalTotalPrice;
                        $order->quantity = $finalTotalQuantity;

                        if ($order->save()) {
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


                            // ************************************ //
                            // Buy 3 for 990 promo
                            // Fetch orders based on the provided criteria
                            $buy3For990 = OrderModel::where('id', $id)
                                ->where('user_id', $user->id)
                                ->first(); // Use first() to get a single order

                            if ($buy3For990) {
                                $quantity = $buy3For990->quantity;

                                // Calculate the total price based on the promo logic
                                $divisibleBy3 = intdiv($quantity, 3);
                                $basePrice = $divisibleBy3 * 990;
                                $additionalItems = $quantity % 3;
                                $additionalPrice = $additionalItems * $buy3For990->product_price;

                                // Update the order's total price
                                $buy3For990->total_price = $basePrice + $additionalPrice;

                                // Save the updated order
                                $buy3For990->save();
                            }
                            // ************************************ //

                            // ****************************//
                            // CALCULATING THE FINAL TOTAL PRICE UNPAID
                            $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                                ->where('status', 'UNPAID')
                                ->first(); // Get the first matching order

                            if ($getAllOderUnpaid) { // Check if there is a matching order
                                $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->sum('total_price');

                                // Update the final_total_price of the first matching order
                                $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                                $getAllOderUnpaid->save();
                            }
                            // ****************************//

                            // ****************************//
                            // 'BUY 1 TAKE 1' promo
                            // Find the latest inserted order with 'BUY 2 TAKE 1' promo
                            $latestOrderBuy2Take1 = OrderModel::where('status', 'UNPAID')
                                ->where('user_id', $user->id)
                                ->where('promo', 'BUY 1 TAKE 1')
                                ->where('id', $id)
                                ->latest()
                                ->first();
                            if ($latestOrderBuy2Take1) {
                                $latestOrderBuy2Take1->promo_buy_and_take_count = $latestOrderBuy2Take1->quantity;
                                $latestOrderBuy2Take1->save();
                            }
                            // ****************************//

                            // ****************************//
                            // 'BUY 2 TAKE 1' promo
                            // Find the latest inserted order with 'BUY 2 TAKE 1' promo
                            $latestOrderBuy2Take1 = OrderModel::where('status', 'UNPAID')
                                ->where('user_id', $user->id)
                                ->where('promo', 'BUY 2 TAKE 1')
                                ->where('id', $id)
                                ->latest()
                                ->first();

                            $totalTake = 0;

                            if ($latestOrderBuy2Take1) {
                                $totalProducts = $latestOrderBuy2Take1->quantity;
                                $totalTake = intdiv($totalProducts, 2);
                                $latestOrderBuy2Take1->promo_buy_and_take_count = $totalTake;
                                $latestOrderBuy2Take1->save();
                            }
                            // ****************************//

                            if ($updateShippingFeeNow->save()) {
                                return response()->json([
                                    'message' => 'Updated'
                                ], Response::HTTP_OK);
                            }
                        }
                    } else {
                        return response()->json([
                            'message' => 'Selected product is unavailable or out of stock.'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found.'
                    ], Response::HTTP_NOT_FOUND);
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
                        'created_at' => Carbon::now()
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
                            'created_at' => Carbon::now()
                        ]);

                        if ($create) {
                            if ($data->delete()) {
                                // ************************************ //
                                // Buy 3 for 990 promo
                                // Fetch orders based on the provided criteria
                                $buy3For990 = OrderModel::where('id', $id)
                                    ->where('user_id', $user->id)
                                    ->first(); // Use first() to get a single order

                                if ($buy3For990) {
                                    $quantity = $buy3For990->quantity;

                                    // Calculate the total price based on the promo logic
                                    $divisibleBy3 = intdiv($quantity, 3);
                                    $basePrice = $divisibleBy3 * 990;
                                    $additionalItems = $quantity % 3;
                                    $additionalPrice = $additionalItems * $buy3For990->product_price;

                                    // Update the order's total price
                                    $buy3For990->total_price = $basePrice + $additionalPrice;

                                    // Save the updated order
                                    $buy3For990->save();
                                }
                                // ************************************ //

                                // ****************************//
                                // CALCULATING THE FINAL TOTAL PRICE UNPAID
                                $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'UNPAID')
                                    ->first(); // Get the first matching order

                                if ($getAllOderUnpaid) { // Check if there is a matching order
                                    $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->sum('total_price');

                                    // Update the final_total_price of the first matching order
                                    $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                                    $getAllOderUnpaid->save();
                                }
                                // ****************************//


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
                    'created_at' => Carbon::now()
                ]);

                // Calculate Shipping Fee Always
                if ($create) {
                    if ($data->delete()) {
                        // ************************************ //
                        // Buy 3 for 990 promo
                        // Fetch orders based on the provided criteria
                        $buy3For990 = OrderModel::where('id', $id)
                            ->where('user_id', $user->id)
                            ->first(); // Use first() to get a single order

                        if ($buy3For990) {
                            $quantity = $buy3For990->quantity;

                            // Calculate the total price based on the promo logic
                            $divisibleBy3 = intdiv($quantity, 3);
                            $basePrice = $divisibleBy3 * 990;
                            $additionalItems = $quantity % 3;
                            $additionalPrice = $additionalItems * $buy3For990->product_price;

                            // Update the order's total price
                            $buy3For990->total_price = $basePrice + $additionalPrice;

                            // Save the updated order
                            $buy3For990->save();
                        }
                        // ************************************ //

                        // ****************************//
                        // CALCULATING THE FINAL TOTAL PRICE UNPAID
                        $getAllOderUnpaid = OrderModel::where('user_id', $user->id)
                            ->where('status', 'UNPAID')
                            ->first(); // Get the first matching order

                        if ($getAllOderUnpaid) { // Check if there is a matching order
                            $totalPriceSumUnPaid = OrderModel::where('user_id', $user->id)
                                ->where('status', 'UNPAID')
                                ->sum('total_price');

                            // Update the final_total_price of the first matching order
                            $getAllOderUnpaid->final_total_price = $totalPriceSumUnPaid;
                            $getAllOderUnpaid->save();
                        }
                        // ****************************//

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
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Intruder',
                ], Response::HTTP_OK);
            }

            $request->validate([
                'payment' => 'required|string',
            ]);

            // Check if a voucher is selected
            if ($request->input('selectedVoucherDiscount') >= 1 && $request->input('selectedVoucherShipping') >= 1) {
                // Fetch the DISCOUNT Voucher
                $voucherDiscount = VouchersModel::where('user_id', $user->id)
                    ->where('status', 'CLAIMED')
                    ->where('id', $request->input('selectedVoucherDiscount'))
                    ->first();

                // Fetch the SHIIPING Voucher
                $voucherShipping = VouchersModel::where('user_id', $user->id)
                    ->where('status', 'CLAIMED')
                    ->where('id', $request->input('selectedVoucherShipping'))
                    ->first();

                if ($voucherDiscount && $voucherShipping) {
                    // Get the first matching order
                    $getTheMain = OrderModel::where('user_id', $user->id)
                        ->where('status', 'UNPAID')
                        ->where('role', 'MAIN')
                        ->first();

                    if ($getTheMain) {
                        // Update the order with voucher information for DISCOUNT ONLY
                        $getTheMain->voucher_discount_id = $voucherDiscount->id;
                        $getTheMain->voucher_name_discount = $voucherDiscount->name;
                        $getTheMain->voucher_discount = $voucherDiscount->discount;

                        // Update the order with voucher information for FREE SHIPPING ONLY
                        $getTheMain->voucher_shipping_id = $voucherShipping->id;
                        $getTheMain->voucher_name_shipping = $voucherShipping->name;

                        if ($getTheMain->save()) {
                            // ************************************ //
                            // Discount Calculate using voucher
                            // Calculate the total price with voucher discount
                            $totalPriceSum = OrderModel::where('user_id', $user->id)
                                ->where('status', 'UNPAID')
                                ->sum('total_price');

                            $discountAmount = ($voucherDiscount->discount / 100) * $totalPriceSum;
                            $finalTotalPrice = $totalPriceSum - $discountAmount;

                            // Update the final_total_price of the first matching order
                            $getTheMain->final_total_price = $finalTotalPrice;
                            // ************************************ //

                            // ************************************ //
                            // Shipping Calculate using voucher
                            // Make it 0 the shipping fee
                            $getTheMain->shipping_fee = 0.00;
                            // ************************************ //

                            if ($getTheMain->save()) {
                                // Mark the voucher DISCOUNT as used
                                $voucherDiscount->used_at = Carbon::now();
                                $voucherDiscount->status = 'USED';

                                // Mark the voucher SHIPPING as used
                                $voucherShipping->used_at = Carbon::now();
                                $voucherShipping->status = 'USED';

                                if ($voucherDiscount->save() && $voucherShipping->save()) {
                                    // Retrieve unpaid products for the user
                                    $data = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->get();

                                    // Update product status and payment method
                                    foreach ($data as $order) {
                                        $order->status = "TO SHIP / TO PROCESS";
                                        $order->payment_method = $request->input('payment');
                                        $order->check_out_at = Carbon::now();
                                        $order->save();
                                    }

                                    // Create a log for the checkout
                                    $userAction = 'CHECK OUT';
                                    $details = 'Checked out products with Order ID: ' . $data->pluck('order_id')->implode(', ');

                                    $create = LogsModel::create([
                                        'user_id' => $user->id,
                                        'ip_address' => $request->ip(),
                                        'user_action' => $userAction,
                                        'details' => $details,
                                        'created_at' => Carbon::now()
                                    ]);

                                    // Check if the log was created successfully
                                    if ($create) {
                                        return response()->json([
                                            'message' => 'Checkout',
                                        ], Response::HTTP_OK);
                                    }
                                }
                            }
                        }
                    }
                }
            } else if ($request->input('selectedVoucherDiscount') >= 1) {
                // Fetch the discount Voucher
                $voucherDiscount = VouchersModel::where('user_id', $user->id)
                    ->where('status', 'CLAIMED')
                    ->where('id', $request->input('selectedVoucherDiscount'))
                    ->first();

                if ($voucherDiscount) {
                    // Get the first matching order
                    $getTheMain = OrderModel::where('user_id', $user->id)
                        ->where('status', 'UNPAID')
                        ->where('role', 'MAIN')
                        ->first();

                    if ($getTheMain) {
                        // Update the order with voucher information for DISCOUNT ONLY
                        $getTheMain->voucher_discount_id = $voucherDiscount->id;
                        $getTheMain->voucher_name_discount = $voucherDiscount->name;
                        $getTheMain->voucher_discount = $voucherDiscount->discount;

                        if ($getTheMain->save()) {
                            // Calculate the total price with voucher discount
                            $totalPriceSum = OrderModel::where('user_id', $user->id)
                                ->where('status', 'UNPAID')
                                ->sum('total_price');

                            $discountAmount = ($voucherDiscount->discount / 100) * $totalPriceSum;
                            $finalTotalPrice = $totalPriceSum - $discountAmount;

                            // Update the final_total_price of the first matching order
                            $getTheMain->final_total_price = $finalTotalPrice;

                            if ($getTheMain->save()) {
                                // Mark the voucher as used
                                $voucherDiscount->used_at = Carbon::now();
                                $voucherDiscount->status = 'USED';

                                if ($voucherDiscount->save()) {
                                    // Retrieve unpaid products for the user
                                    $data = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->get();

                                    // Update product status and payment method
                                    foreach ($data as $order) {
                                        $order->status = "TO SHIP / TO PROCESS";
                                        $order->payment_method = $request->input('payment');
                                        $order->check_out_at = Carbon::now();
                                        $order->save();
                                    }

                                    // Create a log for the checkout
                                    $userAction = 'CHECK OUT';
                                    $details = 'Checked out products with Order ID: ' . $data->pluck('order_id')->implode(', ');

                                    $create = LogsModel::create([
                                        'user_id' => $user->id,
                                        'ip_address' => $request->ip(),
                                        'user_action' => $userAction,
                                        'details' => $details,
                                        'created_at' => Carbon::now()
                                    ]);

                                    // Check if the log was created successfully
                                    if ($create) {
                                        return response()->json([
                                            'message' => 'Checkout',
                                        ], Response::HTTP_OK);
                                    }
                                }
                            }
                        }
                    }
                }
            } else if ($request->input('selectedVoucherShipping') >= 1) {
                // Fetch the SHIIPING Voucher
                $voucherShipping = VouchersModel::where('user_id', $user->id)
                    ->where('status', 'CLAIMED')
                    ->where('id', $request->input('selectedVoucherShipping'))
                    ->first();

                if ($voucherShipping) {
                    // Get the first matching order
                    $getTheMain = OrderModel::where('user_id', $user->id)
                        ->where('status', 'UNPAID')
                        ->where('role', 'MAIN')
                        ->first();

                    if ($getTheMain) {
                        // Update the order with voucher information for FREE SHIPPING ONLY
                        $getTheMain->voucher_shipping_id = $voucherShipping->id;
                        $getTheMain->voucher_name_shipping = $voucherShipping->name;

                        if ($getTheMain->save()) {
                            // ************************************ //
                            // Shipping Calculate using voucher
                            // Make it 0 the shipping fee
                            $getTheMain->shipping_fee = 0.00;
                            // ************************************ //

                            if ($getTheMain->save()) {
                                // Mark the voucher SHIPPING as used
                                $voucherShipping->used_at = Carbon::now();
                                $voucherShipping->status = 'USED';

                                if ($voucherShipping->save()) {
                                    // Retrieve unpaid products for the user
                                    $data = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'UNPAID')
                                        ->get();

                                    // Update product status and payment method
                                    foreach ($data as $order) {
                                        $order->status = "TO SHIP / TO PROCESS";
                                        $order->payment_method = $request->input('payment');
                                        $order->check_out_at = Carbon::now();
                                        $order->save();
                                    }

                                    // Create a log for the checkout
                                    $userAction = 'CHECK OUT';
                                    $details = 'Checked out products with Order ID: ' . $data->pluck('order_id')->implode(', ');

                                    $create = LogsModel::create([
                                        'user_id' => $user->id,
                                        'ip_address' => $request->ip(),
                                        'user_action' => $userAction,
                                        'details' => $details,
                                        'created_at' => Carbon::now()
                                    ]);

                                    // Check if the log was created successfully
                                    if ($create) {
                                        return response()->json([
                                            'message' => 'Checkout',
                                        ], Response::HTTP_OK);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // Retrieving unpaid products for the user
                $data = OrderModel::where('user_id', $user->id)->where('status', 'UNPAID')->get();

                // Updating product status and payment method
                foreach ($data as $order) {
                    $order->status = "TO SHIP / TO PROCESS";
                    $order->payment_method = $request->input('payment');
                    $order->check_out_at = Carbon::now();
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
                    'created_at' => Carbon::now()
                ]);

                // Checking if the log was created successfully
                if ($create) {
                    return response()->json([
                        'message' => 'Checkout',
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

    // DISPLAY CANCEL ON TO SHIP | CLIENT
    public function getCancelItemOnCart(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
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
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $request->validate([
                    'reasonCancel' => 'required|string',
                ]);

                // Retrieve the order to cancel
                $data = OrderModel::where('user_id', $user->id)
                    ->where('id', $id)
                    ->where('status', 'TO SHIP / TO PROCESS')
                    ->first();

                if ($data) { // Check if the order was found
                    if ($data->role == 'MAIN') {
                        // Cancel if the role is MAIN and one only on group_od
                        $count = OrderModel::where('group_id', $data->group_id)->where('status', 'TO SHIP / TO PROCESS')->count();
                        if ($count == 1) {
                            // Cancelled imidate if role is not MAIN
                            $data->status = 'CANCELLED';
                            $data->cancel_at = Carbon::now();
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
                                    'Final Total Price: ' . $data->final_total_price . "\n" .
                                    'Reason to Cancel' . $request->input('reasonCancel') . "\n";

                                // Create Log
                                $create = LogsModel::create([
                                    'user_id' => $user->id,
                                    'ip_address' => $request->ip(),
                                    'user_action' => $userAction,
                                    'details' => $details,
                                    'created_at' => Carbon::now()
                                ]);

                                if ($create) {
                                    // Calculate Status Cancelled
                                    $cancelNowThegroupId = OrderModel::where('group_id', $data->group_id)->where('status', 'CANCELLED');
                                    if ($cancelNowThegroupId) {
                                        $toProcessDataCancel = OrderModel::where('user_id', $user->id)
                                            ->where('status', 'CANCELLED')
                                            ->where('role', 'MAIN')
                                            ->where('group_id', $data->group_id)
                                            ->first();

                                        // Shipping Only && Discount Only
                                        if ($toProcessDataCancel->voucher_name_shipping !== null && $toProcessDataCancel->voucher_name_discount !== null) {
                                            $fixedDiscount = $toProcessDataCancel->voucher_discount; // Assuming $toProcessDataCancel->voucher_discount is 50
                                            $percentageDiscount = $fixedDiscount / 100; // Convert to a percentage (50% in this case)

                                            // Sum all To ship / to process
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'CANCELLED')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $discountedTotal = $totalProductPrice - ($percentageDiscount * $totalProductPrice); // Apply the percentage discount

                                            $toProcessDataCancel->shipping_fee = 0.00;
                                            $toProcessDataCancel->final_total_price = $discountedTotal;

                                            if ($toProcessDataCancel->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                        }
                                        // Shipping Only
                                        else if ($toProcessDataCancel->voucher_name_shipping !== null) {
                                            // ****************************************** //
                                            // Sum all To TO SHIP / TO PROCESS
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'CANCELLED')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $toProcessDataCancel->final_total_price = $totalProductPrice;
                                            $toProcessDataCancel->save();
                                            // ****************************************** //

                                            $toProcessDataCancel->shipping_fee = 0.00;
                                            if ($toProcessDataCancel->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                        }
                                        // Discount Only
                                        else if ($toProcessDataCancel->voucher_name_discount !== null) {
                                            $fixedDiscount = $toProcessDataCancel->voucher_discount; // Assuming $toProcessDataCancel->voucher_discount is 50
                                            $percentageDiscount = $fixedDiscount / 100; // Convert to a percentage (50% in this case)

                                            // Sum all CANCELLED
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'CANCELLED')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $discountedTotal = $totalProductPrice - ($percentageDiscount * $totalProductPrice); // Apply the percentage discount

                                            $toProcessDataCancel->final_total_price = $discountedTotal;
                                            if ($toProcessDataCancel->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                        }
                                        // Normal cancel
                                        else {
                                            // ****************************************** //
                                            // Sum all To CANCELLED
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'CANCELLED')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $toProcessDataCancel->final_total_price = $totalProductPrice;
                                            $toProcessDataCancel->save();
                                            // ****************************************** //

                                            // ****************************************** //
                                            // Updating the Total Shipping fee now
                                            // Fetch the total Quantity
                                            $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'CANCELLED')
                                                ->where('group_id', $data->group_id)
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
                                                ->where('status', 'CANCELLED')
                                                ->where('role', 'MAIN')
                                                ->where('group_id', $data->group_id)
                                                ->first();
                                            $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                            if ($updateShippingFeeNow->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                            // ****************************************** //
                                        }
                                    }
                                }
                            }
                        } else {
                            // Pass the role main, voucher_shipping_id, voucher_discount_id, voucher_name_shipping, voucher_name_discount, shipping_fee, final_total_price to the others
                            $affectedRows = OrderModel::where('group_id', $data->group_id)
                                ->where('status', 'TO SHIP / TO PROCESS')
                                ->where('id', '!=', $id)
                                ->where('user_id', $user->id)
                                ->update([
                                    'role' => 'MAIN',
                                    'voucher_shipping_id' => $data->voucher_shipping_id,
                                    'voucher_discount_id' => $data->voucher_discount_id,
                                    'voucher_name_shipping' => $data->voucher_name_shipping,
                                    'voucher_name_discount' => $data->voucher_name_discount,
                                    'voucher_discount' => $data->voucher_discount,
                                    'shipping_fee' => $data->shipping_fee,
                                    'final_total_price' => $data->final_total_price,
                                ]);

                            if ($affectedRows) {
                                // Set null then change status to CANCELLED
                                $affectedRowsSelf = OrderModel::where('group_id', $data->group_id)
                                    ->where('id', '=', $id)
                                    ->where('user_id', $user->id)
                                    ->where('status', 'TO SHIP / TO PROCESS')
                                    ->update([
                                        'role' => '',
                                        'voucher_shipping_id' => null,
                                        'voucher_discount_id' => null,
                                        'voucher_name_shipping' => null,
                                        'voucher_name_discount' => null,
                                        'voucher_discount' => null,
                                        'shipping_fee' => 0.00,
                                        'status' => 'CANCELLED',
                                        'final_total_price' => 0.00,
                                        'reason_cancel' => $request->input('reasonCancel'),
                                        'cancel_at' => Carbon::now()
                                    ]);

                                if ($affectedRowsSelf) {
                                    $userAction = 'CANCELLED PRODUCT';
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
                                        'Total Price: ' . $data->total_price . "\n" .
                                        'Reason to Cancel' . $request->input('reasonCancel') . "\n";

                                    // Create Log
                                    $create = LogsModel::create([
                                        'user_id' => $user->id,
                                        'ip_address' => $request->ip(),
                                        'user_action' => $userAction,
                                        'details' => $details,
                                        'created_at' => Carbon::now()
                                    ]);

                                    if ($create) {
                                        $toProcessDataCancel = OrderModel::where('user_id', $user->id)
                                            ->where('status', 'TO SHIP / TO PROCESS')
                                            ->where('role', 'MAIN')
                                            ->where('group_id', $data->group_id)
                                            ->first();

                                        // Shipping Only && Discount Only
                                        if ($toProcessDataCancel->voucher_name_shipping !== null && $toProcessDataCancel->voucher_name_discount !== null) {
                                            $fixedDiscount = $toProcessDataCancel->voucher_discount; // Assuming $toProcessDataCancel->voucher_discount is 50
                                            $percentageDiscount = $fixedDiscount / 100; // Convert to a percentage (50% in this case)

                                            // Sum all To ship / to process
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'TO SHIP / TO PROCESS')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $discountedTotal = $totalProductPrice - ($percentageDiscount * $totalProductPrice); // Apply the percentage discount

                                            $toProcessDataCancel->shipping_fee = 0.00;
                                            $toProcessDataCancel->final_total_price = $discountedTotal;

                                            if ($toProcessDataCancel->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                        }
                                        // Shipping Only
                                        else if ($toProcessDataCancel->voucher_name_shipping !== null) {
                                            // ****************************************** //
                                            // Sum all To TO SHIP / TO PROCESS
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'TO SHIP / TO PROCESS')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $toProcessDataCancel->final_total_price = $totalProductPrice;
                                            $toProcessDataCancel->save();
                                            // ****************************************** //

                                            $toProcessDataCancel->shipping_fee = 0.00;
                                            if ($toProcessDataCancel->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                        }
                                        // Discount Only
                                        else if ($toProcessDataCancel->voucher_name_discount !== null) {
                                            $fixedDiscount = $toProcessDataCancel->voucher_discount; // Assuming $toProcessDataCancel->voucher_discount is 50
                                            $percentageDiscount = $fixedDiscount / 100; // Convert to a percentage (50% in this case)

                                            // Sum all To ship / to process
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'TO SHIP / TO PROCESS')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $discountedTotal = $totalProductPrice - ($percentageDiscount * $totalProductPrice); // Apply the percentage discount

                                            $toProcessDataCancel->final_total_price = $discountedTotal;
                                            if ($toProcessDataCancel->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                        }
                                        // Normal cancel
                                        else {
                                            // ****************************************** //
                                            // Sum all To TO SHIP / TO PROCESS
                                            $totalProductPrice = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'TO SHIP / TO PROCESS')
                                                ->where('group_id', $data->group_id)
                                                ->sum('product_price');

                                            $toProcessDataCancel->final_total_price = $totalProductPrice;
                                            $toProcessDataCancel->save();
                                            // ****************************************** //

                                            // ****************************************** //
                                            // Updating the Total Shipping fee now
                                            // Fetch the total Quantity
                                            $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                                ->where('status', 'TO SHIP / TO PROCESS')
                                                ->where('group_id', $data->group_id)
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
                                                ->where('group_id', $data->group_id)
                                                ->first();
                                            $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                            if ($updateShippingFeeNow->save()) {
                                                return response()->json([
                                                    'message' => 'Cancelled'
                                                ], Response::HTTP_OK);
                                            }
                                            // ****************************************** //
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Cancelled imidate if role is not MAIN
                        $data->status = 'CANCELLED';
                        $data->cancel_at = Carbon::now();
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
                                'Total Price: ' . $data->total_price . "\n" .
                                'Reason to Cancel' . $request->input('reasonCancel') . "\n";

                            // Create Log
                            $create = LogsModel::create([
                                'user_id' => $user->id,
                                'ip_address' => $request->ip(),
                                'user_action' => $userAction,
                                'details' => $details,
                                'created_at' => Carbon::now()
                            ]);

                            if ($create) {
                                $toProcessDataCancel = OrderModel::where('user_id', $user->id)
                                    ->where('status', 'TO SHIP / TO PROCESS')
                                    ->where('role', 'MAIN')
                                    ->where('group_id', $data->group_id)
                                    ->first();

                                // Shipping Only && Discount Only
                                if ($toProcessDataCancel->voucher_name_shipping !== null && $toProcessDataCancel->voucher_name_discount !== null) {
                                    $fixedDiscount = $toProcessDataCancel->voucher_discount; // Assuming $toProcessDataCancel->voucher_discount is 50
                                    $percentageDiscount = $fixedDiscount / 100; // Convert to a percentage (50% in this case)

                                    // Sum all To ship / to process
                                    $totalProductPrice = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'TO SHIP / TO PROCESS')
                                        ->where('group_id', $data->group_id)
                                        ->sum('product_price');

                                    $discountedTotal = $totalProductPrice - ($percentageDiscount * $totalProductPrice); // Apply the percentage discount

                                    $toProcessDataCancel->shipping_fee = 0.00;
                                    $toProcessDataCancel->final_total_price = $discountedTotal;

                                    if ($toProcessDataCancel->save()) {
                                        return response()->json([
                                            'message' => 'Cancelled'
                                        ], Response::HTTP_OK);
                                    }
                                }
                                // Shipping Only
                                else if ($toProcessDataCancel->voucher_name_shipping !== null) {
                                    // ****************************************** //
                                    // Sum all To TO SHIP / TO PROCESS
                                    $totalProductPrice = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'TO SHIP / TO PROCESS')
                                        ->where('group_id', $data->group_id)
                                        ->sum('product_price');

                                    $toProcessDataCancel->final_total_price = $totalProductPrice;
                                    $toProcessDataCancel->save();
                                    // ****************************************** //

                                    $toProcessDataCancel->shipping_fee = 0.00;
                                    if ($toProcessDataCancel->save()) {
                                        return response()->json([
                                            'message' => 'Cancelled'
                                        ], Response::HTTP_OK);
                                    }
                                }
                                // Discount Only
                                else if ($toProcessDataCancel->voucher_name_discount !== null) {
                                    $fixedDiscount = $toProcessDataCancel->voucher_discount; // Assuming $toProcessDataCancel->voucher_discount is 50
                                    $percentageDiscount = $fixedDiscount / 100; // Convert to a percentage (50% in this case)

                                    // Sum all To ship / to process
                                    $totalProductPrice = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'TO SHIP / TO PROCESS')
                                        ->where('group_id', $data->group_id)
                                        ->sum('product_price');

                                    $discountedTotal = $totalProductPrice - ($percentageDiscount * $totalProductPrice); // Apply the percentage discount

                                    $toProcessDataCancel->final_total_price = $discountedTotal;
                                    if ($toProcessDataCancel->save()) {
                                        return response()->json([
                                            'message' => 'Cancelled'
                                        ], Response::HTTP_OK);
                                    }
                                } else {
                                    // ****************************************** //
                                    // Sum all To TO SHIP / TO PROCESS
                                    $totalProductPrice = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'TO SHIP / TO PROCESS')
                                        ->where('group_id', $data->group_id)
                                        ->sum('product_price');

                                    $toProcessDataCancel->final_total_price = $totalProductPrice;
                                    $toProcessDataCancel->save();
                                    // ****************************************** //

                                    // ****************************************** //
                                    // Updating the Total Shipping fee now
                                    // Fetch the total Quantity
                                    $fetchAllQuantityAndCalculateShippingFee = OrderModel::where('user_id', $user->id)
                                        ->where('status', 'TO SHIP / TO PROCESS')
                                        ->where('group_id', $data->group_id)
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
                                        ->where('group_id', $data->group_id)
                                        ->first();
                                    $updateShippingFeeNow->shipping_fee = calculateShippingFee($totalQuantity);
                                    if ($updateShippingFeeNow->save()) {
                                        return response()->json([
                                            'message' => 'Cancelled'
                                        ], Response::HTTP_OK);
                                    }
                                    // ****************************************** //
                                }
                            }
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
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

    // RETURN ON TO RECEIVED | CLIENT
    public function return(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
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
                        'image1' => 'required|image|mimes:jpeg,png,jpg|max:10000',
                        'image2' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
                        'image3' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
                        'image4' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
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
                        'created_at' => Carbon::now(),
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
                        $data->return_at = Carbon::now();

                        if ($data->save()) {
                            return response()->json([
                                'message' => 'Created',
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
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

    //  COMPLETED | CLIENT
    public function completedClient(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                // Fetch orders with 'TO SHIP / TO PROCESS' status for a given group ID
                $orderNow = OrderModel::where('id', $id)
                    ->where('status', 'SHIPPING')
                    ->first();

                // Check if an order was found
                if ($orderNow) {
                    // Extract necessary information from the order
                    $productGroupId = $orderNow->product_group_id;
                    $category = $orderNow->category;
                    $name = $orderNow->name;
                    $size = $orderNow->size;
                    $color = $orderNow->color;
                    $quantity = $orderNow->quantity;
                    $promoBuyAndTakeCount = $orderNow->promo_buy_and_take_count;

                    // Find the corresponding product using the extracted information
                    $product = ProductModel::where('group_id', $productGroupId)
                        ->where('name', $name)
                        ->where('category', $category)
                        ->where('color', $color)
                        ->where('size', $size)
                        ->first();

                    // Check if the product exists
                    if ($product) {
                        // Check if there is enough quantity to update
                        if ($product->quantity >= $quantity + $promoBuyAndTakeCount) {
                            // Update the quantity and promo_buy_and_take_count on ProductModel
                            $product->quantity -= $quantity + $promoBuyAndTakeCount;
                            $product->save();
                        } else {
                            // Handle the case where there isn't enough quantity/promo_buy_and_take_count
                            // You might want to throw an exception or log a message
                            // For example: Log::error("Insufficient quantity for order: {$orderNow->id}");
                        }
                    } else {
                        // Handle the case where the product is not found
                        // You might want to throw an exception or log a message
                        // For example: Log::error("Product not found for order: {$orderNow->id}");
                    }
                } else {
                    // Handle the case where no order is found with the specified conditions
                    // For example: Log::info("No order found for ID: {$id}");
                }


                $order = OrderModel::where('id', $id)
                    ->where('status', 'SHIPPING')
                    ->where('user_id', $user->id)
                    ->first(); // Use first() to retrieve the order

                if ($order) {
                    $order->status = 'COMPLETED';
                    $order->completed_at = Carbon::now();

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
                            'created_at' => Carbon::now()
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
                    ], Response::HTTP_OK);
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

    // RETURN REFUND RETURN | CLIENT
    public function returnReturn(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                // Retrieve the order to cancel
                $data = OrderModel::where('user_id', $user->id)
                    ->where('status', 'RETURN REFUND / ACCEPT')
                    ->where('id', $id)
                    ->first();

                if ($data) {
                    $request->validate([
                        'returnReason' => 'required|string|max:255',
                        'image1' => 'required|image|mimes:jpeg,png,jpg|max:10000',
                        'image2' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
                        'image3' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
                        'image4' => 'nullable|image|mimes:jpeg,png,jpg|max:10000',
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
                        'created_at' => Carbon::now(),
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
                        $data->return_at = Carbon::now();

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

    // RETURN REFUND COMPLETED  | CLIENT
    public function returnCompletedClient(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::where('id', $id)
                    ->where('status', 'RETURN REFUND / ACCEPT')
                    ->where('user_id', $user->id)
                    ->first(); // Use first() to retrieve the order

                if ($order) {
                    $order->status = 'RETURN REFUND / COMPLETED';
                    $order->completed_at = Carbon::now();

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
                            'created_at' => Carbon::now()
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
                    ], Response::HTTP_OK);
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
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::where('id', $id)
                    ->where('status', 'TO SHIP / TO PROCESS')
                    ->first();

                if ($order) {
                    $order->status = 'TO SHIP / PROCESSED';
                    $order->mark_as_done_at = Carbon::now();

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
                            'created_at' => Carbon::now()
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
                    ], Response::HTTP_OK);
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
            $orderNow = OrderModel::where('group_id', $id)
                ->where('status', 'TO SHIP / TO PROCESS');
            if (!$orderNow) {
                return response()->json([
                    'message' => 'Sorry already cancel'
                ], Response::HTTP_OK);
            }

            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                // Fetch orders with 'TO SHIP / TO PROCESS' status for a given group ID
                $orderNow = OrderModel::where('group_id', $id)
                    ->where('status', 'TO SHIP / TO PROCESS')
                    ->get();

                // Process each order
                foreach ($orderNow as $order) {
                    // Extract necessary information from the order
                    $productGroupId = $order->product_group_id;
                    $category = $order->category;
                    $name = $order->name;
                    $size = $order->size;
                    $color = $order->color;
                    $quantity = $order->quantity;
                    $promoBuyAndTakeCount = $order->promo_buy_and_take_count;

                    // Find the corresponding product using the extracted information
                    $product = ProductModel::where('group_id', $productGroupId)
                        ->where('name', $name)
                        ->where('category', $category)
                        ->where('color', $color)
                        ->where('size', $size)
                        ->first();

                    // Check if the product exists
                    if ($product) {
                        // Check if there is enough quantity to update
                        if ($product->quantity >= $quantity + $promoBuyAndTakeCount) {
                            // Update the quantity and promo_buy_and_take_count on ProductModel
                            $product->quantity -= $quantity + $promoBuyAndTakeCount;
                            $product->save();
                        } else {
                            // Handle the case where there isn't enough quantity/promo_buy_and_take_count
                            // You might want to throw an exception or log a message
                            // For example: Log::error("Insufficient quantity for order: {$order->id}");
                        }
                    } else {
                        // Handle the case where the product is not found
                        // You might want to throw an exception or log a message
                        // For example: Log::error("Product not found for order: {$order->id}");
                    }
                }


                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'TO SHIP / TO PROCESS')
                    ->update([
                        'status' => 'TO SHIP / PROCESSED',
                        'mark_as_done_at' => Carbon::now()
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
                        'created_at' => Carbon::now()
                    ]);

                    if ($create) {
                        return response()->json([
                            'message' => 'Marked as done for all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
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

    // SHIP ALL | ADMIN
    public function shipAll(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'TO SHIP / PROCESSED')
                    ->update([
                        'status' => 'SHIPPING',
                        'ship_at' => Carbon::now()
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
                        'created_at' => Carbon::now()
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

    // COMPLETE PER ITEM | ADMIN
    public function completePerItem(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::where('id', $id)
                    ->where('status', 'SHIPPING')
                    ->first();

                if ($order) {
                    $order->status = 'COMPLETED';
                    $order->completed_at = Carbon::now();

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
                            'created_at' => Carbon::now()
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
                    ], Response::HTTP_OK);
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
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::where('id', $id)
                    ->where('status', 'SHIPPING')
                    ->first();


                if ($order) {
                    $order->status = 'FAILED';
                    $order->failed_at = Carbon::now();

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
                            'created_at' => Carbon::now()
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
                    ], Response::HTTP_OK);
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
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'SHIPPING')
                    ->update([
                        'status' => 'COMPLETED',
                        'completed_at' => Carbon::now()
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
                        'created_at' => Carbon::now()
                    ]);

                    if ($create) {
                        return response()->json([
                            'message' => 'Complete all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
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

    // Fail ALL ITEM | ADMIN
    public function failAll(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $affectedRows = OrderModel::where('group_id', $id)
                    ->where('status', 'SHIPPING')
                    ->update([
                        'status' => 'FAILED',
                        'failed_at' => Carbon::now()
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
                        'created_at' => Carbon::now()
                    ]);

                    if ($create) {
                        return response()->json([
                            'message' => 'Fail all'
                        ], Response::HTTP_OK);
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
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

    // RETURN TO ACcEPT PER ITEM | ADMIN
    public function returnAccept(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::find($id);

                if ($order) {
                    $order->status = 'RETURN REFUND / ACCEPT';
                    $order->return_accept_at = Carbon::now();

                    if ($order->save()) {
                        $userAction = 'RETURN ACCEPT';
                        $details = "Return Accept this product Information with Group ID: {$order->group_id}\n" .
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
                            "Total Price: {$order->total_price}\n" .
                            "Status: {$order->status}\n" .
                            "Reason: {$order->return_reason}\n" .
                            "Description: {$order->return_description}\n" .
                            "Solution: {$order->return_solution}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => Carbon::now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Return Accept'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_OK);
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

    // RETURN TO DECLINE PER ITEM | ADMIN
    public function returnDecline(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::find($id);

                if ($order) {
                    $order->status = 'RETURN REFUND / DECLINE';
                    $order->return_decline_at = Carbon::now();

                    if ($order->save()) {
                        $userAction = 'RETURN ACCEPT';
                        $details = "Return Accept this product Information with Group ID: {$order->group_id}\n" .
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
                            "Total Price: {$order->total_price}\n" .
                            "Status: {$order->status}\n" .
                            "Reason: {$order->return_reason}\n" .
                            "Description: {$order->return_description}\n" .
                            "Solution: {$order->return_solution}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => Carbon::now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Return Decline'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_OK);
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

    public function returnComplete(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::find($id);

                if ($order) {
                    $order->status = 'RETURN REFUND / COMPLETED';
                    $order->return_completed_at = Carbon::now();
                    $order->completed_at = Carbon::now();
                    if ($order->save()) {
                        $userAction = 'RETURN ACCEPT';
                        $details = "Return Accept this product Information with Group ID: {$order->group_id}\n" .
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
                            "Total Price: {$order->total_price}\n" .
                            "Status: {$order->status}\n" .
                            "Reason: {$order->return_reason}\n" .
                            "Description: {$order->return_description}\n" .
                            "Solution: {$order->return_solution}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => Carbon::now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Return Complete'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_OK);
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

    public function returnFail(Request $request, $id)
    {
        try {
            // Fetch User ID
            $user = AuthModel::where('session_login', $request->input('session') ?? 'asd')
                ->where('status', 'VERIFIED')
                ->first();

            if ($user) {
                $order = OrderModel::find($id);

                if ($order) {
                    $order->status = 'RETURN REFUND / FAILED';
                    $order->return_failed_at = Carbon::now();
                    $order->failed_at = Carbon::now();

                    if ($order->save()) {
                        $userAction = 'RETURN FAILED';
                        $details = "Return Accept this product Information with Group ID: {$order->group_id}\n" .
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
                            "Total Price: {$order->total_price}\n" .
                            "Status: {$order->status}\n" .
                            "Reason: {$order->return_reason}\n" .
                            "Description: {$order->return_description}\n" .
                            "Solution: {$order->return_solution}\n";

                        // Create Log
                        $createLog = LogsModel::create([
                            'user_id' => $user->id,
                            'ip_address' => $request->ip(),
                            'user_action' => $userAction,
                            'details' => $details,
                            'created_at' => Carbon::now()
                        ]);

                        if ($createLog) {
                            return response()->json([
                                'message' => 'Return Fail'
                            ], Response::HTTP_OK);
                        }
                    }
                } else {
                    return response()->json([
                        'message' => 'Order not found'
                    ], Response::HTTP_OK);
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
}
