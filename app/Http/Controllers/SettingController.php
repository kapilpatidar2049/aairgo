<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Country;
use App\Models\TimeZone;
use App\Models\User;
use App\Models\Referral;
use App\Models\Admin\ReferralCondition;
use App\Models\Admin\ServiceLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Transformers\Common\ReferralConditionTransformer;

class SettingController extends BaseController
{

    protected $imageUploader;

    protected $settings;



    public function __construct(Setting $settings, ImageUploaderContract $imageUploader)
    {
        $this->settings = $settings;
        $this->imageUploader = $imageUploader;
    }

    public function generalSettings() 
    {
        $settings = Setting::where('category', 'general')->get()->pluck('value', 'name')->toArray();
        // dd($settings);

        $dispathcer_addons = Setting::whereName('dispatcher-addons')->pluck('value');

        $logo = Setting::where('name', 'logo')->first();

        $favicon = Setting::where('name', 'favicon')->first();

        $loginbg = Setting::where('name', 'loginbg')->first();
        $owner_loginbg = Setting::where('name', 'owner_loginbg')->first();

        $timeZones = TimeZone::get();


        $baseUrl = request()->getSchemeAndHttpHost();

        return Inertia::render('pages/general_settings/index', [
            'settings' => $settings,
            'app_for'=>env('APP_FOR'),
            'baseUrl' => $baseUrl."/login/",
            'timeZones' => $timeZones,
            'dispathcer_addons' => $dispathcer_addons,
        ]);
    }

    public function updateStatus(Request $request)
    {
        $settings = Setting::where('category', 'general')->where('name', $request->id)->first();

        if($settings){
            $settings->update(['value'=>$request->status]);

        }else{
            Setting::create(['category'=>'general','name'=>$request->id,'value'=>$request->status]);

        }
        // dd($request->all());
       
        return response()->json([
            'successMessage' => 'status updated successfully',
        ]);
    }
    public function updateGeneralSettings(Request $request) 
    {
    // Extract settings from validated data
    $settings = $request->only([        
        'nav_color',
        'sidebar_color',
        'sidebar_text_color',
        'app_name',
        'currency_code',
        'currency_symbol',
        'contact_us_mobile1',
        'contact_us_mobile2',
        'contact_us_link',
        'default_latitude',
        'default_longitude',
        'default_timezone',
        'admin_login',
        'owner_login',
        'dispatcher_login',
        'user_login',
        'footer_content1',
        'footer_content2',
        'android_user',
        'android_driver',
        'ios_user',
        'ios_driver',
        'dispatcher_sidebar_color',
        'dispatcher_sidebar_txt_color',
        'dispatcher_login_pro'
    ]);



    // Check if files are present and handle them
    // if ($request->hasFile('logo')) {
    //     $uploadedFile = $request->file('logo');
    //     $settings['logo'] = $this->imageUploader->file($uploadedFile)->saveSystemAdminLogo();
    // }

    if ($uploadedFile = $request->file('logo')) {
        $settings['logo'] = $this->imageUploader->file($uploadedFile)
            ->saveSystemAdminLogo();
    }

    if ($uploadedFile = $request->file('favicon')) {
        $settings['favicon'] = $this->imageUploader->file($uploadedFile)
            ->saveSystemAdminLogo();
    }

    if ($uploadedFile = $request->file('loginbg')) {
        $settings['loginbg'] = $this->imageUploader->file($uploadedFile)
            ->saveSystemAdminLogo();
    }

    if ($uploadedFile = $request->file('owner_loginbg')) {
        $settings['owner_loginbg'] = $this->imageUploader->file($uploadedFile)
            ->saveSystemAdminLogo();
    }
    // if ($request->hasFile('favicon')) {
    //     $uploadedFile = $request->file('favicon');
    //     $settings['favicon'] = $this->imageUploader->file($uploadedFile)->saveSystemAdminLogo();
    // }
    // if ($request->hasFile('loginbg')) {
    //     $uploadedFile = $request->file('loginbg');
    //     $settings['loginbg'] = $this->imageUploader->file($uploadedFile)->saveSystemAdminLogo();
    // }
        $this->updateEnvFile([
            'SYSTEM_DEFAULT_TIMEZONE'=>$request->default_timezone,
        ]);


    foreach($settings as $key=>$setting)
    {
        Setting::where('name',$key)->update(['value'=>$setting]);
    }

    // Update settings
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Settings updated successfully.',
            'settings' => $settings,
        ], 200);
    }

    
    public function customizationSettings() 
    {
        $countries = Country::where('active', true)->whereNotNull('code')->whereRaw('LENGTH(code) > 0')->pluck('code', 'id')->toArray();
        $settings = Setting::where('category', 'customization_settings')->get()->pluck('value', 'name')->toArray();
        return Inertia::render('pages/customization_settings/index',
        [ 'settings' => $settings,'countries' => $countries,'app_for'=>env('APP_FOR')]);
    }
    
    public function updateCustomizationStatus(Request $request)
    {
        $settings = Setting::where('category', 'customization_settings')->where('name', $request->id)->first();

        if($settings){
            $settings->update(['value'=>$request->status, 'category'=>'customization_settings']);
            

        }
        // dd($settings);
       
        return response()->json([
            'successMessage' => 'status updated successfully',
        ]);
    }

    public function updateCustomizationSettings(Request $request) 
    {
    // dd($request->all());
    // Extract settings from validated data
    $settings = request()->only([
        'enable_vase_map',
        'enable_modules_for_applications',        
        'default_country_code_for_mobile_app',
        'default_country_code_for_mobile_app',
        'enable_shipment_load_feature',
        'show_outstation_ride_feature',
        'show_delivery_outstation_ride_feature',
        'enable_shipment_unload_feature',
        'enable_digital_signature',
        'enable_pet_preference_for_user',
        'enable_document_auto_approval',
        'enable_luggage_preference_for_user',
        'enable_my_route_booking_feature',
        'enable_country_restrict_on_map',
        'show_wallet_feature_on_mobile_app',
        'show_wallet_feature_on_mobile_app_driver',
        'show_wallet_feature_on_mobile_app_for_owner',
        'show_instant_ride_feature_on_mobile_app',
        'show_owner_module_feature_on_mobile_app',
        'show_wallet_money_transfer_feature_on_mobile_app',
        'show_wallet_money_transfer_feature_on_mobile_app_for_driver',
        'show_wallet_money_transfer_feature_on_mobile_app_for_owner',
        // 'show_email_otp_feature_on_mobile_app',
        // 'show_bank_info_feature_on_mobile_app',
        'show_rental_ride_feature',
        'show_delivery_rental_ride_feature',
        'show_card_payment_feature',
        'show_taxi_rental_ride_feature',
        'show_ride_otp_feature',
        'show_ride_later_feature',
        'show_ride_without_destination',
        'enable_web_booking_feature',
        'show_incentive_feature_for_driver',
        'enable_landing_site',
        'enable_sub_vehicle_feature',
        'show_driver_level_feature',
        // 'enable_driver_tips_feature',
        'enable_driver_profile_disapprove_on_update',
        'enable_support_ticket_feature',
        'enable_map_appearance_change_on_mobile_app',
        'enable_eta_total_update',
        'enable_driver_leaderboard_feature',
        'enable_multiple_ride_feature',
        'how_many_times_a_driver_can_enable_the_my_route_booking_per_day',
        'enable_outstation_round_trip_feature',
        // 'enable_email_login',
        'enable_fixed_fare',
        'enable_user_sign_in_email_otp',
        'enable_user_sign_in_email_password',
        'enable_user_sign_in_mobile_otp',
        'enable_user_sign_in_mobile_password',
        // 'enable_user_sign_up_email_otp',
        // 'enable_user_sign_up_email_password',
        // 'enable_user_sign_up_mobile_otp',
        // 'enable_user_sign_up_mobile_password',
        'enable_driver_sign_in_email_otp',
        'enable_driver_sign_in_email_password',
        'enable_driver_sign_in_mobile_otp',
        'enable_driver_sign_in_mobile_password',
        'enable_owner_sign_in_email_otp',
        'enable_owner_sign_in_email_password',
        'enable_owner_sign_in_mobile_otp',
        'enable_owner_sign_in_mobile_password',
        'enable_user_email_login',
        'enable_user_mobile_login',
        'enable_driver_email_login',
        'enable_driver_mobile_login',
        'enable_owner_email_login',
        'enable_owner_mobile_login'
    ]);
   //dd($request);


   foreach($settings as $key=>$setting)
    {
        Setting::where('name',$key)->update(['value'=>$setting, 'category'=>'customization_settings']);
    }

    // Update settings
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Settings updated successfully.',
            'settings' => $settings,
        ], 200);
    }

    public function transportRideSettings() 
    {
        $settings = Setting::where('category', 'trip_settings')->get()->pluck('value', 'name')->toArray();
// dd($settings);
        return Inertia::render('pages/transport_ride_settings/index', [
            'settings' => $settings,
            'app_for'=>env('APP_FOR'),
        ]);
    }
    public function updateTransportSettings(Request $request) 
    {
        // dd($request->all());
    // Extract settings from validated data
    $settings = request()->only([
        'trip_dispatch_type',
        'driver_search_radius',
        'maximum_time_for_accept_reject_bidding_ride',
        'user_can_make_a_ride_after_x_miniutes',
        'maximum_time_for_find_drivers_for_bitting_ride',
        'minimum_time_for_search_drivers_for_schedule_ride',
        'minimum_time_for_starting_trip_drivers_for_schedule_ride',
        'maximum_time_for_find_drivers_for_regular_ride',
        'trip_accept_reject_duration_for_driver',
        // 'bidding_low_percentage',
        // 'bidding_high_percentage',
        // 'bidding_amount_increase_or_decrease',
        'can_round_the_bill_values',
        // 'minimum_trip_distane',
        'bidding_ride_maximum_distance'
    ]);
   


   foreach($settings as $key=>$setting)
    {
        Setting::where('name',$key)->update(['value'=>$setting]);
    }

    // Update settings
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Settings updated successfully.',
            'settings' => $settings,
        ], 200);
    }

    public function updateTransportStatus(Request $request)
    {
        $settings = Setting::where('category', 'trip_settings')->where('name', $request->id)->first();
        // dd($request);

        if($settings){
            $settings->update(['value'=>$request->status]);

        }
       
        return response()->json([
            'successMessage' => 'status updated successfully',
        ]);
    }

    public function bidRideSettings() 
    {
        $settings = Setting::where('category', 'trip_settings')->get()->pluck('value', 'name')->toArray();
// dd($settings);
        return Inertia::render('pages/bid_ride_settings/index', [
            'app_for'=>env('APP_FOR'),
            'settings' => $settings
        ]);
    }
    public function updateBidSettings(Request $request) 
    {
        // dd($request->all());
    // Extract settings from validated data
    $settings = request()->only([
        'bidding_low_percentage',
        'bidding_high_percentage',
        'bidding_amount_increase_or_decrease',
        'user_bidding_low_percentage',
        'user_bidding_high_percentage',
        'user_bidding_amount_increase_or_decrease',
    ]);
   


   foreach($settings as $key=>$setting)
    {
        Setting::where('name',$key)->update(['value'=>$setting]);
    }

    // Update settings
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Settings updated successfully.',
            'settings' => $settings,
        ], 200);
    }
    public function walletSettings() 
    {
        $driver_wallet = Setting::where('category', 'Wallet')->where('name', 'driver_wallet_minimum_amount_to_get_an_order')->first();
        $minimum_wallet = Setting::where('category', 'Wallet')->where('name', 'minimum_wallet_amount_for_transfer')->first();
        $owner_wallet = Setting::where('category', 'Wallet')->where('name', 'owner_wallet_minimum_amount_to_get_an_order')->first();
        $minimum_wallet_add = Setting::where('category', 'Wallet')->where('name', 'minimum_amount_added_to_wallet')->first();
    
        return Inertia::render('pages/wallet_settings/index', [
            'app_for'=>env('APP_FOR'),
            'driver_wallet' => $driver_wallet,
            'minimum_wallet' => $minimum_wallet,
            'owner_wallet' => $owner_wallet, 
            'minimum_wallet_add' => $minimum_wallet_add, 
        ]);
    }
    public function updateWalletSettings(Request $request)
    {
        $settings = $request->only([
            'driver_wallet_minimum_amount_to_get_an_order',
            'minimum_wallet_amount_for_transfer',
            'owner_wallet_minimum_amount_to_get_an_order',
            'minimum_amount_added_to_wallet',
        ]);
    
        // Delete existing wallet settings
        Setting::where('category', 'Wallet')->delete();
    
        // Insert new wallet settings
        foreach ($settings as $key => $setting) {
            Setting::create([
                'name' => $key,
                'field' => 'text',
                'value' => $setting,
                'category' => 'Wallet'
            ]);
        }

        return response()->json([
            'successMessage' => 'Settings updated successfully.',
        ], 201);

    }
    public function referralSettings() 
    {
        $enable_user_referral_earnings = Setting::where('category', 'referral')
            ->where('name', 'enable_user_referral_earnings')
            ->first();
    
   
    
        $referral_commission_amount_for_user = Setting::where('category', 'referral')
            ->where('name', 'referral_commission_amount_for_user')
            ->first();



        $referral_commission_for_new_user_from_referer_user = Setting::where('category', 'referral')
        ->where('name', 'referral_commission_for_new_user_from_referer_user')
        ->first();

        $referral_commission_for_new_driver_from_referer_user = Setting::where('category', 'referral')
            ->where('name', 'referral_commission_for_new_driver_from_referer_user')
            ->first();

        
        $referral_type = Setting::where('category', 'referral')
        ->where('name', 'referral_type')
        ->first(); 

        $enable_referral_condition_by_ride_count = Setting::where('category', 'referral')
            ->where('name', 'enable_referral_condition_by_ride_count')
            ->first();

        $enable_referral_condition_by_earning = Setting::where('category', 'referral')
            ->where('name', 'enable_referral_condition_by_earning')
            ->first();

        $referral_condition_user_ride_count = Setting::where('category', 'referral')
            ->where('name', 'referral_condition_user_ride_count')
            ->first();

        $referral_condition_driver_ride_count = Setting::where('category', 'referral')
            ->where('name', 'referral_condition_driver_ride_count')
            ->first();

        $referral_condition_user_spent_amount = Setting::where('category', 'referral')
            ->where('name', 'referral_condition_user_spent_amount')
            ->first();

        $referral_condition_driver_earning_amount = Setting::where('category', 'referral')
            ->where('name', 'referral_condition_driver_earning_amount')
            ->first();
        
       
        // Convert the settings to boolean
        $enable_user_referral_earnings_value = $enable_user_referral_earnings && $enable_user_referral_earnings->value === "1";
        $enable_referral_condition_by_ride_count_value = $enable_referral_condition_by_ride_count && $enable_referral_condition_by_ride_count->value === "1";
        $enable_referral_condition_by_earning_value = $enable_referral_condition_by_earning && $enable_referral_condition_by_earning->value === "1";
    // dd($enable_user_referral_earnings_value);
        return Inertia::render('pages/referral_settings/index', [
            'enable_user_referral_earnings' => $enable_user_referral_earnings_value,
            'referral_commission_amount_for_user' => $referral_commission_amount_for_user,
            'app_for'=>env("APP_FOR"),
            'referral_type' => $referral_type,
            'referral_commission_for_new_user_from_referer_user' => $referral_commission_for_new_user_from_referer_user,
            'referral_commission_for_new_driver_from_referer_user' => $referral_commission_for_new_driver_from_referer_user,
            'enable_referral_condition_by_ride_count' => $enable_referral_condition_by_ride_count_value,
            'enable_referral_condition_by_earning' => $enable_referral_condition_by_earning_value,
            'referral_condition_user_ride_count' => $referral_condition_user_ride_count,
            'referral_condition_driver_ride_count' => $referral_condition_driver_ride_count,
            'referral_condition_user_spent_amount' => $referral_condition_user_spent_amount,
            'referral_condition_driver_earning_amount' => $referral_condition_driver_earning_amount,
       ]); 
    }
    
    public function updateReferralSettings(Request $request)
    {
       // dd($request->all());
        $settings = $request->only([
            'referral_commission_amount_for_user',
            'enable_user_referral_earnings',
            'referral_type',
            'referral_commission_for_new_user_from_referer_user',
            'referral_commission_for_new_driver_from_referer_user',
            'enable_referral_condition_by_ride_count',
            'enable_referral_condition_by_earning',
            'referral_condition_user_ride_count',
            'referral_condition_driver_ride_count',
            'referral_condition_user_spent_amount',
            'referral_condition_driver_earning_amount',
        ]);
    
        // Delete existing wallet settings
        Setting::where('category', 'referral')->delete();
    
        // Insert new wallet settings
        foreach ($settings as $key => $setting) {
            Setting::create([
                'name' => $key,
                'field' => 'text',
                'value' => $setting,
                'category' => 'referral'
            ]);
        }

    }
    public function updateReferralToggle(Request $request)
    {

        // dd($request->all());

        $settings = Setting::where('category', 'referral')->where('name', $request->key)->first();

        if($settings)
        {
            $settings->update(['value'=>$request->enabled]);

        }else{
           $settings =  Setting::create(['category'=> 'referral', 'name'=> $request->key,'value'=>$request->enabled]);

            // dd($settings);
        }


        return response()->json([
            'successMessage' => 'Settings updated successfully.',
        ], 200);
    }
    public function peakZoneSettings()
    {
        $settings = Setting::where('category', 'peak_zone_settings')->get()->pluck('value', 'name')->toArray();
        return Inertia::render('pages/peak_zone_setting/index',
        [ 'settings' => $settings,'app_for'=>env('APP_FOR')]);
    }
    public function updatePeakZoneSettings(Request $request) 
    {
    // Extract settings from validated data
    $settings = request()->only([
        'enable_peak_zone_feature',
        'peak_zone_radius',
        'peak_zone_duration',
        'peak_zone_history_duration',
        'peak_zone_ride_count',
        'distance_price_percentage',
    ]);
   


   foreach($settings as $key=>$setting)
    {
        Setting::where('name',$key)->update(['value'=>$setting]);
    }

    // Update settings
        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Settings updated successfully.',
            'settings' => $settings,
        ], 200);
    }

    public function tipSettings() 
    {
        $minimum_tip_add = Setting::where('category', 'tip_settings')->where('name', 'minimum_tip_amount')->first();
        $settings = Setting::where('category', 'tip_settings')->get()->pluck('value', 'name')->toArray();
    
        return Inertia::render('pages/tip_settings/index', [
            'app_for'=>env('APP_FOR'),
            'minimum_tip_add' => $minimum_tip_add,
            'settings' => $settings, 
        ]);
    }
    public function updateTipStatus(Request $request)
    {
        $settings = Setting::where('name', $request->id)->first();
        // dd($request);

        if($settings){
            if($settings->category !== 'tip_settings'){
                $settings->update(['category'=> 'tip_settings']);
            }
            $settings->update(['value'=>$request->status]);

        }
       
        return response()->json([
            'successMessage' => 'status updated successfully',
        ]);
    }
    public function updateTipSettings(Request $request)
    {
        $settings = $request->only([
            'minimum_tip_amount',
            'enable_driver_tips_feature',
        ]);
    
        // Delete existing wallet settings
        Setting::where('category', 'tip_settings')->delete();
    
        // Insert new wallet settings
        foreach ($settings as $key => $setting) {
            Setting::create([
                'name' => $key,
                'field' => 'text',
                'value' => $setting,
                'category' => 'tip_settings'
            ]);
        }

        return response()->json([
            'successMessage' => 'Settings updated successfully.',
        ], 201);

    }

    /**
     * Update the .env file with new settings.
     *
     * @param array $settings
     * @return void
     */
    private function updateEnvFile(array $settings)
    {
        // Get the path to the .env file
        $envPath = base_path('.env');

        // Check if the .env file exists
        if (file_exists($envPath)) {
            // Read the current content of the .env file
            $envContent = file_get_contents($envPath);

            // Update or add each setting in the .env file
            foreach ($settings as $key => $value) {
                $envKey = strtoupper($key); // Convert the key to uppercase to match the .env convention

                // Create a regex pattern to match the existing key-value pair
                $pattern = "/^{$envKey}=[^\r\n]*/m";

                // If the key exists, replace it; otherwise, append the new key-value pair
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$envKey}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$envKey}={$value}";
                }
            }

            // Write the updated content back to the .env file
            file_put_contents($envPath, $envContent);
        }
    }
    //referral dashboard//
    public function referralDashboard(){
        return Inertia::render("pages/referral_settings/dashboard");
    }
    public function DriverReferralSettings() {
        $enable_driver_referral_earnings = Setting::where('category', 'driver_referral')
            ->where('name', 'enable_driver_referral_earnings')
            ->first();
        $driver_referral_type = Setting::where('category', 'driver_referral')
            ->where('name', 'driver_referral_type')
            ->first(); 

        $enable_driver_referral_condition_by_ride_count = Setting::where('category', 'driver_referral')
            ->where('name', 'enable_driver_referral_condition_by_ride_count')
            ->first();

        $enable_driver_referral_condition_by_earning = Setting::where('category', 'driver_referral')
            ->where('name', 'enable_driver_referral_condition_by_earning')
            ->first();

        $driver_referral_condition_user_ride_count = Setting::where('category', 'driver_referral')
            ->where('name', 'driver_referral_condition_user_ride_count')
            ->first();

        $driver_referral_condition_driver_ride_count = Setting::where('category', 'driver_referral')
            ->where('name', 'driver_referral_condition_driver_ride_count')
            ->first();

        $driver_referral_condition_user_spent_amount = Setting::where('category', 'driver_referral')
            ->where('name', 'driver_referral_condition_user_spent_amount')
            ->first();

        $driver_referral_condition_driver_earning_amount = Setting::where('category', 'driver_referral')
            ->where('name', 'driver_referral_condition_driver_earning_amount')
            ->first();

        $referral_commission_for_new_user_from_referer_driver = Setting::where('category', 'driver_referral')
            ->where('name', 'referral_commission_for_new_user_from_referer_driver')
            ->first();

        $referral_commission_for_new_driver_from_referer_driver = Setting::where('category', 'driver_referral')
            ->where('name', 'referral_commission_for_new_driver_from_referer_driver')
            ->first(); 
        $referral_commission_amount_for_driver = Setting::where('category', 'driver_referral')
            ->where('name', 'referral_commission_amount_for_driver')
            ->first();

            $enable_driver_referral_condition_by_ride_count_value = $enable_driver_referral_condition_by_ride_count && $enable_driver_referral_condition_by_ride_count->value === "1";
            $enable_driver_referral_condition_by_earning_value = $enable_driver_referral_condition_by_earning && $enable_driver_referral_condition_by_earning->value === "1";
            $enable_driver_referral_earnings_value = $enable_driver_referral_earnings && $enable_driver_referral_earnings->value === "1";

        return Inertia::render('pages/referral_settings/driverIndex', [
            'enable_driver_referral_earnings' => $enable_driver_referral_earnings_value,
            'driver_referral_type' => $driver_referral_type,
            'referral_commission_for_new_user_from_referer_driver' => $referral_commission_for_new_user_from_referer_driver,
            'referral_commission_for_new_driver_from_referer_driver' => $referral_commission_for_new_driver_from_referer_driver,
            'enable_driver_referral_condition_by_earning' => $enable_driver_referral_condition_by_earning_value,
            'enable_driver_referral_condition_by_ride_count' => $enable_driver_referral_condition_by_ride_count_value,
            'driver_referral_condition_user_ride_count' => $driver_referral_condition_user_ride_count,
            'driver_referral_condition_driver_ride_count' => $driver_referral_condition_driver_ride_count,
            'driver_referral_condition_user_spent_amount' => $driver_referral_condition_user_spent_amount,
            'driver_referral_condition_driver_earning_amount' => $driver_referral_condition_driver_earning_amount,
            'referral_commission_amount_for_driver' => $referral_commission_amount_for_driver,
 
        ]);
     }
     public function updateDriverReferralSettings(Request $request)
    {
        $settings = $request->only([
            'driver_referral_type',
            'referral_commission_for_new_user_from_referer_driver',
            'referral_commission_for_new_driver_from_referer_driver',
            'enable_driver_referral_condition_by_earning',
            'enable_driver_referral_condition_by_ride_count',
            'driver_referral_condition_user_ride_count',
            'driver_referral_condition_driver_ride_count',
            'driver_referral_condition_user_spent_amount',
            'driver_referral_condition_driver_earning_amount',            
            'enable_driver_referral_earnings',
            'referral_commission_amount_for_driver',
        ]);
         Setting::where('category', 'driver_referral')->delete();
    
        // Insert new wallet settings
        foreach ($settings as $key => $setting) {
            Setting::create([
                'name' => $key,
                'field' => 'text',
                'value' => $setting,
                'category' => 'driver_referral'
            ]);
        }

    }
    public function updateDriverReferralToggle(Request $request)
    {
        // dd($request->all());
        $settings = Setting::where('category', 'driver_referral')->where('name', $request->key)->first();

        if($settings)
        {
            $settings->update(['value'=>$request->enabled]);

        }else{
           $settings =  Setting::create(['category'=> 'driver_referral', 'name'=> $request->key,'value'=>$request->enabled]);

            // dd($settings);
        }
        return response()->json([
            'successMessage' => 'Settings updated successfully.',
        ], 200);
    }
    
    public function driverReferral(){
        return Inertia::render("pages/referral_settings/driverIndex");
    }
    
    public function referralTranslation(){
       $referrals = ReferralCondition::with('referralConditionTranslationWords')->get();
        foreach ($referrals as $referral) {
            $referral_description = [];
            foreach ($referral->referralConditionTranslationWords as $translation) {
                $referral_description[$translation->locale] = $translation->description;
            }
          $referral->referral_description = !empty($referral_description) ? $referral_description : [];

        }
        // dd($referrals);
       return Inertia::render("pages/referral_settings/referralTranslation",['referrals'=>$referrals]);
    }

    

    public function updateReferralTranslation(Request $request)
    {
        $validated = $request->validate([
            'referral_description' => 'required|array',
        ]);

        foreach ($validated['referral_description'] as $referralType => $languages) {
            $referral = ReferralCondition::where('referral_type', $referralType)->first();

            if ($referral) {
                $referral->referralConditionTranslationWords()->delete();

                $translationData = [];
                $translations_data = [];

                foreach ($languages as $code => $description) {
                    $translationData[] = [
                        'locale' => $code,
                        'description' => $description,
                        'referral_id' => $referral->id,
                    ];
                    $translations_data[$code] = (object)[
                        'locale' => $code,
                        'description' => $description,
                    ];
                }
                $referral->referralConditionTranslationWords()->insert($translationData);
                $referral->update([
                    'description' => $languages['en'] ?? null,
                    'translation_dataset' => json_encode($translations_data, JSON_UNESCAPED_UNICODE),
                ]);
            }
        }
        return response()->json([
            'successMessage' => 'Referral Descriptions updated successfully!',
        ], 201);
    }


  

    public function referralDashboardData(){
        $service_location_id = request()->service_location_id;

        $currency_symbol = get_settings('currency_symbol');
        $timezone = get_settings('default_timezone');

        if($service_location_id && $service_location_id != 'all'){
            $service = ServiceLocation::find($service_location_id);
            if($service){
                $currency_symbol = $service->currency_symbol;
                $timezone = $service->timezone;
            }
        }

        $now = now($timezone);
        $prev_month_end_date = $now->copy()->subMonth()->endOfMonth()->endOfDay();
        $currentYear = $now->year;

        $referrerQuery = User::select(
            DB::raw("COUNT(CASE WHEN referred_by IS NOT NULL THEN 1 END) as referred_count"),
            DB::raw("COUNT(CASE WHEN referred_by IS NULL THEN 1 END) as normal_count"),
            DB::raw("COUNT(CASE WHEN referred_by IS NOT NULL AND created_at <= '{$prev_month_end_date}' THEN 1 END) as prev_referred_count")
        );

        $monthlyReferralQuery = User::whereNotNull('referred_by')->whereYear('created_at', $currentYear);

        $topReferrerQuery = User::query()
            ->whereNotNull('users.referred_by')
            ->join('users as ref', 'ref.id', '=', 'users.referred_by')
            ->selectRaw('
                users.referred_by as referrer_id,
                ref.name as name,
                ref.profile_picture as profile_picture,
                COUNT(users.id) as referral_count
            ')
            ->groupBy('users.referred_by', 'ref.name', 'ref.profile_picture')
            ->orderByDesc('referral_count')
            ->limit(5);

        $referralEarningQuery = Referral::where('status', 'credited')
            ->select(
                DB::raw('SUM(referrer_amount + new_amount) as total'),
                DB::raw("SUM(CASE WHEN created_at <= '{$prev_month_end_date}' THEN (referrer_amount + new_amount) ELSE 0 END) as prev_total")
            );

        if($service_location_id && $service_location_id != 'all'){
            $topReferrerQuery = $topReferrerQuery->where('users.service_location_id',$service_location_id);
            $referrerQuery = $referrerQuery->where('service_location_id',$service_location_id);
            $referralEarningQuery = $referralEarningQuery->whereHas('referrer', function($locationQuery) use ($service_location_id){
                $locationQuery->where('service_location_id',$service_location_id);
            });
            $monthlyReferralQuery->where('service_location_id', $service_location_id);
        }

        $userData = (clone $referrerQuery)->belongsToRole('user')->first();
        $driverData = (clone $referrerQuery)->belongsToRole('driver')->first();

        $referralEarning = $referralEarningQuery->first();

        $userMonths = (clone $monthlyReferralQuery)->belongsToRole('user')->get()
            ->groupBy(function ($user) use ($timezone) {
                return \Carbon\Carbon::parse($user->created_at)
                    ->setTimezone($timezone)
                    ->format('m');
            })
            ->map(fn($group) => $group->count());

        $driverMonths = (clone $monthlyReferralQuery)->belongsToRole('driver')->get()
            ->groupBy(function ($user) use ($timezone) {
                return \Carbon\Carbon::parse($user->created_at)
                    ->setTimezone($timezone)
                    ->format('m');
            })
            ->map(fn($group) => $group->count());


        $userMonthsData = collect(range(1, 12))->mapWithKeys(function ($month) use ($userMonths) {
            $monthKey = str_pad($month, 2, '0', STR_PAD_LEFT);
            return [$monthKey => $userMonths[$monthKey] ?? 0];
        });

        $driverMonthsData = collect(range(1, 12))->mapWithKeys(function ($month) use ($driverMonths) {
            $monthKey = str_pad($month, 2, '0', STR_PAD_LEFT);
            return [$monthKey => $driverMonths[$monthKey] ?? 0];
        });
        
        $userReferredData = array_values($userMonthsData->toArray());
        $driverReferredData = array_values($driverMonthsData->toArray());

        $topUserReferrer = (clone $topReferrerQuery)->belongsToRole('user')->get();
        $topDriverReferrer = (clone $topReferrerQuery)->belongsToRole('driver')->get();
        $userData['referral_count_rise'] = (($userData['referred_count'] - $userData['prev_referred_count']) / ($userData['prev_referred_count']>0 ? $userData['prev_referred_count'] : 1) * 100);
        $driverData['referral_count_rise'] = (($driverData['referred_count'] - $driverData['prev_referred_count']) / ($driverData['prev_referred_count']>0 ? $driverData['prev_referred_count'] : 1) * 100);
        $referralEarning['total_rise'] = (($referralEarning['total'] - $referralEarning['prev_total']) / ($referralEarning['prev_total']>0 ? $referralEarning['prev_total'] : 1) * 100);
        $active_referral = $userData['referred_count'] + $driverData['referred_count'];
        $prev_active_referral = $userData['prev_referred_count'] + $driverData['prev_referred_count'];
        $referralEarning['active_referral_rise'] = ($active_referral - $prev_active_referral) / ($prev_active_referral > 0 ? $prev_active_referral : 1)* 100; 

        $data = [
            'userData' => [
                'non_referred_users' => $userData['normal_count'],
                'referred_users' => $userData['referred_count'],
                'referral_users_rise' => round($userData['referral_count_rise'] ?? 0),
                'referred_data' => $userReferredData,
            ],
            'driverData' => [
                'non_referred_users' => $driverData['normal_count'],
                'referred_users' => $driverData['referred_count'],
                'referral_users_rise' => round($driverData['referral_count_rise'] ?? 0),
                'referred_data' => $driverReferredData,
            ],
            'referralData' =>[
                'total_drivers'=>$driverData['referred_count'],
                'total_users'=>$userData['referred_count'],
                'active_referrals'=>$userData['referred_count'] + $driverData['referred_count'],
                'referral_earning'=>round($referralEarning['total'],2),
                'total_drivers_rise'=>round($driverData['referral_count_rise'] ?? 0),
                'total_users_rise'=>round($userData['referral_count_rise'] ?? 0),
                'referrals_rise'=>round($referralEarning['active_referral_rise'] ?? 0),
                'referral_earning_rise'=> round($referralEarning['total_rise']?? 0),
            ],
            'dashboardData'=>[
                'user_leaderboard' => $topUserReferrer,
                'driver_leaderboard' => $topDriverReferrer,
                'currency_symbol' => $currency_symbol,
            ]
        ];


        return response()->json($data);
    }


    public function referralCondition(){
        $referral_type = get_settings('referral_type');
        $user_banner = ReferralCondition::where('referral_type', 'user_banner_text')->first();

        $referred_user = "";
        $user_spent_amount = "";
        $driver_earning_amount = "";
        $user_ride_count = "";
        $driver_ride_count = "";
        $new_driver_commission = "";
        $new_user_commission = "";
        $finalString = "";

        $user_referral = get_settings('enable_user_referral_earnings');

     
        if($user_referral == 1){

            $referral_type = get_settings('referral_type');

            if($referral_type == 'instant'){                
                $referred_user = get_settings('referral_commission_amount_for_user');
               
                $referral_condition = ReferralCondition::where('referral_type','instant_referrer_user')->first();
               
                $finalString = str_replace('{referred_user_amount}', $referred_user, $referral_condition->description);
               
            }
            elseif($referral_type == 'dual_instant') {

                $referred_user = get_settings('referral_commission_amount_for_user');
                $new_user_commission = get_settings('referral_commission_for_new_user_from_referer_user');
                $new_driver_commission = get_settings('referral_commission_for_new_driver_from_referer_user');
               
                $referral_condition = ReferralCondition::where('referral_type','instant_referrer_user_and_new_user')->first();
                
                $finalString = str_replace(

                    ['{referred_user_amount}', '{new_driver_amount}', '{new_user_amount}'], 
                    [$referred_user, $new_driver_commission, $new_user_commission],  

                    $referral_condition->description                                        
                );
               
            }
            elseif($referral_type == 'conditional') {

                $referral_ride_count = get_settings('enable_referral_condition_by_ride_count');

                if($referral_ride_count == 1){
                    $referred_user = get_settings('referral_commission_amount_for_user');
                    $user_ride_count = get_settings('referral_condition_user_ride_count');
                    $driver_ride_count = get_settings('referral_condition_driver_ride_count');
                
                    $referral_condition = ReferralCondition::where('referral_type','conditional_for_referrer_user_ride_count')->first();

                    $finalString = str_replace(
                        ['{referred_user_amount}', '{user_ride_count}', '{driver_ride_count}'], 
                        [$referred_user, $user_ride_count, $driver_ride_count],  

                        $referral_condition->description                                        
                    );
                }

               $referral_earnings = get_settings('enable_referral_condition_by_earning');

                if($referral_earnings == 1){
                    $referred_user = get_settings('referral_commission_amount_for_user');
                    $user_spent_amount = get_settings('referral_condition_user_spent_amount');
                    $driver_earning_amount = get_settings('referral_condition_driver_earning_amount');
                   
                    $referral_condition = ReferralCondition::where('referral_type','conditional_for_referrer_user_earnings')->first();
                  

                    $finalString = str_replace(
                    ['{referred_user_amount}', '{user_spent_amount}', '{driver_earning_amount}'], 
                    [$referred_user, $user_spent_amount, $driver_earning_amount], 

                    $referral_condition->description    
                   );
                }
            }
            elseif ($referral_type == 'dual_conditional') {

              $referral_ride_count = get_settings('enable_referral_condition_by_ride_count');
              
                if ($referral_ride_count == 1) {

                    $referred_user        = get_settings('referral_commission_amount_for_user');
                    $user_ride_count      = get_settings('referral_condition_user_ride_count');
                    $driver_ride_count    = get_settings('referral_condition_driver_ride_count');
                    $new_driver_commission = get_settings('referral_commission_for_new_driver_from_referer_user');
                    $new_user_commission   = get_settings('referral_commission_for_new_user_from_referer_user');

                    $referral_condition = ReferralCondition::where('referral_type', 'dual_conditional_for_referrer_user_and_new_user_ride_count')->first();

                    $finalString = str_replace(
                        [
                            '{referred_user_amount}',
                            '{user_ride_count}',
                            '{driver_ride_count}',
                            '{new_user_amount}',
                            '{new_driver_amount}'
                        ],
                        [
                            $referred_user,
                            $user_ride_count,
                            $driver_ride_count,
                            $new_user_commission,
                            $new_driver_commission
                        ],
                        $referral_condition->description
                    );
                }
                //earnings
                $referral_earnings = get_settings('enable_referral_condition_by_earning');
               
                if($referral_earnings == 1){

                    $referred_user        = get_settings('referral_commission_amount_for_user');
                    $user_spent_amount      = get_settings('referral_condition_user_spent_amount');
                    $driver_earning_amount   = get_settings('referral_condition_driver_earning_amount');
                    $new_driver_commission = get_settings('referral_commission_for_new_driver_from_referer_user');
                    $new_user_commission   = get_settings('referral_commission_for_new_user_from_referer_user');

                    $referral_condition = ReferralCondition::where('referral_type', 'dual_conditional_for_referrer_user_and_new_user_earnings')->first();

                   $finalString = str_replace(
                    [
                        '{referred_user_amount}',
                        '{user_spent_amount}',
                        '{driver_earning_amount}',
                        '{new_driver_amount}',
                        '{new_user_amount}'
                    ],
                    [
                        $referred_user,
                        $user_spent_amount,
                        $driver_earning_amount,
                        $new_driver_commission,
                        $new_user_commission
                    ],
                    $referral_condition->description
                    );

                }
               
            }
            $referral_condition->description = $finalString;
            $referral_condition->translation_dataset = str_replace(
                ['{referred_user_amount}', '{user_spent_amount}', '{driver_earning_amount}', '{new_driver_amount}', '{new_user_amount}', '{user_ride_count}', '{driver_ride_count}'],
                [$referred_user, $user_spent_amount, $driver_earning_amount, $new_driver_commission, $new_user_commission, $user_ride_count, $driver_ride_count],
                $referral_condition->translation_dataset
            );

            $result  = fractal($referral_condition, new ReferralConditionTransformer)->toArray();

            $user_banner  = fractal($user_banner, new ReferralConditionTransformer)->toArray();

            return $this->respondSuccess(
            [
                'referral_content' => $result,
                'user_banner' => $user_banner,
            ],
            'referrals_description'
        );
        }   
        else{
            $user_banner  = fractal($user_banner, new ReferralConditionTransformer)->toArray();
            return $this->respondSuccess(
            [
                'referral_content' => null,
                'user_banner' => $user_banner,
            ],
        );
        }    
    }

    public function driverReferralCondition(){
        $referral_type = get_settings('referral_type');
        $driver_banner = ReferralCondition::where('referral_type', 'driver_banner_text')->first();


        $finalString = "";   
        $referred_driver = "";     
        $user_spend_amount = "";
        $new_driver_commision = "";
        $new_user_commision = "";
        $driver_earning_amount = "";
        $user_ride_count = "";
        $driver_ride_count = "";
  

        $driver_referral = get_settings('enable_driver_referral_earnings');

        if($driver_referral == 1){

            $driver_referral_type = get_settings('driver_referral_type');

            if ($driver_referral_type == 'instant') {

                $referred_driver = get_settings('referral_commission_amount_for_driver');

                $referral_condition = ReferralCondition::where('referral_type', 'instant_referrer_driver')->first();

                $finalString = str_replace('{referred_driver_amount}', $referred_driver, $referral_condition->description);

                //dd($finalString);
            }

           elseif ($driver_referral_type == 'dual_instant') {

                $referred_driver      = get_settings('referral_commission_amount_for_driver');
                $new_user_commision   = get_settings('referral_commission_for_new_user_from_referer_driver');
                $new_driver_commision = get_settings('referral_commission_for_new_driver_from_referer_driver');

                $referral_condition = ReferralCondition::where('referral_type', 'instant_referrer_driver_and_new_driver')->first();

                $finalString = str_replace(
                    ['{referred_driver_amount}', '{new_driver_amount}', '{new_user_amount}'],
                    [$referred_driver, $new_driver_commision, $new_user_commision],
                    $referral_condition->description
                );
            }

            elseif($driver_referral_type == 'conditional') {

                $enable_ride_count = get_settings('enable_driver_referral_condition_by_ride_count');

                if($enable_ride_count == 1){
                    $referred_driver = get_settings('referral_commission_amount_for_driver');
                    $user_ride_count = get_settings('driver_referral_condition_user_ride_count');
                    $driver_ride_count = get_settings('driver_referral_condition_driver_ride_count');
                
                    $referral_condition = ReferralCondition::where('referral_type','conditional_for_referrer_driver_ride_count')->first();

                    $finalString = str_replace(
                        ['{referred_driver_amount}', '{user_ride_count}', '{driver_ride_count}'], 
                        [$referred_driver, $user_ride_count, $driver_ride_count],  

                        $referral_condition->description                                        
                    );
                    //dd($finalString);
                }

               $driver_referral_earnings = get_settings('enable_driver_referral_condition_by_earning');

                if($driver_referral_earnings == 1){
                    $referred_driver = get_settings('referral_commission_amount_for_driver');
                    $user_spend_amount = get_settings('driver_referral_condition_user_spent_amount');
                    $driver_earning_amount = get_settings('driver_referral_condition_driver_earning_amount');
                   
                    $referral_condition = ReferralCondition::where('referral_type','conditional_for_referrer_driver_earnings')->first();
                  

                    $finalString = str_replace(
                    ['{referred_driver_amount}', '{user_spent_amount}', '{driver_earning_amount}'], 
                    [$referred_driver, $user_spend_amount, $driver_earning_amount], 

                    $referral_condition->description    
                   );
                   //dd($finalString);
                }
            }
            elseif ($driver_referral_type == 'dual_conditional') {

              $enable_ride_count = get_settings('enable_driver_referral_condition_by_ride_count');
              
                if ($enable_ride_count == 1) {

                    $referred_driver = get_settings('referral_commission_amount_for_driver');
                    $user_ride_count      = get_settings('driver_referral_condition_user_ride_count');
                    $driver_ride_count    = get_settings('driver_referral_condition_driver_ride_count');
                    $new_driver_commision = get_settings('referral_commission_for_new_driver_from_referer_driver');
                    $new_user_commision   = get_settings('referral_commission_for_new_user_from_referer_driver');

                    $referral_condition = ReferralCondition::where('referral_type', 'dual_conditional_for_referrer_driver_and_new_driver_or_new_user_ride_count')->first();

                    $finalString = str_replace(
                        [
                            '{referred_driver_amount}',
                            '{user_ride_count}',
                            '{driver_ride_count}',
                            '{new_user_amount}',
                            '{new_driver_amount}'
                        ],
                        [
                            $referred_driver,
                            $user_ride_count,
                            $driver_ride_count,
                            $new_user_commision,
                            $new_driver_commision
                        ],
                        $referral_condition->description
                    );
                    //dd($finalString);
                }
                //earnings
                $driver_referral_earnings = get_settings('enable_driver_referral_condition_by_earning');
               
                if($driver_referral_earnings == 1){

                    $referred_driver = get_settings('referral_commission_amount_for_driver');
                    $user_spend_amount      = get_settings('driver_referral_condition_user_spent_amount');
                    $driver_earning_amount   = get_settings('driver_referral_condition_driver_earning_amount');
                    $new_driver_commision = get_settings('referral_commission_for_new_driver_from_referer_driver');
                    $new_user_commision   = get_settings('referral_commission_for_new_user_from_referer_driver');

                    $referral_condition = ReferralCondition::where('referral_type', 'dual_conditional_for_referrer_driver_and_new_driver_or_new_user_earnings')->first();

                   $finalString = str_replace(
                    [
                        '{referred_driver_amount}',
                        '{user_spent_amount}',
                        '{driver_earning_amount}',
                        '{new_driver_amount}',
                        '{new_user_amount}'
                    ],
                    [
                        $referred_driver,
                        $user_spend_amount,
                        $driver_earning_amount,
                        $new_driver_commision,
                        $new_user_commision
                    ],
                    $referral_condition->description
                    );
                  //dd($finalString);
                }
               
            }
            $referral_condition->description = $finalString;
            $referral_condition->translation_dataset = str_replace(
                ['{referred_driver_amount}', '{user_spent_amount}', '{driver_earning_amount}', '{new_driver_amount}', '{new_user_amount}', '{user_ride_count}', '{driver_ride_count}'],
                [$referred_driver, $user_spend_amount, $driver_earning_amount, $new_driver_commision, $new_user_commision, $user_ride_count, $driver_ride_count],
                $referral_condition->translation_dataset
            );

            $result  = fractal($referral_condition, new ReferralConditionTransformer)->toArray();

            $driver_banner  = fractal($driver_banner, new ReferralConditionTransformer)->toArray();

            return $this->respondSuccess(
            [
                'referral_content' => $result,
                'driver_banner' => $driver_banner,
            ],
            'referrals_description'
            );
        }   
        else{
            $driver_banner  = fractal($driver_banner, new ReferralConditionTransformer)->toArray();
            return $this->respondSuccess(
            [
                'referral_content' => null,
                'driver_banner' => $driver_banner,
            ],
        );
        }    
    }
}
