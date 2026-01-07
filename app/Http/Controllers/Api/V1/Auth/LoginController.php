<?php

namespace App\Http\Controllers\Api\V1\Auth;

use Socialite;
use App\Models\User;
use Illuminate\Http\Request;
use App\Base\Constants\Auth\Role;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Auth\SendLoginOTPRequest;
use App\Http\Requests\Auth\App\GenericAppLoginRequest;
use App\Http\Controllers\Web\Auth\LoginController as BaseLoginController;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Models\MobileOtp;
use App\Http\Requests\Auth\Registration\ValidateMobileOTPRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\Driver\OnlineOfflineController;
use Kreait\Firebase\Contract\Database;
use App\Models\Country;
use Illuminate\Database\QueryException;

class LoginController extends BaseLoginController
{
    /**
     * Login user and respond with access token and refresh token.
     * @group User-Login
     *
     * @param \App\Http\Requests\Auth\App\GenericAppLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @bodyParam email string optional email of the user entered
     * @bodyParam mobile string optional mobile of the user entered
     * @bodyParam password string optional password of the user entered
     * @bodyParam device_token string required fcm_token of the user entered
     *
     * @response
     * {
     *     "success": true,
     *     "message": "success",
     *     "access_token": "98|6jzNOIahjd2V72je0OeucPRuaRiIhJxWKXFvNVUr7027d348"
     * }
     */
    public function loginUser(GenericAppLoginRequest $request)
    {

        return $this->loginUserAccountApp($request, Role::USER);
    }

    /**
     * Login driver and respond with access token and refresh token.
     * @group User-Login
     *
     * @param \App\Http\Requests\Auth\App\GenericAppLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
      * @bodyParam email string optional email of the user entered
     * @bodyParam mobile string optional mobile of the user entered
     * @bodyParam social_unique_id string optional mobile of the user entered
     * @bodyParam password string optional password of the user entered
     * @bodyParam device_token string optional fcm_token for push notification
     * @bodyParam apn_token string optional fcm_token for ios push notification
     * @bodyParam login_by string required i.e android,ios
     *
     * @response 
     * {
     *     "success": true,
     *     "message": "success",
     *     "access_token": "98|6jzNOIahjd2V72je0OeucPRuaRiIhJxWKXFvNVUr7027d348"
     * }
    */
    public function loginDriver(GenericAppLoginRequest $request)
    {
        // $request->validate([
        //     'mobile_n' => 'required|decimal'
        //     ]);
    
        //     dd("m");

        if($request->has('role') && $request->role=='driver'){
            return $this->loginUserAccountApp($request, Role::DRIVER);
        }

        if($request->has('role') && $request->role=='owner'){
            return $this->loginUserAccountApp($request, Role::OWNER);
        }
            
        return $this->loginUserAccountApp($request, Role::DRIVER);

    }



    /**
    * Social auth
    * @bodyParam device_token string optional fcm_token for push notification
    * @bodyParam login_by string required i.e android,ios
    * @bodyParam oauth_token string required from social provider
    * @return \Illuminate\Http\JsonResponse
     *@hideFromAPIDocumentation
     *
     * @response {
    "token_type": "Bearer",
    "expires_in": 1296000,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjM4ZTE2N2YyNzlkM2UzZWEzODM5ZGNlMmY4YjdiNDQxYjMwZDQ0YmVlYjAzOWNmZjMzMmE2ZTc0ZDY1MDRiNmE3NjhhZWQzYWU5ZjE5MGUwIn0.eyJhdWQiOiIyIiwiacaP8zkCWTpzh8ZtWBUYVrPkYRWbwz-L5x6dx2d901Aq_7-LwlzPMtP0N93kVfFuLwK2RCzlVtcCTxZaUW9S7x3Y",
    "refresh_token": "def5020045b028faaca5890136e3a8d7c850fb6b95cf2f78698b2356e544ee567cef1efa4099eaea3e3738ba11c9baabb1188a3d49de316e4451f32cdaa6017ebb9ff748fdf43d84b4e796a0456c4125ebaeca7930491fe315e4b86adf7879992509667dd68eacc488bddb2cc005357cdab1da5f0582659eef11e06bf2447c1209f6c17c83453cd6fa6dd6d5d98ff7129a6d3f3509c6c99fba379ea4aee85c0eb89b5f648682484452219d1c592d80c3165657a519f790ba19ad347774c0a199"
}*/
    public function socialAuth(Request $request, $provider)
    {
        $oauth_token = $request->oauth_token;
        $social_user = Socialite::driver($provider)->userFromToken($oauth_token);

        $user = User::where('social_provider', $provider)->where('social_id', $social_user->id)->first();

        if (!$user) {
            $this->throwCustomException('user-not-found');
        }
        // Update User data with social provider
        $user->social_id = $social_user->id;
        $user->social_token = $social_user->token;
        $user->social_refresh_token = $social_user->refreshToken;
        $user->social_expires_in = $social_user->expiresIn;
        $user->social_avatar = $social_user->avatar;
        $user->social_avatar_original = $social_user->avatar_original;
        $user->login_by = $request->input('login_by');
        $user->fcm_token = $request->input('device_token')?:null;
        $user->save();
        $client_tokens = DB::table('oauth_clients')->where('personal_access_client', 1)->first();

        return $this->issueToken([
                'grant_type' => 'personal_access',
                'client_id' => $client_tokens->id,
                'client_secret' => $client_tokens->secret,
                'user_id' => $user->id,
                'scope' => [],
            ]);
    }




    /**
     * Logout the user based on their access token.
     * @group User-Login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success"}
     */
    public function logout(Request $request,Database $database)
    {   
        $user = auth()->user();

        $user->fcm_token=null;
        $user->save();
        if (access()->hasRole('driver')) {
            (new OnlineOfflineController($user->driver,$database))->toggle();
    
        }
        
        auth()->user()->tokens()->delete();

        return $this->respondSuccess();
    }

    /**
     * Send the OTP for user login.
     * @group User-Login
     * @param \App\Http\Requests\Auth\SendLoginOTPRequest $request
     * @bodyParam mobile string required mobile of the user entered
     * @return \Illuminate\Http\JsonResponse
     * @response {"success":true,"message":"success","uuid":"54e4ebe54er5e45re5ber54r5r5rr"}
     */
    public function sendUserLoginOTP(SendLoginOTPRequest $request)
    {
        $field = 'mobile';

        $mobile = $request->input($field);

        $user = $this->resolveUserFromMobile($mobile, Role::USER);

        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'User does not exist',
                'user_exists' => false,
                'action' => 'register',
                'mobile' => $mobile
            ]);
        }

        $this->validateUser($user, "User with that mobile number doesn't exist.", $field);

        if (!$user->createOTP()) {
            $this->throwSendOTPErrorException($field);
        }

        $otp = $user->getCreatedOTP();
        /**
        * Send OTP here
        * Temporary logger
        */
        // \Log::info("Login OTP for {$mobile} is : {$otp}");

        return $this->respondSuccess(['uuid' => $user->getCreatedOTPUuid()]);
    }

    /**
     * Validate the user model and their account status.
     *
     * @param \App\Models\User|null $user
     * @param string $message
     * @param string|null $field
     */
    protected function validateUser($user, $message, $field = null)
    {
        if (!$user) {
            $this->throwCustomException($message, $field);
        }

        if (!$user->isActive()) {
            $this->throwAccountDisabledException($field);
        }
    }


/**
 * Send Mobile Otp
 * @group User-Login
 * @bodyParam mobile string required mobile of the user entered
 * @bodyParam country_code string required Country Code of the user mobile
 * @response
 * {
 *     "success": true,
 *     "message": "success",
 * }
 */
public function mobileOtp(Request $request)
{
    // Validate mobile number is provided and not empty
    $mobile = $request->mobile;
    $country_code = $request->country_code;

    if (empty($mobile) || is_null($mobile)) {
        $this->throwCustomValidationException(['message' => 'Mobile number is required'], 'mobile');
    }

    // Validate mobile number format (basic validation)
    $mobile = trim($mobile);
    if (empty($mobile)) {
        $this->throwCustomValidationException(['message' => 'Mobile number cannot be empty'], 'mobile');
    }

    // Check if user exists
    $user = $this->user->belongsToRole(Role::USER)->where('mobile', $mobile)->first();




    // Generate OTP
    $otp = rand(100000, 999999);

    if(env('APP_FOR')=='demo'){
        $otp = '123456';
    }

    // Check if an OTP already exists for the given mobile number
    $existingOtp = MobileOtp::where('mobile', $mobile)->first();

    try {
        if ($existingOtp) {
            // Update the existing record with the new OTP
            $existingOtp->otp = $otp;
            $existingOtp->verified = false;
            $existingOtp->updated_at = now();
            $existingOtp->save();
        } else {
            // Create a new record if no existing record is found
            // Validate mobile is not null before creating
            if (empty($mobile) || is_null($mobile)) {
                $this->throwCustomValidationException(['message' => 'Mobile number is required to send OTP'], 'mobile');
            }
            
            MobileOtp::create(['mobile' => $mobile, 'otp' => $otp, 'verified' => false]);
        }
    } catch (QueryException $e) {
        $errorCode = $e->getCode();
        if ($errorCode == 23000) { // Integrity constraint violation
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, "Column 'mobile' cannot be null") !== false) {
                Log::error('Failed to create OTP record: Mobile number is null', [
                    'mobile' => $mobile,
                    'error' => $e->getMessage()
                ]);
                $this->throwCustomValidationException(['message' => 'Invalid mobile number provided. Please provide a valid mobile number.'], 'mobile');
            }
            // Handle other constraint violations
            Log::error('Failed to create OTP record: ' . $e->getMessage(), [
                'mobile' => $mobile,
                'error_code' => $errorCode
            ]);
            $this->throwCustomValidationException(['message' => 'Failed to process OTP request. Please try again.'], 'mobile');
        }
        // Re-throw if it's not a constraint violation we can handle
        throw $e;
    } catch (\Exception $e) {
        Log::error('Unexpected error while creating OTP record: ' . $e->getMessage(), [
            'mobile' => $mobile,
            'error' => $e->getMessage()
        ]);
        $this->throwCustomValidationException(['message' => 'An error occurred while processing your request. Please try again.'], 'mobile');
    }

    if(env('APP_FOR')=='demo'){
        return $this->respondSuccess();
    }

    $active_sms_gateway = get_active_sms_settings();

    if (method_exists($this, $method = $active_sms_gateway)) {
         return $this->{$method}($mobile, $otp, $country_code);
    }

    return $this->respondFailed();
}

/**
 * Auto-register user for OTP (without authentication)
 * This creates a user record so OTP can be sent
 *
 * @param \Illuminate\Http\Request $request
 * @param string $mobile
 * @param string $country_code
 * @return \App\Models\User
 */
protected function autoRegisterUserForOTP($request, $mobile, $country_code)
{
    // Validate inputs
    if (empty($mobile) || is_null($mobile)) {
        throw new \Exception('Mobile number is required for user registration');
    }

    if (!$country_code) {
        throw new \Exception('Country code is required for user registration');
    }

    // Validate mobile number format
    $mobile = trim($mobile);
    if (empty($mobile)) {
        throw new \Exception('Mobile number cannot be empty');
    }

    // Get country data - try with + prefix first, then without
    $country = null;
    
    // Remove + if present
    $clean_country_code = ltrim($country_code, '+');
    
    // Try to find country by dial_code or code
    $country = Country::where('active', true)
        ->where(function($query) use ($clean_country_code, $country_code) {
            $query->where('dial_code', $clean_country_code)
                  ->orWhere('dial_code', $country_code)
                  ->orWhere('code', $clean_country_code)
                  ->orWhere('code', $country_code);
        })
        ->first();

    // If still not found, try without active check
    if (!$country) {
        $country = Country::where(function($query) use ($clean_country_code, $country_code) {
            $query->where('dial_code', $clean_country_code)
                  ->orWhere('dial_code', $country_code)
                  ->orWhere('code', $clean_country_code)
                  ->orWhere('code', $country_code);
        })
        ->first();
    }

    if (!$country) {
        Log::error('Country not found for code: ' . $country_code);
        throw new \Exception('Unable to find country with code: ' . $country_code);
    }

    // Create user with minimal data
    $user_params = [
        'name' => 'User', // Default name, can be updated later
        'mobile' => $mobile, // Ensure mobile is not null
        'mobile_confirmed' => false, // Will be confirmed after OTP verification
        'country' => $country->id,
        'active' => true,
        'refferal_code' => str_random(6),
        'ride_otp' => env('APP_FOR') == 'demo' ? 0000 : rand(1111, 9999),
    ];

    // Add optional fields if provided
    if ($request->has('device_token')) {
        $user_params['fcm_token'] = $request->input('device_token');
    }

    if ($request->has('login_by')) {
        $user_params['login_by'] = $request->input('login_by');
    }

    if ($request->has('lang')) {
        $user_params['lang'] = $request->input('lang');
    }

    try {
        // Create user
        $user = $this->user->create($user_params);

        // Create Empty Wallet
        $user->userWallet()->create(['amount_added' => 0]);

        // Attach User role
        $user->attachRole(Role::USER);

        return $user;
    } catch (QueryException $e) {
        $errorCode = $e->getCode();
        if ($errorCode == 23000) { // Integrity constraint violation
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, "Column 'mobile' cannot be null") !== false) {
                Log::error('Failed to create user: Mobile number is null', [
                    'mobile' => $mobile,
                    'country_code' => $country_code,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Invalid mobile number provided. Mobile number cannot be null.');
            }
            // Handle duplicate entry
            if (strpos($errorMessage, "Duplicate entry") !== false) {
                Log::warning('User already exists with mobile: ' . $mobile);
                // Return existing user instead of throwing error
                return $this->user->belongsToRole(Role::USER)->where('mobile', $mobile)->first();
            }
        }
        // Log and re-throw other database errors
        Log::error('Failed to create user during auto-registration: ' . $e->getMessage(), [
            'mobile' => $mobile,
            'country_code' => $country_code,
            'error_code' => $errorCode,
            'user_params' => $user_params
        ]);
        throw $e;
    } catch (\Exception $e) {
        Log::error('Unexpected error during auto-registration: ' . $e->getMessage(), [
            'mobile' => $mobile,
            'country_code' => $country_code
        ]);
        throw $e;
    }
}

//sms India Hub
    public function enable_sms_india_hub($mobile,$otp,$country_code)
    {
// Log::info("sms India Hub");

        $apiKey = get_sms_settings('sms_india_hub_api_key');
        $sid = get_sms_settings('sms_india_hub_sid');
        // dd($apiKey);
        $msisdn = "91".$mobile;
        
        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";
        $fl = '0';
        $gwid = '2';


        $response = Http::get('http://cloud.smsindiahub.in/vendorsms/pushsms.aspx', [
            'APIKey' => $apiKey,
            'msisdn' => $msisdn,
            'sid' => $sid,
            'msg' => $msg,
            'fl' => $fl,
            'gwid' => $gwid,
        ]);

        $result = json_decode($response->body(),true);


        if (isset($result['ErrorCode'])) {
            // log the error for debugging
            \Log::error('SMS API Error', $result);

            return response()->json([
                'success' => false,
                'message' => $result['ErrorMessage'] ?? 'Unknown error',
                'error_code' => $result['ErrorCode'] ?? null,
            ], 400); // or 422 depending on your case
        }


        Log::info('India hub API Response', [
            'status'  => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body'    => $result,
        ]);
        return $this->respondSuccess();
     } 
//sparrow
    public function enable_sparrow($mobile,$otp,$country_code)
    {
        /*Note
        #make sure you updated server Ip addres in saprrow sms gateway  portal
        */
        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";

        $token = get_sms_settings('sparrow_sender_id');
        $id = get_sms_settings('sparrow_token');
        // dd($id);

        // @TODO implement send sms
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.sparrowsms.com/v2/sms/');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "token=$token.M4Wm&from=$id&to=".$mobile."&text=".$msg);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        $server_output = curl_exec($ch);

        curl_close($ch); 
// dd($ch);
        $status = json_decode($server_output);
            // dd($status->status);

        if($status->status!=200)
        {

          return response()->json(['success'=>false,'message'=>$status->message]);

        }else{

           return $this->respondSuccess();  
        } 
        return $this->respondFailed();

    }
//twilio
//twilio
    public function enable_twilio($mobile,$otp,$country_code)
    {


        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";

        $twilioSid = get_sms_settings('twilio_sid');
        $twilioToken = get_sms_settings('twilio_token');
        $twilioPhoneNumber = get_sms_settings('twilio_mobile_number');

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json";

        $postData = [
            'From' => $twilioPhoneNumber,
            'To' => $country_code.$mobile,
            'Body' => $msg,
        ];
        

        $postFields = http_build_query($postData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_USERPWD, "{$twilioSid}:{$twilioToken}");

        $response = curl_exec($ch);
       
        curl_close($ch);

        $status = json_decode($response);
            // dd($status->status);

        if($status->status!= "queued")
        {

          return response()->json(['success'=>false,'message'=>$status->message]);

        }else{

           return $this->respondSuccess();  
        }




    } 

//kudi sms 
    public function enable_kudi_sms_api_key($mobile,$otp,$country_code)
    {


        $msg = "Dear User, your wait is finally over! Your account OTP is $otp.";

        $kudiApiKey = get_sms_settings('kudi_sms_api_key');
        $kudiSenderId = get_sms_settings('kudi_sms_sender_id');


        //  $kudiApiKey = 'HQwOrLRlB2C7YKNqgUTxaD9i3WzFbo0t6jyJ4vsP8mVk5ZMGecdXAh1fSunEpI';
        // $kudiSenderId = 'Rovv Africa';

        $url = "https://my.kudisms.net/api/corporate";

        $postData = [
            'token' => $kudiApiKey,
            'senderID' => $kudiSenderId,
            'Body' => $msg,
            'recipients'  => $country_code.$mobile,      // Single recipient (your phone)
            'message'    => "Your OTP is $otp", // Text containing the OTP
            'otp'        => $otp,
        ];


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($postData),
        ));

        $response = curl_exec($curl); 

        $err = curl_error($curl);

        // Close the cURL session
        curl_close($curl);
        $status = json_decode($response);

        // Return the response or the error
        if ($status->status == 'error') {
            return response()->json(["error" => $status]);
        } else {
           return $this->respondSuccess();
        }
        




    }

//smsala
    public function enable_sms_ala($mobile,$otp,$country_code)
    {

        $apiKey = get_sms_settings('smsala_api_key');
        $apiPassword = get_sms_settings('smsala_api_password');
        $smsType = "P";  
        $encoding = "T";  
        $senderId = get_sms_settings('smsala_sender_id');
        $phoneNumber =$country_code.$mobile;  
        $mag = "Dear User, your wait is finally over! Your OTP is. $otp"; // Replace with the message you want to send

        $client = new Client();

            $response = $client->get('http://api.smsala.com/api/SendSMS', [
                'query' => [
                    'api_id' => $apiKey,
                    'api_password' => $apiPassword,
                    'sms_type' => $smsType,
                    'encoding' => $encoding,
                    'sender_id' => $senderId,
                    'phonenumber' => $mobile,
                    'textmessage' => $mag,
                ]
            ]);

            $body = $response->getBody();
            $content = $body->getContents();

        return $this->respondSuccess();    

    }

//msg91    
    public function enable_msg91($mobile, $otp ,$country_code)
    {
        // MSG91 API details
        
        $template_id = get_sms_settings('msg91_template_id'); 
        $auth_key = get_sms_settings('msg91_auth_key'); 

        // Ensure the mobile number is prefixed with the country code
        $mobile = $country_code. $mobile;

        // Initialize cURL session
        $curl = curl_init();

        // Prepare the data to be sent in the POST request
        $postData = [
            "template_id" => $template_id,
            "short_url" => "0",
            "recipients" => [
                [
                    "mobiles" => $mobile,
                    "var" => $otp  // Ensure 'var' matches the placeholder in the template
                ]
            ]
        ];

        // Set the cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.msg91.com/api/v5/flow/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "authkey: $auth_key",
                "content-type: application/json"
            ],
        ]);

        // Execute the cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Close the cURL session
        curl_close($curl);

        // Return the response or the error
        if ($err) {
            return response()->json(["error" => $err], 500);
        } else {
            return response()->json(json_decode($response, true));
        }
    }

    // 2Factor SMS Gateway
    public function enable_twofactor($mobile, $otp, $country_code)
    {
        $apiKey = get_sms_settings('twofactor_api_key');
        $senderId = get_sms_settings('twofactor_sender_id');
        
        // Validate required settings
        if (empty($apiKey) || empty($senderId)) {
            \Log::error('2Factor SMS Configuration Missing', [
                'api_key_set' => !empty($apiKey),
                'sender_id_set' => !empty($senderId)
            ]);
            return response()->json([
                'success' => false,
                'message' => '2Factor SMS configuration is missing. Please check API key and Sender ID.'
            ], 400);
        }
        
        // Validate mobile number
        if (empty($mobile)) {
            \Log::error('2Factor SMS: Mobile number is empty');
            return response()->json([
                'success' => false,
                'message' => 'Mobile number is required'
            ], 400);
        }
        
        // Format phone number: remove + sign, spaces, and ensure numeric only
        $country_code = ltrim($country_code ?? '', '+');
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        $country_code = preg_replace('/[^0-9]/', '', $country_code);
        
        // Combine country code and mobile number
        $mobileNumber = $country_code . $mobile;
        
        // Validate final phone number
        if (empty($mobileNumber) || strlen($mobileNumber) < 10) {
            \Log::error('2Factor SMS: Invalid phone number format', [
                'mobile' => $mobile,
                'country_code' => $country_code,
                'final_number' => $mobileNumber
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format'
            ], 400);
        }
        
        // Use the approved template with {#var#} placeholder
        $template = '{#var#} is the verification code to log in to your AairGo account. Please DO NOT SHARE this code with anyone. -Aairgo';
        $msg = str_replace('{#var#}', $otp, $template);
        
        // Build the API URL
        $apiUrl = "https://2factor.in/API/V1/{$apiKey}/ADDON_SERVICES/SEND/TSMS";
        
        // Prepare the form parameters
        $postData = [
            'From' => $senderId,
            'To' => $mobileNumber,
            'Msg' => $msg,
        ];
        
        // Log the request for debugging (without sensitive data)
        \Log::info('2Factor SMS Request', [
            'api_url' => $apiUrl,
            'to' => $mobileNumber,
            'from' => $senderId,
            'message_length' => strlen($msg),
            'method' => 'POST'
        ]);
        
        // Initialize cURL session
        $curl = curl_init();
        
        // Set the cURL options for POST request
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        
        // Execute the cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Close the cURL session
        curl_close($curl);
        
        // Handle errors
        if ($err) {
            \Log::error('2Factor SMS API Error', ['error' => $err, 'http_code' => $httpCode]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $err
            ], 500);
        }
        
        // Decode the response
        $result = json_decode($response, true);
        
        // Log the full response for debugging
        \Log::info('2Factor SMS Response', [
            'http_code' => $httpCode,
            'response' => $result
        ]);
        
        // Check the response status
        $status = data_get($result, 'Status');
        
        if (mb_strtolower($status) === 'success') {
            return $this->respondSuccess();
        } else {
            $details = data_get($result, 'Details', data_get($result, 'message', 'Failed to send SMS via 2Factor'));
            \Log::error('2Factor SMS API Error', [
                'response' => $result,
                'status' => $status,
                'details' => $details
            ]);
            return response()->json([
                'success' => false,
                'message' => $details
            ], 400);
        }
    }


//validate-OTP
/**
 * Validate Mobile Otp and Auto-Register if user doesn't exist
 * @group User-Login
 * 
 * @bodyParam mobile string required mobile of the user entered
 * @bodyParam otp string required OTP code
 * @bodyParam country_code string required Country Code of the user mobile
 * @bodyParam device_token string optional fcm_token for push notification
 * @bodyParam login_by string optional i.e android,ios
 * @response
 * {
 *     "success": true,
 *     "message": "success",
 *     "access_token": "token_here"
 * }
 */
    public function validateSmsOtp(ValidateMobileOTPRequest $request)
    {
        $otp = $request->otp;
        $mobile = $request->mobile;
        $country_code = $request->input('country_code');

        $verify_otp = MobileOtp::where('mobile', $mobile)->where('otp', $otp)->first();

        if (env('APP_FOR') == 'demo' && $otp == '123456') {
            // For demo, skip OTP verification
        } elseif (!$verify_otp) {
            $this->throwCustomValidationException(['message' => "The otp provided is invalid"]);
        } else {
            $verify_otp->update(['verified' => true]);
        }

        // Check if user exists
        $user = $this->user->belongsToRole(Role::USER)->where('mobile', $mobile)->first();

        if ($user) {
            // User exists, login them
            if (!$user->isActive()) {
                $this->throwAccountDisabledException('mobile');
            }

            // Update device token if provided
            if ($request->has('device_token')) {
                $user->fcm_token = $request->input('device_token');
                $user->save();
            }

            // Always authenticate in web session for web access
            auth('web')->login($user);
            session(['module' => "transport"]);

            // Return success response with login indication
            $token = $user->createToken('Dilip-iphone')->plainTextToken;
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'access_token' => $token,
                'user' => $user,
                'user_exists' => true,
                'action' => 'login'
            ]);
        }

        // User doesn't exist, return response to open registration screen
        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully. Please complete registration.',
            'user_exists' => false,
            'action' => 'register',
            'mobile' => $mobile,
            'country_code' => $country_code
        ]);
    }

   /**
    * Auto-register user with mobile number and OTP
    *
    * @param \Illuminate\Http\Request $request
    * @param string $mobile
    * @return \Illuminate\Http\JsonResponse
    */
   protected function autoRegisterUser($request, $mobile)
   {
        $country_code = $request->input('country_code');
        
        // Get country data
        $country = \App\Models\Country::where('active', true)
            ->where(function($query) use ($country_code) {
                $query->where('dial_code', $country_code)
                      ->orWhere('code', $country_code);
            })
            ->first();

        if (!$country) {
            $this->throwCustomException('unable to find country');
        }

        // Create user with minimal data
        $user_params = [
            'name' => 'User', // Default name, can be updated later
            'mobile' => $mobile,
            'mobile_confirmed' => true,
            'country' => $country->id,
            'active' => true,
            'refferal_code' => str_random(6),
            'ride_otp' => env('APP_FOR') == 'demo' ? 0000 : rand(1111, 9999),
        ];

        // Add optional fields if provided
        if ($request->has('device_token')) {
            $user_params['fcm_token'] = $request->input('device_token');
        }

        if ($request->has('login_by')) {
            $user_params['login_by'] = $request->input('login_by');
        }

        if ($request->has('lang')) {
            $user_params['lang'] = $request->input('lang');
        }

        // Create user
        $user = $this->user->create($user_params);

        // Create Empty Wallet
        $user->userWallet()->create(['amount_added' => 0]);

        // Attach User role
        $user->attachRole(Role::USER);

        // Fire registration event
        event(new \App\Events\Auth\UserRegistered($user));

        // Always authenticate in web session for web access
        // This allows users to access protected web routes after OTP registration
        // Token is also returned for mobile apps, so web session auth doesn't interfere
        // Web middleware is now applied to this route, so session is properly handled
        auth('web')->login($user);
        session(['module' => "transport"]);
        
        // Send admin notification
        $url = route('users.view-profile', ['user' => $user->id]);
        $uuid = uniqid();
        $notificationData = [
            'id' => $uuid,
            'body' => "New User Registered",
            'title' => "New User Registered",
            'read' => false,
            'updated_at' => round(microtime(true) * 1000),
            'url' => $url
        ];
        
        try {
            $database = app(\Kreait\Firebase\Contract\Database::class);
            $database->getReference('admin-notification/' . $uuid)->set($notificationData);
        } catch (\Exception $e) {
            Log::error('Failed to send Firebase notification: ' . $e->getMessage());
        }

        // Authenticate and respond
        return $this->authenticateAndRespond($user, $request, $needsToken=true);
   }

 





    /**
     * Override loginUserWithMobile to handle non-existent users gracefully
     */
    protected function loginUserWithMobile($request, $role, $needsToken = true, array $conditions = [])
    {
        $user = null;
        $identifier = $this->getLoginIdentifier();
        $emailOrUsername = $request->input($identifier);

        if (method_exists($this, $method = 'resolveUserFrom' . Str::studly($identifier))) {
            $user = $this->{$method}($emailOrUsername, $role);
        }

        if (!$user) {
            // Return response prompting registration instead of generic credential error
             return response()->json([
                'success' => true,
                'message' => 'User does not exist',
                'user_exists' => false,
                'action' => 'register',
                'data' => ['mobile' => $emailOrUsername]
            ]); 
        }

        if (!$user->isActive() || !$this->validateChecks($user, $conditions, $identifier)) {
            $this->throwAccountDisabledException($identifier);
        }

        return $this->authenticateAndRespond($user, $request, $needsToken);
    }
}
