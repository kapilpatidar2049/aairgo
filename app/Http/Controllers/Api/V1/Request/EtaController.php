<?php

namespace App\Http\Controllers\Api\V1\Request;

use App\Events\Event;
use App\AccountApproved;
use App\AccountActivated;
use App\Models\Admin\Promo;
use App\Models\Admin\PromoUser;
use Carbon\Carbon;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Database;
use App\Http\Requests\User\EtaRequest;
use App\Http\Controllers\ApiController;
use App\Transformers\User\EtaTransformer;
use App\Transformers\User\UserTransformer;
use App\Jobs\Notifications\OtpNotification;
use App\Jobs\Notifications\PushNotification;
use App\Jobs\Notifications\AndroidPushNotification;
use Illuminate\Http\Request;
use App\Jobs\Notifications\FcmPushNotification;
use App\Transformers\Requests\TripRequestTransformer;
use App\Base\Constants\Masters\PushEnums;
use App\Models\Request\Request as RequestModel;
use App\Transformers\Requests\PackagesTransformer;
use App\Models\Master\PackageType;
use App\Base\Constants\Auth\Role;
use App\Models\Admin\ZoneTypePackagePrice;
use App\Models\Admin\Category;
use App\Transformers\Driver\CategoryTransformer;
use Illuminate\Support\Facades\Log;
use App\Models\Request\RecentSearch;
use App\Transformers\Requests\RecentSearchesTransformer;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use App\Models\Admin\Zone;
use App\Jobs\Notifications\SendPushNotification;
use App\Helpers\Rides\StoreEtaDetailForRideHelper;

/**
 * @group User-trips-apis
 *
 * @authenticated
 * APIs for User-trips apis
 */
class EtaController extends ApiController
{
    use StoreEtaDetailForRideHelper;

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
    * Calculate an Eta
    * @bodyParam pick_lat double required pikup lat of the user
    * @bodyParam pick_lng double required pikup lng of the user
    * @bodyParam drop_lat double required drop lat of the user
    * @bodyParam drop_lng double required drop lng of the user
    * @bodyParam vehicle_type string required id of zone_type_id
    * @bodyParam ride_type tinyInteger required type of ride whther ride now or scheduele trip
    * @bodyParam promo_code string optional promo code that the user provided
    * @responseFile responses/user/trips/eta.json
    */
    public function eta(EtaRequest $request)
    {
        $category_result = [];
// Log::info("Eta Data");
// Log::info($request->all());
        
        if(auth()->user()->hasRole('user')){
            // To Store Recent Searches
            if($request->has('drop_lat'))
            {
                Log::info('eta Distance');
                Log::info($request->distance);
                if($request->distance) {
                    $this->storeRecentSearches($request);
                }

            }

        }


        // Validate if the ride is airport ride
        // If the ride is airport ride then query only the airport supported vehicle types

        $zone_detail = find_zone($request->input('pick_lat'), $request->input('pick_lng'));

        if (!$zone_detail) {
            $this->throwCustomException('service not available with this location');
        }


        $type = $zone_detail->zoneType()->whereHas('vehicleType',function($vehiclequery)use($request){
            $vehiclequery->active();
        })->where(function($query)use($request){
            $query->where('transport_type',$request->transport_type)->orWhere('transport_type','both');
        })->active();


        //if dispatcher fetch
        if ($request->has('is_dispatch') || !$request->has('drop_lat')) {
            if($request->has('driver_vehicle_type')){
                $type = $type->wherein('type_id',$request->driver_vehicle_type);
            }
        }
        
        if($request->has('drop_lat') && get_settings('enable_fixed_fare') == 1){
            $drop_zone = $this->validateDropZone($zone_detail,$request);
            if(count($drop_zone)){
                $type->whereIn('id',$drop_zone);
            }else{
                $type->whereNull('drop_zone');
            }
        }else{
            $type->whereNull('drop_zone');
        }
        if($request->has('is_out_station')){
            $type = $type->where('support_outstation',true);
        }else{
            if(request()->drop_lat){
                if($request->has('shared_ride')){
                    $type = $type->where('enable_shared_ride',true);
                    if($request->has('seats_taken')){
                        $type = $type->whereHas('vehicleType',function($vehiclequery)use($request){
                            $vehiclequery->where('capacity','>=',$request->seats_taken);
                        });
                    }
                }
            }
        }

        $airport_surge = find_airport(request()->pick_lat,request()->pick_lng);
        if($airport_surge==null && request()->drop_lat)
        {
            $airport_surge = find_airport(request()->drop_lat,request()->drop_lng);
        }

        if($airport_surge && $request->transport_type == 'taxi'){

            $type = $type->where('support_airport_fee',true)->get();
            // Normal City Rides
        }else{
            $type = $type->get();
        }
        

        if(access()->hasRole(Role::DRIVER)){

            $type_id = auth()->user()->driver->vehicle_type;
            if($type_id==null){

                $type_id = auth()->user()->driver->driverVehicleTypeDetail()->pluck('vehicle_type')->first();
            }
            $type = $zone_detail->zoneType()->whereNull('drop_zone')->where('type_id', $type_id)->first();

            if(!$type){
                $this->throwCustomException('Your Vehicle Type is not associated with this zone');
            }
        }


        if(get_settings('enable_maximum_distance_feature') == 1 && request()->has('drop_lat') && request()->has('drop_lng') && request()->drop_lat){
            $maximum_distance = 0;

            $distance_in_unit = 0;


            $dropoff_distance_in_meters = (double) request()->distance ?? 0;

            if ($dropoff_distance_in_meters) {
                $distance_in_unit = $dropoff_distance_in_meters / 1000;

                if ($zone_detail->unit==2) {
                    $distance_in_unit = kilometer_to_miles($distance_in_unit);

                }
                $distance_in_unit = round($distance_in_unit,2);
            }
                    
            
            if(request()->is_out_station){
                $maximum_distance = (double) $zone_detail->maximum_outstation_distance ?? 0;
                $message = 'outstationDistanceTooLong';
            }else{
                if(!access()->hasRole(Role::DRIVER)){
                    $maximum_distance = (double) $zone_detail->maximum_distance ?? 0;
                    $message = 'distanceTooLong';
                }
            }
            if($maximum_distance > 0 && $distance_in_unit > $maximum_distance){
                return response()->json(['message' => $message,'data' => $zone_detail->unit], 500);
            }
        }

        $eta_result = fractal($type, new EtaTransformer);

        $result =json_decode($eta_result->toJson());


        $user= auth()->user();

        $payment_gateways = [];

        $car_details = $user->userCards;

            if($car_details){

                foreach ($car_details as $key => $car_detail) {
                    
               $payment_gateways[] = [
                'is_card'=>true,
                'gateway'=>$car_detail->last_number,
                'enabled'=>true,
                'image'=>$car_detail->card_type,
                'url'=>$car_detail->card_token
               ]; 


                }
                
            }


        
        $current_date = Carbon::today()->toDateTimeString();

        $promos = Promo::where('active', true)
            ->where('from', '<=', $current_date)
            ->where('to', '>=', $current_date)
            ->where('service_location_id', $zone_detail->service_location_id)
            ->where(function($query) use ($request){
                $query->where('transport_type', $request->transport_type)
                      ->orWhere('transport_type', 'both');
            })
            ->get();

        $available_coupons = [];


        foreach ($promos as $promo) {
             $user_usage_count = PromoUser::where('promo_code_id', $promo->id)
                ->where('user_id', auth()->user()->id)
                ->count();

            $remaining_usage = $promo->uses_per_user - $user_usage_count;

            if ($remaining_usage > 0) {
                 $available_coupons[] = [
                    'id' => $promo->id,
                    'code' => $promo->code,
                    'service_location_id' => $promo->service_location_id,
                    'minimum_trip_amount' => $promo->minimum_trip_amount,
                    'maximum_discount_amount' => $promo->maximum_discount_amount,
                    'discount_percent' => $promo->discount_percent,
                    'total_uses' => $promo->total_uses,
                    'uses_per_user' => $promo->uses_per_user,
                    'from' => $promo->from,
                    'to' => $promo->to,
                    'active' => $promo->active,
                    'is_applied' => (request()->has('promo_code') && request()->promo_code == $promo->code) ? true : false,
                    'remaining_usage' => $remaining_usage
                ];
            }
        }

        return response()->json(["success" => true, "message" => [], "data" => $result->data,'saved_cards'=>$payment_gateways, 'available_coupons' => $available_coupons]);
    }


    /**
     * Store Recent Searches
     * @bodyParam pick_lat double required pikup lat of the user
     * @bodyParam pick_lng double required pikup lng of the user
     * @bodyParam drop_lat double required drop lat of the user
     * @bodyParam drop_lng double required drop lng of the user
     * @bodyParam pick_address string required pickup address of the trip request
     * @bodyParam drop_address string required drop address of the trip request
     * @bodyParam stops json required stopovers of the address
     * 
     * */
    public function storeRecentSearches($request){
        $search_place_params = [
            'user_id'=>auth()->user()->id,
            'pick_lat'=>$request->pick_lat,
            'pick_lng'=>$request->pick_lng,
            'drop_lat'=>$request->drop_lat,
            'drop_lng'=>$request->drop_lng,
            'pick_address'=>$request->pick_address,
            'drop_address'=>$request->drop_address,
            'pickup_poc_name'=>$request->pickup_poc_name,
            'pickup_poc_mobile'=>$request->pickup_poc_mobile,
            'pickup_poc_instruction'=>$request->pickup_poc_instruction,
            'drop_poc_name'=>$request->drop_poc_name,
            'drop_poc_mobile'=>$request->drop_poc_mobile,
            'drop_poc_instruction'=>$request->drop_poc_instruction,
            'total_distance'=> $request->distance??0,
            'total_time'=> $request->duration,
            'poly_line'=> $request->polyline,
            'pick_short_address'=>$request->pick_short_address,
            'drop_short_address'=>$request->drop_short_address,
            'transport_type'=>$request->transport_type,

        ];


            // Validate if the pickup or drop already exists
            $radius = 0.5; // 1 km radius for matching locations

            // Haversine formula for the pickup location
            $pickup_haversine = "(6371 * acos(cos(radians($request->pick_lat)) * cos(radians(pick_lat)) * cos(radians(pick_lng) - radians($request->pick_lng)) + sin(radians($request->pick_lat)) * sin(radians(pick_lat))))";

            // Haversine formula for the drop location
            $drop_haversine = "(6371 * acos(cos(radians($request->drop_lat)) * cos(radians(drop_lat)) * cos(radians(drop_lng) - radians($request->drop_lng)) + sin(radians($request->drop_lat)) * sin(radians(drop_lat))))";

            $exists = RecentSearch::selectRaw("{$pickup_haversine} AS pickup_distance, {$drop_haversine} AS drop_distance")
            ->whereRaw("{$pickup_haversine} < ? AND {$drop_haversine} < ?", [
            $radius, // Radius for pickup
            $radius  // Radius for drop
            ])->where('user_id',auth()->user()->id)->exists();
            
        
        if (!$exists) {
            
        // Store Searches
        $search_detail = RecentSearch::create($search_place_params);


        // To Store Search stops along with poc details
        if ($request->has('stops')) {
            // Decode the JSON string into an array

            foreach (json_decode($request->stops) as $key => $stop) {
            

                $stop_params = [
                'short_address'=>$stop->short_address,
                'address'=>$stop->address,
                'latitude'=>$stop->latitude,
                'longitude'=>$stop->longitude,
                'order'=>$stop->order
            ];
                if($request->input('transport_type')=='delivery'){
                $stop_params['poc_name'] = $stop->poc_name;
                $stop_params['poc_mobile'] = $stop->poc_mobile;
                $stop_params['poc_instruction'] = $stop->poc_instruction;
                }
               

                $search_detail->searchStops()->create($stop_params);

            }
        }

        }

        return;


    }

    /**
    * Change Drop Location on trip
    * @bodyParam request_id uuid required id request
    * @bodyParam drop_lat double required drop lat of the user
    * @bodyParam drop_lng double required drop lng of the user
    * @bodyParam drop_address string required drop address of the trip request
    * @response 
    * {
    *     "success": true,
    *     "message": "drop_changed_successfully"
    * }
    *
    */
    public function changeDropLocation(Request $request){

        $request->validate([
        'request_id' => 'required|exists:requests,id',
        'drop_lat'=>'required',
        'drop_lng'=>'required',
        'drop_address'=>'required'
        ]);

        // Get Request Detail
        $request_detail = RequestModel::where('id', $request->input('request_id'))->first();
        if($request_detail->accepted_at !== null)
        {
            $request_place_params = ['drop_lat'=>$request->drop_lat,'drop_lng'=>$request->drop_lng,'drop_address'=>$request->drop_address];

            // Update Droped place details
            $request_detail->requestPlace->update($request_place_params);

            $request_detail->fresh();
           
        
            // Get Distance and Duration
            $request_params = [
                'poly_line'=>$request->poly_line,
            ];

            
           

            // Get the Request Details zone Type
            $zone_type_detail = $request_detail->zoneType;
            $eta_result = fractal($zone_type_detail, new EtaTransformer);         
            $eta_result =json_decode($eta_result->toJson()); 

            // Add total time and distance from request_params
            $request_params['total_time'] = $eta_result->data->time;
            $request_params['total_distance'] = $eta_result->data->distance;

            $request_params['request_eta_amount'] = $eta_result->data->total;

            $request_detail->requestStops()->delete();

            $this->storeEta($request_detail , $eta_result);
    
            $request_detail->update($request_params);
            $request_detail->fresh();

            //  Get the Stops if Requested
            if ($request->has('stops')) {
                foreach (json_decode($request->stops) as $key => $stop) {
                    $request_detail->requestStops()->create([
                        'address'=>$stop->address,
                        'latitude'=>$stop->latitude,
                        'longitude'=>$stop->longitude,
                        'poc_instruction'=>$stop->poc_instruction,
                        'order'=>$stop->order
                    ]);
                }
            }
            $request_result =  fractal($request_detail, new TripRequestTransformer)->parseIncludes(['driverDetail']);
            $notifable_driver = $request_detail->driverDetail->user;

            $notification = \DB::table('notification_channels')
            ->where('topics', 'Change Drop Destination') // Match the correct topic
            ->first();
            
            // send push notification 
            if ($notification && $notification->push_notification == 1) {
                Log::info('driver notify');
                 // Determine the user's language or default to 'en'
                $userLang = $notifable_driver->lang ?? 'en';
                // dd($userLang);

                // Fetch the translation based on user language or fall back to 'en'
                $translation = \DB::table('notification_channels_translations')
                    ->where('notification_channel_id', $notification->id)
                    ->where('locale', $userLang)
                    ->first();

                // If no translation exists, fetch the default language (English)
                if (!$translation) {
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', 'en')
                        ->first();
                }
        
                
                $title =  $translation->push_title ?? $notification->push_title;
                $body = strip_tags($translation->push_body ?? $notification->push_body);
                dispatch(new SendPushNotification($notifable_driver,$title,$body));
            }

            return $this->respondSuccess($request_result,'Drop changes Sucessfully');

        }   
        else{
            return $this->respondFailed('Drop not changed');
        }    

    }

    /**
    * List Packages
    * @bodyParam pick_lat double required pikup lat of the user
    * @bodyParam pick_lng double required pikup lng of the user
    * @bodyParam transport_type string required for super and super-bidding
    * @return \Illuminate\Http\JsonResponse
    * @responseFile responses/requests/list-packages.json
    */
    public function listPackages(Request $request){

        $request->validate([
            'pick_lat'  => 'required',
            'pick_lng'  => 'required',
        ]);

        $app_for = config('app.app_for');

        if($app_for=='taxi' || $app_for=='delivery')
        {

        $type = PackageType::active();
        

        }else{
        $type = PackageType::where('transport_type',$request->transport_type)->orWhere('transport_type', 'both')->active();

        }

        if($request->has('driver_vehicle_type')){

            $vehicleTypes = (array) $request->driver_vehicle_type;
            Log::info('vehicleTypes: ' . json_encode($vehicleTypes));
            $type = $type->whereHas('zoneTypePackage.zoneType', function ($query) use ($vehicleTypes) {
                $query->wherein('type_id',$vehicleTypes);
            });
        }
                        
        $type = $type->get();  
        
        Log::info('type: ' . $type); 

        

        $user= auth()->user();

        $payment_gateways = [];

        $car_details = $user->userCards;

            if($car_details){

                foreach ($car_details as $key => $car_detail) {
                    
               $payment_gateways[] = [
                'is_card'=>true,
                'gateway'=>$car_detail->last_number,
                'enabled'=>true,
                'image'=>$car_detail->card_type,
                'url'=>$car_detail->card_token
               ]; 


                }
                
            }





        $package_result = fractal($type, new PackagesTransformer);

        // return $this->respondSuccess($package_result);

        $result =json_decode($package_result->toJson());

        return response()->json(["success" => true, "message" =>'success', "data" => $result->data,'saved_cards'=>$payment_gateways]);

        // return $this->respondSuccess($result);

    }

    /**
     * Get Directions
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @bodyParam pick_lat double required pikup lat of the user
     * @bodyParam pick_lng double required pikup lng of the user
     * @bodyParam drop_lat double required Drop lat of the user
     * @bodyParam drop_lng double required Drop lng of the user
     * @response {
     *     x"success": true,
     *     x"message": "success",
     *     x"points": "snrbAmaauM"
     * }
     * */
    public function getDirections()
    {

        return get_directions(request()->pick_lat,request()->pick_lng,request()->drop_lat,request()->drop_lng);



    }


    /**
     * List Recent Searches
     * @return \Illuminate\Http\JsonResponse
     * 
     * 
     * @response
     * {
     *     "success": true,
     *     "message": "Listed Recent Searches Successfully",
     *     "data": [
     *         {
     *             "id": 108,
     *             "user_id": 9,
     *             "pick_lat": 11.05894918,
     *             "pick_lng": 76.99666478,
     *             "drop_lat": 11.0788511,
     *             "drop_lng": 76.9399321,
     *             "pick_address": "265 Saravanampatti Siranandha Puram Tamil Nadu, India",
     *             "pick_short_address": "265 Saravanampatti Siranandha Puram Tamil Nadu",
     *             "drop_address": "Vijay Surya Residency, Kanuvai - Thudiyalur Road, Thudiyalur, Tamil Nadu, India",
     *             "drop_short_address": "Vijay Surya Residency",
     *             "pickup_poc_name": null,
     *             "pickup_poc_mobile": null,
     *             "pickup_poc_instruction": null,
     *             "drop_poc_name": null,
     *             "drop_poc_mobile": null,
     *             "drop_poc_instruction": null,
     *             "total_distance": 10282,
     *             "total_time": 25,
     *             "poly_line": "c}nbAcl}tMB}@mAm@aAc@DOPJxBfAfBx@dCnApFbCvChAnHxCtClAfDtAfAn@f@b@V^LRT`@Zp@T~@l@zCWR@r@TxAl@`Cp@|FzA`LbA|IDzAB~BVnBJjAh@`F?ZEb@?b@FXd@`BdAbD~@tBTp@`AnCNf@BZJj@l@jEv@jG@f@Gt@KlAA~@M|@Kd@SfAQ|@Cd@Hv@dAzETfA@XOz@Q~BE\\GfAI|B?d@JLFPBXCbBE~@?NBXFHN\\HXBLD^@pAD`@LrAX~@`@z@bBfD\\x@h@|D^rBl@~BDj@@tBDh@JXLL?THPTRRRBRAv@@zB@xDC~BEhBIt@WlAk@fB_@|@KHY@K@UJEN@x@D`BThCTxA@ZKbA]~ASv@E`@ChBA\\BRRtAD\\@~@?fACpAA~@ATEvA@dCG|EwG@sABi@D}@NmATi@Du@DKAGAaANkBZiDr@iB\\y@J{BPkEXkJj@_FReCRaEr@mDr@mCl@}Dv@uC`@mC^iHx@iKfAwJfAaFr@sG|@{Fd@aFX}EPuETaETwGXiETsELQ?J|AD`@f@hE",
     *             "transport_type": "taxi",
     *             "searchStops": {
     *                 "data": []
     *             }
     *         }
     *     ]
     * }
     * */
    public function recentSearches()
    {
        $user = auth()->user();

        $query = RecentSearch::where('user_id', $user->id)
            ->latest() // Orders by the 'created_at' column in descending order
            ->take(4)  // Retrieves the latest 4 records
            ->get();

        $result =  fractal($query, new RecentSearchesTransformer);

        return $this->respondSuccess($result, 'Listed Recent Searches Successfully');

    }

    public function serviceVerify(Request $request)
     {
        $request->validate([
            'address'=> 'required|array',
            'ride_type'=> 'required',
        ]);


        if(count($request->address) == 1) {

            $pick_address = $request->address[0];
            $pick_zone = find_zone($pick_address['latitude'], $pick_address['longitude']);
        

            if (!$pick_zone) {
                $this->throwCustomException('service not available with this location');
            }else{
                return $this->respondSuccess(null,'Service Available');
            }
        }else{
            $count = count($request->address);
            $pick_address = $request->address[0];
            $drop_address = $request->address[$count-1];


            $pick_zone = find_zone($pick_address['latitude'], $pick_address['longitude']);
        
            
            if($pick_zone == null) {
                $this->throwCustomException('service not available with this location');
            }

            
            $drop_location = new Point($drop_address['latitude'], $drop_address['longitude']);

            $drop_zone = Zone::contains('coordinates', $drop_location)->whereHas('serviceLocation',function($query) {
                $query->where('active',true);
            })->where('active', 1)->where('id',$pick_zone->id)->first();
    
            return $this->respondSuccess(null,'Service Available');
            
        }
    }
    public function validateDropZone($zone,$request)
    {
        $drop_zone = find_zone($request->drop_lat,$request->drop_lng);
        $zone_detail = find_zone($request->input('pick_lat'), $request->input('pick_lng'));
        $drop_zone_ids = [];
        if ($zone_detail && $zone_detail->id != $drop_zone->id){
            $drop_zone_ids = $zone->zoneType()->where('drop_zone',$drop_zone->id)->pluck('id');
        }
        return $drop_zone_ids;
    }
}
