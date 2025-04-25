<?php

namespace App\Http\Controllers;

use App\Mail\OrderUpdateEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function port_booking(Request $request)
    {
          // Check if a booking with the same details already exists
    $existingBooking = DB::table('booking')
        ->where('station_id', $request->station_id)
        ->where('port_number', $request->port_number)
        ->where('date', $request->date)
        ->where('start_time', $request->start_time)
        ->where('end_time', $request->end_time)
        ->where('booking_status','=','booked')
        ->first();

        if ($existingBooking) {
             return response()->json(['status' => false, 'message' => 'booking already exits']);
            }
      
        DB::table('booking')->insert([
            'station_id' => $request->station_id,
            'port_number' => $request->port_number,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'booking_status' => $request->booking_status,
            'booked_by' => $request->booked_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
         
        $station = DB::table('ev_station')->where('id',$request->station_id)->first();
        $user = DB::table('users')->where('id',$request->booked_by)->first();
        $orderDetail = [
          'title' => 'Order Confirmation',
           'message' => 'placed',
          'station_name' => $station->name,
          'station_location' => $station->location,
          'station_port' => $request->port_number,
          'time_slot' => "{$request->start_time}.-.{$request->end_time}", // Use double quotes for variable interpolation
          'booking_of' => $request->date,
         ];
      
        $subject = 'Order Confirmation';
        //sending mail to user
        Mail::to($user->email)->send(new OrderUpdateEmail($orderDetail,$subject));
        
        return response()->json(['status' => true,'message' => 'Booking created successfully']);
    }
  
    //api to fetch history
    public function bookings(Request $request)
    {
     // Validate the request to ensure user_id is provided
    $request->validate([
    'user_id' => 'required|integer',
    'is_upcoming' => 'required'
    ]);

    date_default_timezone_set('Asia/Kolkata');

    // Get today's date in the specified timezone
    $today = Carbon::now()->format('Y-m-d');


      
   $bookings = null;
   if($request->is_upcoming == "true") {
  
    $bookings = DB::table('booking')
        ->join('ev_station', 'booking.station_id', '=', 'ev_station.id')
        ->select('booking.*',
                 'ev_station.name as station_name',
                 'ev_station.location as station_location',
                 'ev_station.latitude as lat',
                 'ev_station.longitude as long',
                 'ev_station.price_per_kwh as price')
        ->where('booking.booked_by', $request->user_id)
        ->whereDate('booking.date', '>=', $today)
        ->where('booking.booking_status', 'booked')
        ->get();
  
   } else {
  
    $bookings = DB::table('booking')
    ->join('ev_station', 'booking.station_id', '=', 'ev_station.id')
    ->select('booking.*',
             'ev_station.name as station_name',
             'ev_station.location as station_location',
             'ev_station.latitude as lat',
             'ev_station.longitude as long',
             'ev_station.price_per_kwh as price')
    ->where('booking.booked_by', $request->user_id)
    ->where(function($query) use ($today) {
        $query->whereDate('booking.date', '<', $today)
              ->orWhere('booking.booking_status', 'cancelled');
    })
    ->get();
  }

 // Check if any bookings were found
 if ($bookings->isEmpty()) {
     return response()->json(['status' => false, 'message' => 'No bookings found for this user']);
 } else {
    return response()->json(['status' => true, 'data' => $bookings]);
 }
       
   }
  
  // api to cancel the booking
    public function cancelBooking(Request $request)
    {
     $stationId = $request->input('station_id');
     $portNumber = $request->input('port_number');
     $date = $request->input('date');
     $startTime = $request->input('start_time');
     $endTime = $request->input('end_time');
     $bookedBy = $request->input('booked_by');
     $createdAt = $request->input('created_at');

     $booking = DB::table('booking')
                       ->where('station_id', $stationId)
                       ->where('port_number', $portNumber)
                       ->where('date', $date)
                       ->where('start_time', $startTime)
                       ->where('end_time', $endTime)
                       ->where('booked_by',$bookedBy)
                       ->where('booking_status','booked')
                       ->where('created_at',$createdAt)
                       ->first();
      //dd($booking);
     if ($booking) {
         DB::table('booking')
          ->where('id', $booking->id)
          ->update([
              'booking_status' => 'cancelled', // Replace with the desired status
              
          ]);
       
        $station = DB::table('ev_station')->where('id',$stationId)->first();
        $user = DB::table('users')->where('id',$bookedBy)->first();
        $orderDetail = [
          'title' => 'Order Cancellation',
          'message' => 'cancelled',
          'station_name' => $station->name,
          'station_location' => $station->location,
          'station_port' => $request->port_number,
          'time_slot' => "{$request->start_time}.-.{$request->end_time}", // Use double quotes for variable interpolation
          'booking_of' => $request->date,
         ];
        
         $subject = 'Order Cancellation';
        //sending mail to user
        Mail::to($user->email)->send(new OrderUpdateEmail($orderDetail,$subject));
       
         return response()->json(['message' => 'Booking cancelled successfully'], 200);
     } else {
         return response()->json(['message' => 'Booking Already cancelled']);
     }
   }

}
