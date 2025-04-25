<?php

namespace App\Http\Controllers;

use App\Models\EVStation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvStationController extends Controller
{
    
    function findNearbyStations(Request $request)
    {
        // Your location coordinates
        $x = $request->input('latitude');
        $y = $request->input('longitude');

        // Earth's radius in kilometers
        $earthRadius = 6371;

        // Convert degrees to radians
        $lat = deg2rad($x);
        $lng = deg2rad($y);



        // Haversine formula to calculate the distance
        $stations = EVStation::selectRaw('*, 
                     ROUND(' . $earthRadius . ' * acos(
                     cos(' . $lat . ') * cos(radians(latitude)) * cos(radians(longitude) - ' . $lng . ') + 
                     sin(' . $lat . ') * sin(radians(latitude))
                     )) AS distance')
            ->having('distance', '<', 5)
            ->where('is_active','active')
            ->orderBy('distance')
            ->get();


        return response()->json($stations);
    }

    public function fetchTimeSlot(Request $request)
    {
        $station_id = $request->station_id;
        $stationData = DB::table('ev_station')->find($station_id);

        if (!$stationData) {
            return response()->json(['message' => 'Station not found.'], 404);
        }

        $slotDuration = 60; // Default duration (minutes)

        // Check for the charging type to adjust the slot duration
        switch ($stationData->charging_type) {
            case 'Level 2':
                $slotDuration = 60;
                break;
            case 'Level 3':
                $slotDuration = 20;
                break;
        }

        $operating_hours = $this->parseOperatingHours($stationData->operating_hours);

        $slots = [];

        // Loop through each time range (e.g. '8:00-11:00') in the operating hours
        foreach ($operating_hours as $time_range) {
            $start_time = Carbon::parse($time_range['start']);
            $end_time = Carbon::parse($time_range['end']);

            // Ensure the end time is always after the start time
            if ($end_time->lt($start_time)) {
                $end_time->addDay(); // If the end time is before the start time (crosses midnight), add a day.
            }

            $current_time = $start_time;

            // Loop until the current time is before the end time
            while ($current_time->lt($end_time)) {
                $slot_end_time = $current_time->copy()->addMinutes($slotDuration);

                // Check if the slot end time exceeds the operating hours end time
                if ($slot_end_time->gt($end_time)) {
                    break; // Don't create a slot if the end time exceeds the operating hours
                }

                // Generate slots for each port

                $slots[] = [
                    'start_time' => $current_time->format('H:i'),
                    'end_time' => $slot_end_time->format('H:i')
                ];


                // Move current time forward by the slot duration
                $current_time = $slot_end_time;
            }
        }

        return response()->json(['status' => true,'message' => 'Slot list fetched.', 'station' => $stationData, 'slots' => $slots], 200);
    }

    protected function parseOperatingHours($operating_hours)
    {
        // If operating hours are 24/7, return a single range for the whole week
        if ($operating_hours === '24/7') {
            return [['start' => '00:00', 'end' => '23:59']];
        }

        $ranges = explode(',', $operating_hours);
        $parsed_ranges = [];

        // Parse each range in the format "start-end"
        foreach ($ranges as $range) {
            list($start, $end) = explode('-', $range);
            $parsed_ranges[] = ['start' => $start, 'end' => $end];
        }

        return $parsed_ranges;
    }
  
  public function getAvailablePorts(Request $request)
 {
    // Define validation rules
    $rules = [
        'station_id' => 'required',
        'start_time' => 'required',
        'end_time' => 'required',
        'date' => 'required',
    ];

    // Validate the request
    $validator = Validator::make($request->all(), $rules);

    // Handle validation failures
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $station_id = $request->station_id;
    $start_time = $request->start_time;
    $end_time = $request->end_time;
    $date = $request->date;
    
    // Get station details
    $station = DB::table('ev_station')->where('id', $station_id)->first(); // Use first() to get a single record
    
    if (!$station) {
        return response()->json(['status' => false, 'error' => 'Station not found'], 404);
    }

    $total_ports = $station->number_of_ports; // Get the total number of ports

    // Get booked ports on the given date and time range
    $bookedPortsCount = DB::table('booking')
                            ->where('station_id', $station_id)
                            ->where('date', $date) // Uncomment if you want to filter by date
                            ->where('start_time',$start_time)
                            ->where('end_time',$end_time)
                            ->count('port_number'); // Count the number of booked ports

    // Calculate available ports
    $available_ports_count = $total_ports - $bookedPortsCount;

    // Generate a list of available port identifiers
    $available_ports = [];
    for ($i = 1; $i <= $total_ports; $i++) {
        $port_id = $i; // Generate port identifier (e.g., bp1, bp2, ...)
        // Check if this port is booked
        $isBooked = DB::table('booking')
                        ->where('station_id', $station_id)
                        ->where('port_number', $port_id) // Assuming port_number corresponds to the generated port_id
                        ->where('date', $date) 
                        ->where('start_time',$start_time)
                        ->where('end_time',$end_time)
                        ->where('booking_status', '=', 'booked')
                        ->exists(); // Check if this port is booked

        if (!$isBooked) {
            $available_ports[] = $port_id; // Add to available ports if not booked
        }
    }

    return response()->json([
        'status' => true, 
        'station' => $station,
        'available_ports_count' => $available_ports_count,
        'available_ports' => $available_ports,
        
    ]);
 } 
  
}
