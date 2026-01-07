<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Country;
use App\Transformers\CountryTransformer;
use App\Models\User;
use App\Models\Admin\Driver;
use App\Models\Request\Request;
use App\Models\Request\RequestBill;
use Carbon\Carbon;
use App\Models\Admin\GoodsType;
use App\Models\Admin\Zone;
use App\Models\Admin\VehicleType;
use App\Models\ThirdPartySetting;
use App\Base\Filters\Admin\RequestFilter;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Transformers\Requests\TripRequestTransformer;
use App\Models\Admin\ServiceLocation;
// use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Admin\Setting;
use App\Models\Admin\InvoiceConfiguration;
use Illuminate\Support\Facades\Mail;
use App\Mail\RideLaterMail;
use Kreait\Firebase\Contract\Database;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Admin\PackageType;
use App\Models\Admin\ZoneTypePackagePrice;
use App\Jobs\Mails\SendUserRideLaterMailNotification;
use App\Http\Controllers\Api\V1\Payment\Stripe\StripeController;
use App\Models\Master\MobileAppSetting;
use App\Models\Master\Preference;
use App\Models\Admin\ZoneType;
use App\Transformers\User\EtaTransformer;
use DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\Rides\StoreEtaDetailForRideHelper;
use App\Models\Payment\WalletWithdrawalRequest;
use App\Transformers\Payment\WalletWithdrawalRequestsTransformer;
use App\Base\Filters\Admin\DriverFilter;
use App\Http\Requests\Request\DriverEndRequest;
// use Illuminate\Http\Request as HttpRequest;
use App\Models\Request\RecentSearch;
use App\Models\Master\PreferencePrices;
use App\Models\Request\RequestEnquiry;
use App\Base\Filters\Admin\RequestEnquiryFilter;
use App\Helpers\Rides\RidePriceCalculationHelpers;
use App\Helpers\Rides\PaymentOptionCalculationHelper;
use App\Helpers\Rides\EndRequestHelper;
use App\Helpers\Payment\PaymentReferenceHelper;
use App\Models\Admin\Promo;
use App\Jobs\ValidateAndUpdateIncentivesJob;
use App\Models\Admin\Incentive;
use App\Models\Payment\DriverIncentiveHistory;
use App\Models\Payment\DriverWallet;
use App\Models\Admin\DriverLevelUp;
use App\Mail\UserInvoiceMail;
use App\Mail\DriverInvoiceMail;
use App\Jobs\Mails\SendUserInvoiceMailNotification;
use App\Jobs\Mails\SendDriverInvoiceMailNotification;
use App\Jobs\ValidateAndUpdateDriverLoyaltyJob;
use App\Jobs\NotifyViaMqtt;
use App\Jobs\NotifyViaSocket;
use App\Models\Admin\PromoUser;
use App\Base\Constants\Masters\UnitType;
use App\Base\Constants\Masters\PushEnums;
use App\Base\Constants\Masters\PaymentType;
use App\Base\Constants\Masters\WalletRemarks;
use App\Http\Controllers\Api\V1\BaseController;
use App\Jobs\Notifications\AndroidPushNotification;
use App\Models\Request\RequestCancellationFee;
use App\Base\Constants\Setting\Settings;
use App\Models\Master\MailTemplate;
use App\Mail\WelcomeMail;
use App\Jobs\Mails\SendMailNotification;
use App\Jobs\Mails\SendInvoiceMailNotification;
use App\Models\Request\Request as RequestRequest;
use App\Models\Request\RequestStop; 
use App\Models\DispatcherConversation;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\DispatcherMessage;
use App\Base\Filters\Admin\UserFilter;
use Salman\Mqtt\MqttClass\Mqtt;
use App\Models\Request\RequestMeta;
use App\Base\Constants\Masters\EtaConstants;
use App\Http\Requests\Request\CreateTripRequest;
use Illuminate\Http\Request as ValidatorRequest;
use Illuminate\Support\Facades\Validator; 
use App\Helpers\Rides\FetchDriversFromFirebaseHelpers;
use App\Models\Request\DispatcherLocation;

class OwnerDispatcherController extends StripeController
{
    protected $request;

    protected $database;

    protected $user;

    use FetchDriversFromFirebaseHelpers,StoreEtaDetailForRideHelper;

    public function __construct(Database $database,Request $request,User $user)
    {
        $this->database = $database;
         $this->request = $request;
        $this->user = $user;
    }

     public function bookrides(ValidatorRequest $request) {

        $owner = null;
        
         if (auth()->user()->hasRole('owner')) {
            // Retrieve the specific owner associated with the authenticated user
            $owner = auth()->user()->owner;
        }
        $type = [];
        $trip_start_time = $request->query('date'); 

        $booking_details = $request->only([
                'pick_lat', 'pick_lng', 'drop_lat', 'drop_lng',
                'pick_address', 'drop_address', 'mobile', 'name'
            ]);

        $query = Country::active()->get();

        $countries = fractal($query, new CountryTransformer);

        $result = json_decode($countries->toJson(),true);
        
        $default_country = Country::active()->where('code',get_settings('default_country_code_for_mobile_app'))->first();

        $firebaseSettings = [
            'firebase_api_key' => get_firebase_settings('firebase_api_key'),
            'firebase_auth_domain' => get_firebase_settings('firebase_auth_domain'),
            'firebase_database_url' => get_firebase_settings('firebase_database_url'),
            'firebase_project_id' => get_firebase_settings('firebase_project_id'),
            'firebase_storage_bucket' => get_firebase_settings('firebase_storage_bucket'),
            'firebase_messaging_sender_id' => get_firebase_settings('firebase_messaging_sender_id'),
            'firebase_app_id' => get_firebase_settings('firebase_app_id'),
        ];

        $default_dial_code = $default_country->dial_code;
        $default_flag = $default_country->flag;

        // @TODO check if the rental is enabled or not
        $ride_type_for_ride = ['regular','rental'];

        $goods_types = GoodsType::active()->get();

        $transport_settings = Setting::where('category', 'trip_settings')
        ->pluck('value', 'name')
        ->toArray();

        $schedule_a_ride = (double) $transport_settings['user_can_make_a_ride_after_x_miniutes'];

        $settings = Setting::where('category', 'customization_settings')
        ->pluck('value', 'name')
        ->toArray(); 

        $preference = Preference::active()->get();

        $enabled_modules = $settings['enable_modules_for_applications'] ?? 'taxi';       
        
        $transport_type_regular = [];


        $package_taxi = ZoneTypePackagePrice::active()->whereHas('zoneType',function($query) {
            $query->where('transport_type','taxi')->orWhere('transport_type','both');
        })->exists();

        $package_delivery = ZoneTypePackagePrice::active()->whereHas('zoneType',function($query) {
            $query->where('transport_type','delivery')->orWhere('transport_type','both');
        })->exists();

        $rental_taxi = $settings['show_taxi_rental_ride_feature'];
        $rental_delivery = $settings['show_delivery_rental_ride_feature'];

        // Initialize the transport type array
        $transport_type_rental = [];

        

        $outstation_taxi = $settings['show_outstation_ride_feature'];
        $outstation_delivery = $settings['show_delivery_outstation_ride_feature'];

        $transport_type_outstation = [];

        // preference
        $pet_preference = $settings['enable_pet_preference_for_user'] == '1';
        $luggage_preference = $settings['enable_luggage_preference_for_user'] == '1';
        $map_type = get_map_settings('map_type');
        $enable_ride_without_destination = get_settings('show_ride_without_destination') == '1';
        $app_modules = MobileAppSetting::active()->get();
        $package = ZoneTypePackagePrice::active()->get();

        if($map_type=="open_street_map")
        {

            
            return Inertia::render('dispatch/open-dispatch',['countries'=>$result['data'],
            'default_dial_code'=>$default_dial_code,'default_flag'=>$default_flag,
            'default_lat'=>get_settings('default_latitude'),'default_lng'=>get_settings('default_longitude'),
            'firebaseSettings'=>$firebaseSettings,'enable_ride_without_destination'=>$enable_ride_without_destination,
            'ride_type_for_ride'=>$ride_type_for_ride,'goodsTypes'=>$goods_types,'type'=> $type,
            'transport_type_outstation' => $transport_type_outstation,'schedule_a_ride'=>$schedule_a_ride,
            'transport_type_rental' =>$transport_type_rental, 'transport_type_regular' =>$transport_type_regular,
            'is_pet_available' => $pet_preference, 'is_luggage_available' => $luggage_preference,'preference'=> $preference,'app_modules' => $app_modules,
            'package' => $package,'trip_start_time' => $trip_start_time,'booking_details' => $booking_details,'owner' =>$owner
            ]);   

        }else{
            $map_key = get_map_settings('google_map_key');

            $default_location = (object)[
                "lat"=> (float) get_settings('default_latitude'),
                "lng"=> (float) get_settings('default_longitude'),
            ];

            return Inertia::render('pages/owner_dispatcher/dispatch',['countries'=>$result['data'],
            'default_dial_code'=>$default_dial_code,'default_flag'=>$default_flag,
            'baseUrl'=>route('landing.index'),'default_location'=>$default_location,
            'default_lat'=>get_settings('default_latitude'),'default_lng'=>get_settings('default_longitude'),
            'firebaseSettings'=>$firebaseSettings,'enable_ride_without_destination'=>$enable_ride_without_destination,
            'ride_type_for_ride'=>$ride_type_for_ride,'goodsTypes'=>$goods_types,'map_key'=>$map_key,
            'transport_type_outstation' => $transport_type_outstation,'schedule_a_ride'=>$schedule_a_ride,
            'transport_type_rental' =>$transport_type_rental, 'transport_type_regular' =>$transport_type_regular,
            'is_pet_available' => $pet_preference, 'is_luggage_available' => $luggage_preference, 'preference'=> $preference,
            'app_modules' => $app_modules,'package' => $package,'trip_start_time' => $trip_start_time,
            'booking_details' => $booking_details,'type'=> $type, 'owner' => $owner
            ]);

    
         }
    }

    /**
     * Fetch User Detail
     * 
     * 
     * */
    public function fetchUserIfExists()
    {
        $mobile = request()->mobile;

        //belongsTorole('user')->
        $user = User::where('mobile',$mobile)->first();
        
        return $this->respondSuccess($user);


    }

    public function createRequests(ValidatorRequest $request)
    {

        $rules = [
            'pick_lat'  => 'required',
            'pick_lng'  => 'required',
            'vehicle_type'=>'sometimes|required|exists:zone_types,id',
            'payment_opt'=>'sometimes|required|in:0,1,2',
            'pick_address'=>'required',
            'is_later'=>'sometimes|required|in:1,0',
        ];

        Log::info($request->all());
        Log::info($request->transport_type);
        // Create a new validator instance
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // Validation failed
            $errors = $validator->errors()->all();
            return response()->json(['status'=>false,"message"=>$errors]);
            
        }         
        /**
        * Validate payment option is available.
        * if card payment choosen, then we need to check if the user has added thier card.
        * if the paymenr opt is wallet, need to check the if the wallet has enough money to make the trip request
        * Check if thge user created a trip and waiting for a driver to accept. if it is we need to cancel the exists trip and create new one
        * Find the zone using the pickup coordinates & get the nearest drivers
        * create request along with place details
        * assing driver to the trip depends the assignment method
        * send emails and sms & push notifications to the user& drivers as well.
        */
        // Validate payment option is available.
        if ($request->has('is_later') && $request->is_later && $request->is_later == '1' ) {
            return $this->createRideLater($request);
        }

        $country_data = Country::where('dial_code',$request->country)->first();

        // @TODO
        // get type id
        $zone_type_detail = ZoneType::where('id', $request->vehicle_type)->first();
        $type_id = $zone_type_detail->type_id;

        // Get currency code of Request
        $service_location = $zone_type_detail->zone->serviceLocation;
        $currency_code = $service_location->currency_code;
        $currency_symbol = $service_location->currency_symbol;
        $eta_result = fractal($zone_type_detail, new EtaTransformer);

        $eta_result =json_decode($eta_result->toJson());
        // fetch unit from zone
        $unit = $zone_type_detail->zone->unit;
        // Fetch user detail
        $user_detail = auth()->user();
        // Get last request's request_number
        $request_number = $this->request->orderBy('created_at', 'DESC')->pluck('request_number')->first();


        $current_timestamp = Carbon::now()->timestamp.rand(0, 99);

        $request_number = 'REQ_'.$current_timestamp;
        
        $request_params = [
            'request_number'=>$request_number,
            'zone_type_id'=>$request->vehicle_type,
            'if_dispatch'=>true,
            'dispatcher_id'=>$user_detail->admin->id ?? null,
            'payment_opt'=>$request->payment_opt,
            'poly_line'=>$request->poly_line,
            'unit'=>$unit,
            'transport_type'=>$request->transport_type,
            'requested_currency_code'=>$currency_code,
            'requested_currency_symbol'=>$currency_symbol,
            'service_location_id'=>$service_location->id,
            'total_distance'=>$eta_result->data->distance,
            'total_time'=>$eta_result->data->time,
        ];

        if(!$user_detail->admin)
        {
            $request_params['booked_by'] = auth()->user()->id; 

        }

        if($request->has('is_pet_available')){

            $request_params['is_pet_available'] = $request->is_pet_available;
        }

        if($request->has('is_luggage_available')){

            $request_params['is_luggage_available'] = $request->is_luggage_available;
        }

        $request_params['assign_method'] = $request->assign_method;
        $request_params['request_eta_amount'] = $eta_result->data->total;
        if($request->has('rental_package_id') && $request->rental_package_id){

            $request_params['is_rental'] = true; 

            $request_params['rental_package_id'] = $request->rental_package_id;
        }
        if($request->has('goods_type_id') && $request->goods_type_id){
            $request_params['goods_type_id'] = $request->goods_type_id; 
            $request_params['goods_type_quantity'] = $request->goods_type_quantity;
        }

        $request_params['is_parcel'] = 1;
        $request_params['paid_at'] = 'Sender';
        $request_params['parcel_type'] = 'Send Parcel';

          // store request place details
          $user = $this->user->belongsToRole('user')
                        ->where('mobile', $request->mobile)
                        ->first();
          if($user!=null)
          {
            if($user->ride_otp==null)
            {
                $user->ride_otp=rand(1111, 9999);
                $user->save();
            }   
         }
                     

        if($user && $request->name){
            $user->name = $request->name;
            $user->save();
        }
        

        if(!$request->drop_lat){
            $request_params['is_without_destination'] = true;
        }
          if(!$user)
          {
            $request_params1['name'] = $request->name;
            $request_params1['mobile'] = $request->mobile;
            $request_params1['country'] = $country_data->id;
            $request_params1['ride_otp'] = rand(1111, 9999);
                      
            $user = $this->user->create($request_params1); 
             
            $user->attachRole('user');
          }  
          $request_params['user_id'] = $user->id;
        $request_detail = $this->request->create($request_params);

        if($request->has('driver_id') && $request->driver_id != null){
            $driver = Driver::find($request->driver_id);
            if (!$driver) {
               Log::error('Driver not found for ID: '.$request->driver_id);
                return;
            }
             Log::info('Saving meta data:', [
        'driver_id' => $driver->id,
        'request_id' => $request_detail->id,
        'user_id' => $request_detail->user_id,
    ]);

                $selected_drivers["user_id"] =  $request_detail->user_id;
                $selected_drivers["driver_id"] = $request->driver_id;
                $selected_drivers["active"] = 1;
                $selected_drivers["assign_method"] = 1;
                $selected_drivers["created_at"] = date('Y-m-d H:i:s');
                $selected_drivers["updated_at"] = date('Y-m-d H:i:s');

                $request_detail->requestMeta()->create($selected_drivers);

                 $this->database->getReference('request-meta/'.$request_detail->id)
                    ->set([
                            'driver_id'=>$driver->id,
                            'request_id'=>$request_detail->id,
                            'user_id'=>$request_detail->user_id,
                            'active'=>1,
                            'transport_type'=>"taxi",
                            'updated_at'=> Database::SERVER_TIMESTAMP
                        ]);

                $request_detail->update(['assign_method'=>1, 'accepted_ride_fare'=>$request_detail->offerred_ride_fare,'is_bid_ride'=>false]);

                
        }
        if ($request->has('preferences')) {
            $this->storePreference($request_detail , json_decode($request->preferences));
        }
       

        if ($request->has('stops')) {


            foreach (json_decode($request->stops) as $key => $stop) {
                $request_detail->requestStops()->create([
                'address'=>$stop->address,
                'latitude'=>$stop->latitude,
                'longitude'=>$stop->longitude,
                'order'=>$key+1]);

            }
        }

        // request place detail params
        $request_place_params = [
            'pick_lat'=>$request->pick_lat,
            'pick_lng'=>$request->pick_lng,
            'drop_lat'=>$request->drop_lat,
            'drop_lng'=>$request->drop_lng,
            'pick_address'=>$request->pick_address,
            'drop_address'=>$request->drop_address];
      
        $request_detail->requestPlace()->create($request_place_params);

        $this->storeEta($request_detail,$eta_result);

        // Add Request detail to firebase database
         $this->database->getReference('requests/'.$request_detail->id)->update(['request_id'=>$request_detail->id,'request_number'=>$request_detail->request_number,'service_location_id'=>$service_location->id,'user_id'=>$request_detail->user_id,'trnasport_type'=>$request->trnasport_type,'pick_address'=>$request->pick_address,'drop_address'=>$request->drop_address,'assign_method'=>1,'active'=>1,'is_accept'=>0,'date'=>$request_detail->converted_created_at,'updated_at'=> Database::SERVER_TIMESTAMP]); 

        $selected_drivers = [];
        $notification_android = [];
        $notification_ios = [];
        $i = 0; 
        $request_result =  fractal($request_detail, new TripRequestTransformer)->parseIncludes('userDetail');

        $mqtt_object = new \stdClass();
        $mqtt_object->success = true;
        $mqtt_object->success_message  = PushEnums::REQUEST_CREATED;
        $mqtt_object->result = $request_result; 
        DB::commit();
        if($request->assign_method == 0)
        {
            $nearest_drivers =  $this->fetchDriversFromFirebase($request_detail,$this->database);

            // Send Request to the nearest Drivers
             if ($nearest_drivers==null) {
                    goto no_drivers_available;
             } 
            no_drivers_available:
        }

        return $this->respondSuccess($request_result, 'Request Created Successfully');
    }


   

   
    /**
    * Create Ride later trip
    */
    public function createRideLater($request)
    {
        /**
        * @TODO validate if the user has any trip with same time period
        *
        */
        // get type id
        $zone_type_detail = ZoneType::where('id', $request->vehicle_type)->first();
        $type_id = $zone_type_detail->type_id;

        // Get currency code of Request
        $service_location = $zone_type_detail->zone->serviceLocation;
        $currency_code = $service_location->currency_code;
        $currency_symbol = $service_location->currency_symbol;
        $trip_start_time = $request->trip_start_time;
        $secondcarbonDateTime = Carbon::parse($request->trip_start_time, $service_location->timezone)->setTimezone('UTC')->toDateTimeString(); 
        $now = Carbon::now($service_location->timezone)->addHour(); 

        // fetch unit from zone
        $unit = $zone_type_detail->zone->unit;
        $eta_result = fractal($zone_type_detail, new EtaTransformer);

        $eta_result =json_decode($eta_result->toJson());

         // Calculate ETA
         $request_eta_params=[
            'base_price'=>$eta_result->data->base_price,
            'base_distance'=>$eta_result->data->base_distance,
            'total_distance'=>$eta_result->data->distance,
            'total_time'=>$eta_result->data->time,
            'price_per_distance'=>$eta_result->data->price_per_distance,
            'distance_price'=>$eta_result->data->distance_price,
            'price_per_time'=>$eta_result->data->price_per_time,
            'time_price'=>$eta_result->data->time_price,
            'service_tax'=>$eta_result->data->tax_amount,
            'service_tax_percentage'=>$eta_result->data->tax,
            'promo_discount'=>$eta_result->data->discount_amount,
            'admin_commision'=>$eta_result->data->without_discount_admin_commision,
            'admin_commision_with_tax'=>($eta_result->data->without_discount_admin_commision + $eta_result->data->tax_amount),
            'total_amount'=>$eta_result->data->total,
            'requested_currency_code'=>$currency_code
        ];

        // Fetch user detail
        $user_detail = auth()->user();

        $current_timestamp = Carbon::now()->timestamp.rand(0, 99);
        $country_data = Country::where('dial_code',$request->country)->first();

        $request_number = 'REQ_'.$current_timestamp;
        
        // Convert trip start time as utc format
        $timezone = auth()->user()->timezone?:env('SYSTEM_DEFAULT_TIMEZONE');
        
        $trip_start_time = $secondcarbonDateTime; 
        $request_params = [
            'request_number'=>$request_number,
            'is_later'=>true,
            'zone_type_id'=>$request->vehicle_type,
            'trip_start_time'=>$trip_start_time,
            'if_dispatch'=>true,
            'dispatcher_id'=>$user_detail->admin->id ?? null,
            'payment_opt'=>$request->payment_opt,
            'poly_line'=>$request->poly_line,
            'unit'=>$unit,
            'requested_currency_code'=>$currency_code,
            'requested_currency_symbol'=>$currency_symbol,
            'service_location_id'=>$service_location->id,
            'transport_type'=>$request->transport_type,
            'total_distance'=>$eta_result->data->distance,
            'total_time'=>$eta_result->data->time,
        ];


        if($request->has('is_pet_available')){

            $request_params['is_pet_available'] = $request->is_pet_available;
        }

        if($request->has('is_luggage_available')){

            $request_params['is_luggage_available'] = $request->is_luggage_available;
        }
    

        if(!$request->drop_lat){
            $request_params['is_without_destination'] = true;
        }
            if($request->has('request_eta_amount') && $request->request_eta_amount){
 
                $request_params['request_eta_amount'] = round($request->request_eta_amount, 2);
     
             }    
     
             if($request->has('rental_package_id') && $request->rental_package_id){
     
                 $request_params['is_rental'] = true; 
     
                 $request_params['rental_package_id'] = $request->rental_package_id;
             }
             if($request->has('goods_type_id') && $request->goods_type_id){
                 $request_params['goods_type_id'] = $request->goods_type_id; 
                 $request_params['goods_type_quantity'] = $request->goods_type_quantity;
             }

        
            $request_params['is_parcel'] = 1;
            $request_params['paid_at'] = 'Sender';
            $request_params['parcel_type'] = 'Send Parcel';

            $request_params['assign_method'] = $request->assign_method;
            $request_params['request_eta_amount'] = $eta_result->data->total;
            $user = $this->user->belongsToRole('user')
            ->where('mobile', $request->mobile)
            ->first();

            if($request->has('is_out_station') && $request->is_out_station){
                $request_params['is_out_station'] = $request->is_out_station;
                $request_params['offerred_ride_fare'] = $eta_result->data->total;
        
                if($request->has('is_round_trip')  && $request->is_round_trip == 1)
                {
                $return_time = Carbon::parse($request->return_time, $timezone)->setTimezone('UTC')->toDateTimeString();
        
                $request_params['return_time'] = $return_time;
                $request_params['is_round_trip'] = true;
        
        
                }
        
        
            }
        
            if(!$user)
            {
                $request_params1['name'] = $request->name;
                $request_params1['mobile'] = $request->mobile;
                $request_params1['country'] = $country_data->id;
                $request_params1['ride_otp'] = rand(1111, 9999);
                        
                $user = $this->user->create($request_params1); 
                
                $user->attachRole('user');
            }  
            if($user && $request->name){
                $user->name = $request->name;
                $user->save();
            }

            if($user->ride_otp==null)
            {
                $user->ride_otp=rand(1111, 9999);
                $user->save();
    
            }  
            if(!$user)
            {
              $country_data = Country::where('dial_code',$request->country)->first();
              $request_params1['name'] = $request->name;
              $request_params1['mobile'] = $request->mobile;
              $request_params1['country'] = $country_data->id;
              $request_params1['ride_otp'] = rand(1111, 9999);

              $user = $this->user->create($request_params1);  
              $user->attachRole('user');
            }  
            $request_params['user_id'] = $user->id; 

            if(!$user_detail->admin)
            {
                $request_params['booked_by'] = auth()->user()->id; 
    
            }

        // store request details to db
         
        Log::info($request_params);
        DB::beginTransaction();
        try {
            $request_detail = $this->request->create($request_params);
            if($request->has('driver_id') && $request->driver_id != null){
            $driver = Driver::find($request->driver_id);

                $selected_drivers["user_id"] =  $request_detail->user_id;
                $selected_drivers["driver_id"] = $driver->id;
                $selected_drivers["active"] = 1;
                $selected_drivers["assign_method"] = 1;
                $selected_drivers["created_at"] = date('Y-m-d H:i:s');
                $selected_drivers["updated_at"] = date('Y-m-d H:i:s');

                $request_detail->requestMeta()->create($selected_drivers);

                 $this->database->getReference('request-meta/'.$request_detail->id)
                    ->set([
                            'driver_id'=>$driver->id,
                            'request_id'=>$request_detail->id,
                            'user_id'=>$request_detail->user_id,
                            'active'=>1,
                            'transport_type'=>"taxi",
                            'updated_at'=> Database::SERVER_TIMESTAMP
                        ]);

                $request_detail->update(['assign_method'=>1, 'accepted_ride_fare'=>$request_detail->offerred_ride_fare,'is_bid_ride'=>false]);

                
            }
            // request place detail params

             if ($request->has('preferences')) {
                $this->storePreference($request_detail , json_decode($request->preferences));
            }

            if ($request->has('stops')) {

                Log::info($request->stops);

                foreach (json_decode($request->stops) as $key => $stop) {
                    $request_detail->requestStops()->create([
                    'address'=>$stop->address,
                    'latitude'=>$stop->latitude,
                    'longitude'=>$stop->longitude,
                    'order'=>$key+1]);

                }
            }
            $request_place_params = [
            'pick_lat'=>$request->pick_lat,
            'pick_lng'=>$request->pick_lng,
            'drop_lat'=>$request->drop_lat,
            'drop_lng'=>$request->drop_lng,
            'pick_address'=>$request->pick_address,
            'drop_address'=>$request->drop_address];
            // store request place details
            $request_detail->requestPlace()->create($request_place_params);
            
            $this->database->getReference('requests/'.$request_detail->id)->update(['request_id'=>$request_detail->id,'request_number'=>$request_detail->request_number,'service_location_id'=>$service_location->id,'user_id'=>$request_detail->user_id,'trnasport_type'=>$request->trnasport_type,'pick_address'=>$request->pick_address,'drop_address'=>$request->drop_address,'assign_method'=>$request->assign_method,'active'=>1,'is_accept'=>0,'date'=>$request_detail->converted_trip_start_time,'updated_at'=> Database::SERVER_TIMESTAMP]);


          


            $request_result =  fractal($request_detail, new TripRequestTransformer)->parseIncludes('userDetail');
            // @TODO send sms & email to the user
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            
            // Log full exception details clearly
                Log::error('Create Schedule Request Error: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all(),
                ]);
            Log::error('Error while Create new schedule request. Input params : ' . json_encode($request->all()));
            return $this->respondBadRequest('Unknown error occurred. Please try again later or contact us if it continues.');
        }
        DB::commit();

        return $this->respondSuccess($request_result, 'Request Scheduled Successfully');
    }
}
