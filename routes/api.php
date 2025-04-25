<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentication;
use App\Http\Controllers\EvStationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\OwnerController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//*************User**********
//======================Authentication====================//

Route::post('/regi_user', [Authentication::class, 'register']);
Route::post('/login_user', [Authentication::class, 'login']);
Route::post('/checkLoginToken', [Authentication::class, 'checkLoginToken']);
Route::post('/profileUpdate', [Authentication::class, 'profileUpdate']);


//======================EV_Station========================//

Route::get('/findNearbyStations', [EvStationController::class, 'findNearbyStations']);
Route::get('/fetchTimeSlot', [EvStationController::class, 'fetchTimeSlot']);
Route::get('/getAvailablePorts', [EvStationController::class, 'getAvailablePorts']);

//======================Booking===================//
Route::post('/port_booking', [BookingController::class, 'port_booking']);
Route::get('/bookings', [BookingController::class, 'bookings']);
Route::post('/cancel_booking',[BookingController::class,'cancelBooking']);

//======================Email====================//
Route::get('/send_otp', [MailController::class, 'sendOtp']);
Route::get('/verify_otp',[MailController::class,'otpVerification']);


//**************owner*********
//======================OwnerController========================//
Route::post('/add_station', [OwnerController::class, 'addStation']);
Route::get('/get_orders',[OwnerController::class,'getOrders']);
Route::get('/get_station',[OwnerController::class,'getStation']);
Route::post('/update_station',[OwnerController::class,'UpdateStation']);


Route::get('/hi', function () {
    return "hello kishan";
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
