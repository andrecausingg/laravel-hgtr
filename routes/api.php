<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VisitorsController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\UserInfoController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AUTH
// Signup
Route::post('signup', [AuthController::class, 'signUp']);
// Resend Code
Route::post('resend-code', [AuthController::class, 'resendCode']);
// Verify Email
Route::post('verify-email', [AuthController::class, 'verifyEmail']);
// Login
Route::post('login', [AuthController::class, 'logIn']);
// Reset Password
Route::post('reset-password', [AuthController::class, 'resetPassword']);
// Update Password
Route::post('update-password', [AuthController::class, 'updatePassword']);
// Check Legit Email and Verification Key
Route::post('check-verkey-and-email', [AuthController::class, 'checkVerKeyAndEmail']);

// Client
// Update Email
Route::post('change-email', [AuthController::class, 'updateNewEmailClient']);
// Update Password
Route::post('change-password', [AuthController::class, 'updatePasswordClient']);

// VISITOR
// Store Visitor
Route::post('store-visitor', [VisitorsController::class, 'create']);

// ADMIN ACCOUNTS
// Admin
Route::get('logs', [LogsController::class, 'index']);
Route::get('visitors', [VisitorsController::class, 'index']);
// Admin Accounts
Route::get('accounts', [AccountsController::class, 'index']);
Route::post('accounts/store', [AccountsController::class, 'store']);
// Admin role And Status
Route::get('accounts/edit/role/status/{id}', [AccountsController::class, 'editRoleStatus']);
Route::post('accounts/update/role/status/{id}', [AccountsController::class, 'updateRoleStatus']);
// Admin Email
Route::get('accounts/edit/email/{id}', [AccountsController::class, 'editEmail']);
Route::post('accounts/update/email/{id}', [AccountsController::class, 'updateEmail']);
// Admin Update Password
Route::post('accounts/update/password/{id}', [AccountsController::class, 'updatePassword']);
// Admin Delete
Route::delete('accounts/delete/{id}', [AccountsController::class, 'destroy']);

// NEW USER
// User Info
// Store New User
Route::post('user-info/store', [UserInfoController::class, 'store']);
// Edit Show Specific User Info
Route::get('user-info/edit/{id}', [UserInfoController::class, 'edit']);
// Update User Info
Route::post('user-info/update/{id}', [UserInfoController::class, 'update']);
// Show User Info
Route::get('user-info/show/{id}', [UserInfoController::class, 'show']);

// PRODUCT
// index Product
Route::get('product/index', [ProductController::class, 'index']);
// Store Product
Route::post('product/store', [ProductController::class, 'store']);
// Add Product with same group Id
Route::post('product/add', [ProductController::class, 'addProduct']);
// Update Product
Route::post('product/update/{id}', [ProductController::class, 'update']);
// Edit Product All
Route::get('product/edit/{id}', [ProductController::class, 'edit']);
// Delete Product | Update the other same group id with a role MAIN
Route::delete('product/destroy/{id}', [ProductController::class, 'destroy']);
// Delete All Product 
Route::delete('product/destroy/all/{id}', [ProductController::class, 'destroyAll']);

// Client Cart
// Add to Cart
Route::post('product/add-to-cart', [OrderController::class, 'addToCart']);
// Fetch Unpaid
Route::get('product/unpaid/{id}', [OrderController::class, 'getUnpaid']);
// Edit Product Selected on Unpaid
Route::get('product/unpaid/edit/{id}', [OrderController::class, 'edit']);
// Delete Item on Cart
Route::delete('product/order/destroy/{id}', [OrderController::class, 'destroy']);
// Check Out
Route::post('product/checkout', [OrderController::class, 'checkOut']);
// Edit Product Selected on Unpaid
Route::get('product/to-ship/get-cancel-item/{id}', [OrderController::class, 'getCancelItemOnCart']);

// Cancel
Route::post('product/cancelled/{id}', [OrderController::class, 'cancelItemOnCart']);

// Admin Orders
// Display order
Route::get('product/order/index', [OrderController::class, 'index']);
// Mark as done per item
Route::post('product/order/mark-as-done-per-item/{id}', [OrderController::class, 'markAsDonePerItem']);
// Mark as done all item
Route::post('product/order/mark-as-done-all-item/{id}', [OrderController::class, 'markAsDoneAllItem']);
// Ship all Item
Route::post('product/order/ship-all/{id}', [OrderController::class, 'shipAll']);
// Complete per item
Route::post('product/order/complete-per-item/{id}', [OrderController::class, 'completePerItem']);
// Fail per item
Route::post('product/order/fail-per-item/{id}', [OrderController::class, 'failedPerItem']);
// Complete All item
Route::post('product/order/complete-all-item/{id}', [OrderController::class, 'completeAll']);
// Complete All item
Route::post('product/order/fail-all-item/{id}', [OrderController::class, 'failAll']);