<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EVStation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class OwnerController extends Controller
{
    public function addStation(Request $request)
    {
      //dd('Hari Bol');
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'charging_type' => 'required|string|max:50',
            'number_of_ports' => 'required|integer',
            'operating_hours' => 'nullable|string|max:100',
            'price_per_kwh' => 'nullable|numeric',
        ]); // Insert data directly into the ev_station 

        if ($validator->fails()) {
            $result = ["status" => false, "message" => $validator->errors()->first()];
        } else {
            $stationId = DB::table('ev_station')->insertGetId([
                'owner_id' => $request->owner_id,
                'name' => $request->name,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'charging_type' => $request->charging_type,
                'number_of_ports' => $request->number_of_ports,
                'operating_hours' => $request->operating_hours,
                'price_per_kwh' => $request->price_per_kwh,
            ]);

           // for ($i = 0; $i < $request->number_of_ports; $i++) {
           //     // Example code to be executed inside the loop
           //     DB::table('ports')->insert([
           //         'station_id' => $stationId,
           //         'port_number' => $i + 1,
           //     ]);
           // }

            $result = ['status' => true, 'message' => 'EV Station created successfully!'];
        }

        return response()->json($result);
    }
  
    public function getOrders(Request $request){
       $request->validate([
            'user_id' => 'required',
            'date' => 'required',
        ]);

        // Retrieve the date and owner_id from the request
        $date = $request->input('date');
        $ownerId = $request->input('user_id');
      
        $stations = DB::table('ev_station')->where('owner_id',$ownerId)->get();
        
       $bookingData = [];
      // Fetch bookings based on the provided date and owner_id
      foreach ($stations as $station) {
        $booking = DB::table('booking')
            ->where('station_id', $station->id)
            ->whereDate('date', $date) // Assuming you have a booking_date column
            ->where('booking_status', 'booked')
            ->first();

        // Check if a booking exists
        if ($booking) {
            $userData = DB::table('users')->where('id', $booking->booked_by)->first();

            // Check if user data exists
            if ($userData) {
                $data = [
                    'name' => $userData->name,
                    'email' => $userData->email,
                    'booking' => $booking // Renamed to avoid confusion
                ];

                $bookingData[] = $data;
            }
        }
    }

        // Return the bookings as a JSON response
        return response()->json(['status'=>true, 'message'=> 'Order fetch Successfully','data'=>$bookingData]);
    }
  
     public function getStation(Request $request){
       $validator = Validator::make($request->all(),[
         'user_id' => 'required'
       ]);
       
       if($validator->fails()){
         
         $result = ["status" => false, "message" => $validator->errors()->first()];
       
       }else{
       
         $stations = DB::table('ev_station')->where('owner_id',$request->user_id)->get();
         
         $result = ["status" => true, "message" => "Station Fetch Successfully" , "data" => $stations];
       }
       
       return response()->json($result);
       
     }

     public function UpdateStation(Request $request){
     
        $validator = Validator::make($request->all(),[
         'station_id' => 'required',
         'number_of_ports' => 'required',
         'operating_hr' => 'required',
         'price' => 'required',
         'Is_active' => 'required'
       ]);
       
        if($validator->fails()){
         
         return response()->json([
            "status" => false,
            "message" => $validator->errors()->first()
        ]);
       
       }else{
       
         $station = DB::table('ev_station')->where('id', $request->station_id)->update([
        'number_of_ports' => $request->number_of_ports,
        'operating_hours' => $request->operating_hr,
        'price_per_kwh' => $request->price,
        'is_active' => $request->Is_active // Corrected the semicolon to a comma
          ]);
         
         if ($station) {
             return response()->json([
                    "status" => true,
                    "message" => "Station Updated Successfully"
               ]);
       } else {
        return response()->json([
            "status" => false,
            "message" => "Failed to update the station. Please try again."
        ]);
       }
          
          
        }
   
     }
}
