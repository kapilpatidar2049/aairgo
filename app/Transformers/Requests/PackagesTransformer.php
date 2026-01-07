<?php

namespace App\Transformers\Requests;

use App\Models\Master\PackageType;
use App\Transformers\Transformer;
use App\Helpers\Exception\ExceptionHelpers;
use App\Models\Admin\ZoneType;
use App\Transformers\User\ZoneTypeTransformer;
use App\Models\Admin\Promo;
use Carbon\Carbon;
use App\Models\Admin\PromoUser;
use App\Base\Constants\Auth\Role;
use Log;
use App\Models\Admin\ZoneTypePackagePrice;
use League\Fractal\Resource\Collection;

class PackagesTransformer extends Transformer
{
    use ExceptionHelpers;
     /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected array $availableIncludes = [
        
    ];
    /**
     * Resources that can be included default.
     *
     * @var array
     */
    protected array $defaultIncludes = [
        
        'typesWithPrice'
    ];

    /**
     * A Fractal transformer.
     *
     * @param PackageType $package
     * @return array
     */
    public function transform(PackageType $package)
    {
        $params = [
            'id' => $package->id,
            'package_name'=>$package->name,
            'description'=>$package->description,
            'short_description'=>$package->short_description,
        ];


        $user_balance = 0;


        // userWallet
        if(!auth()->user()->hasRole(Role::DRIVER) && !auth()->user()->hasRole(Role::DISPATCHER) )
        {

        $user = auth()->user();

        $user_balance = $user->userWallet ? $user->userWallet->amount_balance : 0;

        }


        $params['user_wallet_balance'] = $user_balance;

        if(request()->input('pick_lat') && request()->input('pick_lng'))
        {
       
        $zone_detail = find_zone(request()->input('pick_lat'), request()->input('pick_lng'));


        $maxBasePrice = ZoneTypePackagePrice::whereHas('zoneType', function ($query) use ($zone_detail) {
            $query->where('zone_id', $zone_detail->id);
        })->where('package_type_id', $package->id)->max('base_price');

        // Retrieve the minimum base price for the selected zone and package type
        $minBasePrice = ZoneTypePackagePrice::whereHas('zoneType', function ($query) use ($zone_detail) {
            $query->where('zone_id', $zone_detail->id);
        })->where('package_type_id', $package->id)->min('base_price');


// Log::info("----maxBasePrice----");

// Log::info($maxBasePrice);

// Log::info("----minBasePrice----");

// Log::info($minBasePrice);


        $params['currency'] = $zone_detail->serviceLocation->currency_symbol;
        $params['currency_name'] = $zone_detail->serviceLocation->currency_code;

        $params['max_price'] = $maxBasePrice;
        $params['min_price'] = $minBasePrice;


        }


        return $params;
    }


    /**
    * Include the vehicle type along with price.
    *
    * @param User $user
    * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
    */
    public function includeTypesWithPrice(PackageType $package)
    {
        $pickLat = request()->input('pick_lat');
        $pickLng = request()->input('pick_lng');
    
        // Find the zone based on pickup location
        $zoneDetail = find_zone($pickLat, $pickLng);
    
        if (!$zoneDetail) {
            $this->throwCustomException('Service not available at this location');
        }
    
        // Fetch zone types with associated package type
        $types = ZoneType::active()->where('zone_id', $zoneDetail->id)
        ->whereHas('vehicleType',function($vehiclequery){
                        $vehiclequery->active();
                    })
            ->whereHas('zoneTypePackage', function ($query) use ($package) {
                $query->where('package_type_id', $package->id);
            })
            ->orderBy('order_number')
            ->get();

            if(request()->has('driver_vehicle_type')){

            $vehicleTypes = (array) request()->input('driver_vehicle_type');
            $types = ZoneType::active()->whereHas('vehicleType',function($vehiclequery)use($vehicleTypes){
                        $vehiclequery->whereIn('id', $vehicleTypes)->active();
                    })->get();
        }
    
        $zoneTypes = [];
        $user = auth()->user();
    
        foreach ($types as $key => $type) {
            $prices = $type->zoneTypePackage()->where('package_type_id', $package->id)->first();
    
            // Ensure prices exist
            if (!$prices) {
                continue;
            }
    
            $userBalance = $user->userWallet ? $user->userWallet->amount_balance : 0;
    
            $price_per_distance = $prices->distance_price_per_km;

            $timezone = $user->timezone ?? env('SYSTEM_DEFAULT_TIMEZONE');

            $current_time = Carbon::now()->setTimezone($timezone);
            
            $day = $current_time->dayName;
            $current_time = $current_time->toTimeString();

            $zone_surge_price = $type->zoneSurge()->where('day',$day)->whereTime('start_time','<=',$current_time)->whereTime('end_time','>=',$current_time)->first();

            if($zone_surge_price){

                $surge_percent = $zone_surge_price->value;

                $surge_price_additional_cost = ($price_per_distance * ($surge_percent / 100));

                $price_per_distance += $surge_price_additional_cost;

            }
            $zoneTypeData = [
                'zone_type_id' => $type->id,
                'type_id' => $type->type_id,
                'name' => $type->vehicle_type_name,
                'icon' => $type->icon,
                'capacity' => $type->vehicleType->capacity,
                'currency' => $type->zone->serviceLocation->currency_symbol,
                'unit' => (int) $type->zone->unit,
                'unit_in_words' => $type->zone->unit ==1 ? 'Km' : 'Miles',
                'distance_price_per_km' => $price_per_distance,
                'time_price_per_min' => $prices->time_price_per_min,
                'free_distance' => $prices->free_distance,
                'free_min' => $prices->free_min,
                'payment_type' => $type->payment_type,
                'fare_amount' => $prices->base_price,
                'description' => $type->vehicleType->description,
                'short_description' => $type->vehicleType->short_description,
                'supported_vehicles' => $type->vehicleType->supported_vehicles,
                'is_default' => $type->zone->default_vehicle_type == $type->type_id,
                'discounted_total' => 0,
                'has_discount' => false,
                'promocode_id' => null,
                'user_wallet_balance' => $userBalance,
                'preference' => [],
            ];
            
            foreach ($type->preference()->get() as $key => $preference) {
                $zoneTypeData['preference'] [] = [
                    'id' => $preference->id,
                    'preference_id' => $preference->preference_id,
                    'name' => $preference->name,
                    'icon' => $preference->preference->icon,
                    'price' => $preference->price,
                ];
            }

            $preferences = json_decode(request()->preferences) ?? [];

            $preference_price_total = $this->calculateRidePreference($preferences,$type, null) ?? 0;
            $zoneTypeData['preference_price_total'] = $preference_price_total;
            $zoneTypeData['total'] = $prices->base_price + $preference_price_total;
    
            // Apply promo code if available
            if (request()->has('promo_code') && request()->input('promo_code')) {
                $couponDetail = $this->validate_promo_code($zoneDetail->service_location_id);
    
                if ($couponDetail && $couponDetail->minimum_trip_amount < $prices->base_price) {
                    $discountAmount = $prices->base_price * ($couponDetail->discount_percent / 100);
                    if ($couponDetail->maximum_discount_amount > 0 && $discountAmount > $couponDetail->maximum_discount_amount) {
                        $discountAmount = $couponDetail->maximum_discount_amount;
                    }
    
                    $couponAppliedSubTotal = $prices->base_price - $discountAmount + $preference_price_total;
                    
                    $zoneTypeData['discounted_total'] = $couponAppliedSubTotal;
                    $zoneTypeData['has_discount'] = true;
                    $zoneTypeData['promocode_id'] = $couponDetail->id;
                }
            }
    
            $zoneTypes[] = $zoneTypeData;
        }
    
        // Return a Fractal collection
        return new Collection($zoneTypes, function (array $zoneType) {
            return $zoneType;
        });
    }
    public function validate_promo_code($service_location)
    {
        $app_for = config('app.app_for');


        $user = auth()->user();
        if (!request()->has('promo_code')) {
            return $coupon_detail = null;
        }
        $promo_code = request()->input('promo_code');
        // Validate if the promo is expired
        $current_date = Carbon::today()->toDateTimeString();

        if($app_for=='taxi' || $app_for=='delivery')
        {      
            $expired = Promo::where('code', $promo_code)->where('service_location_id',$service_location)->where('to', '>', $current_date)->first();
        }else{
            $transport_type = request()->transport_type;
            $expired = Promo::where('code', $promo_code)->where('service_location_id',$service_location)->where(function($query)use($transport_type){
            $query->where('transport_type',$transport_type)->orWhere('transport_type','both');
            })->where('to', '>', $current_date)->where('active',true)->first();
        }


        if (!$expired) {
            $this->throwCustomException('provided promo code expired or invalid');
        }
        if($expired->promo_code_users_availabe == "yes")
        {
            $validate_promo_code = PromoCodeUser::where('promo_code_id', $expired->id)->where('user_id', $user->id)->where('service_location_id', $service_location)->first();
            if(!$validate_promo_code)
            {
                $this->throwCustomException('provided promo code expired or invalid');
            }
        }

        $exceed_usage = PromoUser::where('promo_code_id', $expired->id)->where('user_id', $user->id)->count(); 
     
        if ($exceed_usage >= $expired->uses_per_user) {
            $this->throwCustomException('provided promo code expired or invalid');
        }

        return $expired;
        
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

}
