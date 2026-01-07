<?php

namespace App\Helpers\Rides;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;
use App\Base\Constants\Masters\UnitType;


trait EndRequestHelper
{

    /**
     * Calculate and charge Ride fare
     * @param Request $request_detail with generated bill
     * 
     */
    //
    protected function calculateDistanceAndDuration($distance_in_unit,$request_detail)
    {

        $trip_duration = $this->calculateDurationOfTrip($request_detail->trip_start_time);

        $app_distance = round($distance_in_unit,2);

        $distance_in_unit = $app_distance;
        $duration = $trip_duration;

        if($request_detail->requestEtaDetail()->exists()){
           
            $eta_detail = $request_detail->requestEtaDetail;

            $distance_in_unit = $eta_detail->total_distance;  
            $duration = $eta_detail->total_time;  
            
            if(get_settings('enable_eta_total_update') != "1" || ($request_detail->is_rental && !$request_detail->is_without_destination)){

                $distance_in_unit = $app_distance;
                $duration = $trip_duration;
                
            }

        }


        return $distance_and_duration = ['distance'=>$distance_in_unit,'duration'=>$duration];

    
    }


    /**
     * Calculate Duration
     * @return $totald_duration number in minutes
     */
    protected function calculateDurationOfTrip($start_time)
    {

        $current_time = date('Y-m-d H:i:s');

        $start_time = Carbon::parse($start_time);
        // Log::info($start_time);
        $end_time = Carbon::parse($current_time);
        // Log::info($end_time);
        $totald_duration = $end_time->diffInMinutes($start_time);
        // Log::info($totald_duration);

        return $totald_duration;
    }


}
