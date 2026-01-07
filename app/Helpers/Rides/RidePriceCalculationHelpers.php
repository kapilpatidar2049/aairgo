<?php

namespace App\Helpers\Rides;

use Kreait\Firebase\Contract\Database;
use Sk\Geohash\Geohash;
use Carbon\Carbon;
use App\Models\Request\RequestMeta;
use Illuminate\Support\Facades\DB;
use App\Models\Request\Request;
use Illuminate\Support\Facades\Log;
use App\Base\Constants\Setting\Settings;
use App\Models\Admin\Driver;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Request\DriverRejectedRequest;
use App\Jobs\NoDriverFoundNotifyJob;
use App\Models\Admin\ZoneSurgePrice;
use App\Models\Request\RequestCancellationFee;
use App\Models\Admin\PromoUser;
use App\Helpers\Rides\CalculatAdminCommissionAndTaxHelper;

trait RidePriceCalculationHelpers
{
    use CalculatAdminCommissionAndTaxHelper;



    /**
     * Calculate Ride fare
     * pick lat,pick lng, drop lat, drop lng should be double
     * total_distance can be double
     * duration should be in integer and in mins
     * 
     */
    //
    protected function calculateBillForARide($pick_lat,$pick_lng,$drop_lat,$drop_lng,$total_distance, $duration, $zone_type, $type_prices, $coupon_detail,$timezone,$user_id,$waiting_time,$request_detail,$driver,$airport_surge_fee)
    {

        $is_round = (integer)get_settings('can_round_the_bill_values');
        
        $is_surge_applied = false;
        
        /**
         * Distance price calculation starts here
         * 
         * */

        if($request_detail && $request_detail->requestEtaDetail && !$request_detail->is_later){
            
            $eta_detail = $request_detail->requestEtaDetail;

            $base_price = $eta_detail->base_price;
            $base_distance = $eta_detail->base_distance;
            $price_per_distance = $eta_detail->price_per_distance;
            $price_per_time = $eta_detail->price_per_time;
            $is_surge_applied = $request_detail->is_surge_applied;

            goto endCalculation;
        }
        // Pricing Parameters
        $price_per_distance = $type_prices->price_per_distance;


        if(request()->has('shared_ride') || ($request_detail && $request_detail->shared_ride)){
            $price_per_distance = $zone_type->shared_price_per_distance;

        }

        if(request()->has('rental_pack_id') || ($request_detail && $request_detail->is_rental)){
            $price_per_distance = $type_prices->distance_price_per_km;

        }

        if((request()->has('is_out_station') && request()->is_out_station) || ($request_detail && $request_detail->is_out_station)){
            // Log::info("Driver True");
            $price_per_distance = $type_prices->outstation_price_per_distance;

        }
        if($driver && $driver->price_per_distance!=null)
        {
            // Log::info("Driver True");
            $price_per_distance = $driver->price_per_distance;

            // Log::info($price_per_distance);


        }

        $base_distance = (double) $type_prices->base_distance;

        if(request()->has('shared_ride') || ($request_detail && $request_detail->shared_ride)){
            $base_distance = 0;

        }

        if((request()->has('is_out_station') && request()->is_out_station) || ($request_detail && $request_detail->is_out_station)){
            
            $base_distance = (double) $type_prices->outstation_base_distance;

        }

        if(request()->has('rental_pack_id') || ($request_detail && $request_detail->is_rental)){
            $base_distance = (double) $type_prices->free_distance;

        }

        // Calculate Surge price 
        $current_time = Carbon::now()->setTimezone($timezone);

        if(request()->is_later){
            $current_time = Carbon::parse(request()->trip_start_time)->setTimezone($timezone);
        }
        if($request_detail && $request_detail->is_later){
            $current_time = Carbon::parse($request_detail->trip_start_time)->setTimezone($timezone);
        }
        $day = $current_time->dayName;
        $current_time = $current_time->toTimeString();


        // Set price surge
        $zone_surge_price = ZoneSurgePrice::where('zone_type_id',$zone_type->id)->where('day',$day)->whereTime('start_time','<=',$current_time)->whereTime('end_time','>=',$current_time)->first();

        $surge_percent = 0;

        if($zone_surge_price){
            
            $surge_percent += $zone_surge_price->value;


        }

        // Peak Zone surge
        $peak_zone = find_peak_zone(request()->pick_lat,request()->pick_lng);

        if($peak_zone){
            
            $surge_percent += $peak_zone->distance_price_percentage;

        }

        $surge_price_additional_cost = 0;

        if($surge_percent > 0){
            $is_surge_applied = true;
            $surge_price_additional_cost = ($price_per_distance * ($surge_percent / 100));

        }
        $price_per_distance += $surge_price_additional_cost;

         // Base price
         
        $base_price = $type_prices->base_price;

        if((request()->has('is_out_station') && request()->is_out_station) || ($request_detail && $request_detail->is_out_station)){
            // Log::info("Driver True");
            $base_price = $type_prices->outstation_base_price;

        }

        if(request()->has('shared_ride') || ($request_detail && $request_detail->shared_ride)){
            $base_price = $zone_type->price_per_seat;
            if($request_detail){
                $base_price *= $request_detail->seats_taken;
            }else{
                $base_price *= request()->seats_taken;
            }

        }

         // Time price
        $price_per_time = (double)$type_prices->price_per_time;
        
        if((request()->has('is_out_station') && request()->is_out_station) || ($request_detail && $request_detail->is_out_station)){
            $price_per_time = $type_prices->outstation_price_per_time;
            // Log::info("Driver True");
   
        }

        if(request()->has('shared_ride') || ($request_detail && $request_detail->shared_ride)){
            $price_per_time = 0;

        }



        if(request()->has('rental_pack_id') || ($request_detail && $request_detail->is_rental)){
            $price_per_time = (double) $type_prices->time_price_per_min;

        }

        endCalculation:

        $calculatable_distance = ($total_distance - $base_distance);

        if($calculatable_distance < 0 ){

            $calculatable_distance = 0;
        }

         $time_price = $duration * $price_per_time;

         $time_price = round($time_price,2);

        // Distance Price

        $distance_price = ($calculatable_distance * $price_per_distance);

        $distance_price = round($distance_price,2);

         // Waiting charge
        $waiting_charge = $waiting_time * ($type_prices->waiting_charge ?? 0);

        $waiting_charge = round($waiting_charge,2);

        if($airport_surge_fee > 0){
            $is_surge_applied = true;
        }

        $sub_total = $base_price + $time_price + $distance_price + $waiting_charge + $airport_surge_fee;

        $sub_total = round($sub_total,2);

        if($is_round){
            
        // Time price
         $time_price = ceil($time_price);

        // Distance Price

        $distance_price = ceil($distance_price);

         // Waiting charge
        $waiting_charge = ceil($waiting_charge);


        $sub_total = ceil($sub_total);

        }

       
        /**
         * Apply Coupon
         * 
         * */

        $discount_amount = 0;
        $coupon_applied_sub_total = 0;
        $calculated_sub_total_for_coupon = $sub_total;
        $promo_applied = false;

        if ($coupon_detail) {
        $coupon_applied_sub_total = $sub_total;

            if ($coupon_detail->minimum_trip_amount <= $sub_total) {
                $discount_amount = $sub_total * ($coupon_detail->discount_percent/100);
                if ($coupon_detail->maximum_discount_amount>0 && $discount_amount > $coupon_detail->maximum_discount_amount) {
                    if ($coupon_detail->available_balance > $coupon_detail->maximum_discount_amount || $coupon_detail->available_balance == 0) {
                        $discount_amount = $coupon_detail->maximum_discount_amount;
                    }                                                            
                    else{ 
                        $discount_amount = $coupon_detail->available_balance;
                    } 
                }

                $coupon_applied_sub_total = $sub_total - $discount_amount;

                $coupon_applied_sub_total = round($coupon_applied_sub_total,2);

                if($is_round){

                    $coupon_applied_sub_total = ceil($coupon_applied_sub_total);
                }

                $calculated_sub_total_for_coupon = $coupon_applied_sub_total;
                
                $promo_applied= true;
            }else{

            if($request_detail){
                $promoUsers =  PromoUser::where('request_id', $request_detail->id)->delete();
            }



            }
        }


        // Apply coupon ends here

        if($request_detail){

        // Calculate Ride Cancellation Fee
        $cancellation_fee = RequestCancellationFee::where('user_id',$request_detail->user_id)->where('is_paid',0)->sum('cancellation_fee');

        $cancellation_fee = round($cancellation_fee,2);

        if($is_round){
            $cancellation_fee = ceil($cancellation_fee);
        }
        $sub_total += $cancellation_fee;

        if($cancellation_fee >0){

            RequestCancellationFee::where('user_id',$request_detail->user_id)->update([
                'is_paid'=>1,
                'paid_request_id'=>$request_detail->id]);

        }

         if($request_detail->is_bid_ride || $request_detail->is_trip_meter){
                
                $sub_total=$request_detail->accepted_ride_fare;
            }


        }else{
            
            $cancellation_fee = RequestCancellationFee::where('user_id',auth()->user()->id)->where('is_paid',0)->sum('cancellation_fee');

            $cancellation_fee = round($cancellation_fee,2);

            if($is_round){
                $cancellation_fee = ceil($cancellation_fee);
            }
            $sub_total += $cancellation_fee;
        }

        
        $discount_admin_commision_and_tax = $this->calculateAdminCommissionAndTax($calculated_sub_total_for_coupon,$zone_type,$request_detail);

        $discount_tax_amount = $discount_admin_commision_and_tax['tax_amount'];

        $discount_admin_commision = $discount_admin_commision_and_tax['admin_commision'];

         $admin_commision_and_tax = $this->calculateAdminCommissionAndTax($sub_total,$zone_type,$request_detail);

        $admin_commision = $admin_commision_and_tax['admin_commision'];

        $tax_amount = $admin_commision_and_tax['tax_amount'];

        $tax_percent = $admin_commision_and_tax['tax_percent'];

        // Admin commision with tax amount
        $admin_commision_with_tax = $tax_amount + $admin_commision;

        $discounted_total_price = $coupon_applied_sub_total + $discount_tax_amount + $discount_admin_commision;

        $discounted_total_price = round($discounted_total_price,2);

        if($is_round){

            $discounted_total_price = ceil($discounted_total_price);
        }

        // Preference Calculation
        $preferences = json_decode(request()->preferences) ?? [];
        if($request_detail){
            $preferences = $request_detail->preferenceDetail()->get();
        }
        $preference_total = $this->calculateRidePreference($preferences,$zone_type,$request_detail);

        $sub_total += $preference_total;

        // Driver.0 Commissions Starts Here

        $driver_commision = $sub_total;

        Log::info([
            'driver commision' => $driver_commision,
        ]);

        $sub_total = $admin_commision_and_tax['sub_total'];

        $sub_total += $preference_total;

        if($driver && $driver->owner_id != NULL){

            $admin_commission_type_for_driver = $zone_type->admin_commission_type_for_owner ?? 0;
            $service_fee_for_driver = $zone_type->admin_commission_from_driver ?? 0;
        }else {
            $admin_commission_type_for_driver = $zone_type->admin_commission_type_from_driver ?? 0;
            $service_fee_for_driver = $zone_type->admin_commission_from_driver ?? 0;
        }

        if($admin_commission_type_for_driver==1){

        $admin_commission_from_driver = ($driver_commision * ($service_fee_for_driver / 100));

        }else{

            $admin_commission_from_driver = $service_fee_for_driver;

        }
        if($request_detail?->driverDetail?->is_subscribed && get_settings('driver_register_module') !== 'commission'){
            $admin_commission_from_driver = 0;
        }

        if(isset($cancellation_fee) && $cancellation_fee > 0){
            $admin_commission_from_driver += $cancellation_fee;
        }

        $driver_commision -= $admin_commission_from_driver;


        if($request_detail && ($request_detail->is_bid_ride|| $request_detail->is_trip_meter)){
            $driver_commision -= $admin_commision_with_tax;
        }
        // Driver Commissions Ends Here

        // Total Amount
        $total_amount = $sub_total + $admin_commision_with_tax;

        
        if ($request_detail && $request_detail->additional_charges_amount > 0){
            $total_amount += $request_detail->additional_charges_amount;
        }


        if($is_round==0){
            $total_amount = round($total_amount,2);

        }
        $admin_commision_with_tax += $admin_commission_from_driver;

        $pickup_duration = 0;
        $dropoff_duration = $duration;
        $wait_duration = 0;
        $duration = $pickup_duration + $dropoff_duration + $wait_duration;
        $price_per_distance = round($price_per_distance,2);
        if($is_round){

            $price_per_distance = ceil($price_per_distance);
        }


        if($request_detail){

            
            $result = [
                'base_price'=>$base_price,
                'base_distance'=>$base_distance ?? 0,
                'price_per_distance'=>$price_per_distance,
                'distance_price'=>$distance_price,
                'price_per_time'=>$price_per_time,
                'time_price'=>$time_price,
                'promo_discount'=>$discount_amount,
                'waiting_charge'=>$waiting_charge,
                'service_tax'=>$tax_amount,
                'service_tax_percentage'=>$tax_percent,
                'admin_commision'=>$admin_commision,
                'admin_commision_with_tax'=>$admin_commision_with_tax,
                'driver_commision'=>$driver_commision,
                'admin_commission_from_driver'=>$admin_commission_from_driver,
                'total_amount'=> $total_amount,
                'total_distance'=>$total_distance,
                'preference_price_total'=>$preference_total,
                'total_time'=>$duration,
                'airport_surge_fee'=>$airport_surge_fee,
                'is_surge_applied' => $is_surge_applied,
                'cancellation_fee'=>$cancellation_fee,
                ];

            if($discount_amount>0){

                $result['total_amount'] = $discounted_total_price;
                $result['admin_commision'] = $discount_admin_commision;
                $result['service_tax'] = $discount_tax_amount;
                $result['admin_commision_with_tax'] = ($discount_admin_commision + $discount_tax_amount + $admin_commission_from_driver);

            }

        return $result;


        }


        // return calculated params for eta
        return (object)[
                'distance' => $total_distance,
                'base_distance' => $base_distance,
                'base_price' => $base_price,
                'price_per_distance' => $price_per_distance,
                'price_per_time' => $price_per_time,
                'distance_price' => $distance_price,
                'time_price' => $time_price,
                'subtotal_price' => $sub_total,
                'calculated_distance' => number_format($calculatable_distance,2),
                'tax_percent' => $tax_percent,
                'tax_amount' => $tax_amount,
                'discount_total_tax_amount'=>$discount_tax_amount,
                'without_discount_admin_commision'=>$admin_commision,
                'discount_admin_commision'=>$discount_admin_commision,
                'preference_price_total'=>$preference_total,
                'total_price' => $total_amount,
                'discounted_total_price'=>$discounted_total_price,
                'discount_amount'=>$discount_amount,
                'pickup_duration' => $pickup_duration,
                'dropoff_duration' => $dropoff_duration,
                'wait_duration' =>$wait_duration,
                'duration' => $duration,
                'is_surge_applied' => $is_surge_applied,
                'airport_surge_fee'=>$airport_surge_fee
            ];




    }

    protected function calculateRentalRideFares($zone_type_price, $distance, $duration, $waiting_time, $coupon_detail,$request_detail,$airport_surge_fee){

        // Distance Price
        $calculatable_distance = $distance - $zone_type_price->free_distance;
        $calculatable_distance = $calculatable_distance<0?0:$calculatable_distance;

        $price_per_distance = $zone_type_price->distance_price_per_km;

        // Validate if the current time in surge timings

        $timezone = $request_detail->serviceLocationDetail->timezone;

        $current_time = Carbon::parse($request_detail->created_at)->setTimezone($timezone);

        if($request_detail && $request_detail->is_later){
            $current_time = Carbon::parse($request_detail->trip_start_time)->setTimezone($timezone);
        }
        
        $day = $current_time->dayName;
        $current_time = $current_time->toTimeString();

        $zone_surge_price = $request_detail->zoneType->zoneSurge()->where('day',$day)->whereTime('start_time','<=',$current_time)->whereTime('end_time','>=',$current_time)->first();

        if($zone_surge_price){

            $surge_percent = $zone_surge_price->value;

            $surge_price_additional_cost = ($price_per_distance * ($surge_percent / 100));

            $price_per_distance += $surge_price_additional_cost;

            $request_detail->is_surge_applied = true;

            $request_detail->save();

        }

        $distance_price = $calculatable_distance * $price_per_distance;
        // Time Price
        $ride_duration = $duration > $zone_type_price->free_min ? $duration-$zone_type_price->free_min: 0; 
        $time_price = ($ride_duration) * $zone_type_price->time_price_per_min;
        // Waiting charge
        $waiting_charge = $waiting_time * $zone_type_price->waiting_charge;
        // Base Price
        $base_price = $zone_type_price->base_price;

        // Sub Total

        if($airport_surge_fee > 0){

            $request_detail->is_surge_applied = true;

            $request_detail->save();

        }

        $sub_total = $base_price+$distance_price+$time_price+$waiting_charge + $airport_surge_fee;


        $discount_amount = 0;

         if ($coupon_detail) {
            if ($coupon_detail->minimum_trip_amount < $sub_total) {

                $discount_amount = $sub_total * ($coupon_detail->discount_percent/100);
                if ($discount_amount > $coupon_detail->maximum_discount_amount) {
                    $discount_amount = $coupon_detail->maximum_discount_amount;
                }

                $sub_total = $sub_total - $discount_amount;
            }
        }

        // get the Tax and Commission Excempt Subtotal
        $driver = auth()->user()->driver;

        $sub_total = $this->calculateRentalPrices($sub_total,$zone_type_price, $driver);



        // Get Admin Commision
        $admin_commision_type = $zone_type_price->admin_commission_type;
        $service_fee = $zone_type_price->admin_commission;
        $tax_percent = $zone_type_price->service_tax; 
        $tax_amount = ($sub_total * ($tax_percent / 100));




        if($driver && $driver->owner_id != NULL){
            $admin_commission_type_for_driver = $zone_type_price->admin_commission_type_from_owner ?? 0;
            $service_fee_for_driver = $zone_type_price->admin_commission_from_owner ?? 0;
        }else {
            $admin_commission_type_for_driver = $zone_type_price->admin_commission_type_from_driver ?? 0;
            $service_fee_for_driver = $zone_type_price->admin_commission_from_driver ?? 0;
        }
        
        if($admin_commision_type==1){

        $admin_commision = ($sub_total * ($service_fee / 100));

        }else{

            $admin_commision = $service_fee;

        }
        // Preference Calculation
        $preferences = json_decode(request()->preferences) ?? [];
        if($request_detail){
            $preferences = $request_detail->preferenceDetail()->get();
        }
        $preference_total = $this->calculateRidePreference($preferences,null,$request_detail);

        $sub_total += $preference_total;


        // Admin commision with tax amount
        $admin_commision_with_tax = $tax_amount + $admin_commision;
        $driver_commision = $sub_total+$discount_amount;
        // Driver Commission
        if($coupon_detail && $coupon_detail->deduct_from==2){
            $driver_commision = $sub_total;
        }

        $admin_commission_from_driver = 0;
        if($admin_commission_type_for_driver==1){

        $admin_commission_from_driver = ($driver_commision * ($service_fee_for_driver / 100));

        }else{

            $admin_commission_from_driver = $service_fee_for_driver;

        }

        Log::info([
            'driver commision' => $admin_commission_from_driver,
            'subtotal' => $driver_commision,
        ]);

        // Total Amount
        $admin_commision_with_tax += $admin_commission_from_driver;
        $total_amount = $sub_total + $admin_commision_with_tax;      

        //  $driver_commision -= $admin_commision_with_tax;
        return $result = [
        'base_price'=>$base_price,
        'base_distance'=>$zone_type_price->free_distance,
        'price_per_distance'=>$price_per_distance ?? 0,
        'distance_price'=>$distance_price,
        'price_per_time'=>$zone_type_price->time_price_per_min,
        'time_price'=>$time_price,
        'promo_discount'=>$discount_amount,
        'waiting_charge'=>$waiting_charge,
        'service_tax'=>$tax_amount,
        'service_tax_percentage'=>$tax_percent,
        'admin_commision'=>$admin_commision,
        'admin_commision_with_tax'=>$admin_commision_with_tax,
        'admin_commission_from_driver'=>$admin_commission_from_driver,
        'driver_commision'=>$driver_commision,
        'preference_price_total'=>$preference_total,
        'total_amount'=>$total_amount,
        'total_distance'=>$distance,
        'total_time'=>$duration,
        'airport_surge_fee'=>$airport_surge_fee
        ];
    }

    protected function calculateRidePreference($ridePreferences,$zoneType,$request_detail = null) {

        if(!$ridePreferences || empty($ridePreferences)){
            return 0;
        }

        $total = 0;

        if($request_detail){
            foreach ($ridePreferences as $key => $ride_preference) {
                $total += $ride_preference->price;
            }
        }else{
            foreach ($ridePreferences as $key => $ride_preference) {
                $price = $zoneType->preference()->where('preference_id',$ride_preference)->first();
                if($price){
                    $total += $price->price;
                }
            }
        }

        return $total;

    }

    protected function calculateRentalPrices($rental_total,$package_price,$driver) {
        $subtotal = 0;
        $fixed_charges = 0;
        $percentage_charges = 100 + ($package_price->service_tax ?? 0); // base price + service tax

         // Commission from user
        if($package_price->admin_commission_type == 1){
            $percentage_charges += $package_price->admin_commission;
        }else{
            $fixed_charges += $package_price->admin_commission;
        }
         // Commission from driver/owner
        if($driver && $driver->owner_id != NULL){
            if($package_price->admin_commission_type_from_owner == 1){
                $percentage_charges += $package_price->admin_commission_from_owner;
            }else{
                $fixed_charges += $package_price->admin_commission_from_owner;
            }
        }else {
            if($package_price->admin_commission_type_from_driver == 1){
                $percentage_charges += $package_price->admin_commission_from_driver;
            }else{
                $fixed_charges += $package_price->admin_commission_from_driver;
            }
        }
        

        // Fixed commission are simply added to the subtotal to get the final total
        if($fixed_charges > 0){
            $rental_total -= $fixed_charges;
        }
        
        // Original subtotal if the commissions include percentage
        $subtotal = ($rental_total * 100 / $percentage_charges) ?? 0;

        return $subtotal;
    }
}
