<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request as ValidatorRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use Log;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Payment\UserWallet;
use App\Models\Payment\UserWalletHistory;
use App\Models\Payment\DriverWallet;
use App\Models\Payment\DriverWalletHistory;
use App\Models\Payment\OwnerWallet;
use App\Models\Payment\OwnerWalletHistory;
use App\Base\Constants\Masters\PushEnums;
use App\Jobs\Notifications\SendPushNotification;
use App\Base\Constants\Setting\Settings;
use App\Models\Request\Request as RequestModel;
use App\Base\Constants\Masters\WalletRemarks;
use App\Base\Constants\Auth\Role;
use Kreait\Firebase\Contract\Database;

class AirtelController extends Controller
{
    protected $database;
    protected $guzzle;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->guzzle = new GuzzleClient(['timeout' => 30]);
    }

    /**
     * Show payment page (you can reuse Airtel view or create a new view)
     */
    public function airtel(ValidatorRequest $request)
    {
        $amount = ($request->input('amount') * 100 ); // if your frontend expects *100
        $payment_for = $request->input('payment_for');
        $user_id = $request->input('user_id');
        $request_id = $request->input('request_id');
        $plan_id = $request->input('plan_id');

        $user = User::find($user_id);
        $currency = $user->countryDetail->currency_code ?? "MWK";

        return view('airtel.airtel', compact('amount', 'payment_for', 'currency', 'user_id','request_id','plan_id'));
    }

    /**
     * Main checkout: get token, call Airtel collection API, and handle response.
     */
//     public function airtelCheckout(ValidatorRequest $request)
//     {
        
//         // dd($request->all());
//         $request->validate([
//             'amount' => 'required|numeric|min:1',
//             'currency' => 'nullable|string',
//             'payment_for' => 'required|string',
//             'user_id' => 'required|integer',
//         ]);
//         // dd($request);
//         $amount = $request->input('amount');
//         $currency = $request->input('currency') ?? 'MWK';
//         $payment_for = $request->input('payment_for');
//         $user_id = $request->input('user_id');
//         $plan_id = $request->input('plan_id');
//         $request_id = $request->input('request_id');
//         $country = $request->input('country') ?? 'MW';
//         $msisdn = $request->input('msisdn') ?? $request->input('mobile') ?? '256782123456';
//         $transactionId = (string) Str::uuid();

//         try {
//             /**
//              * STEP 1: Determine environment and fetch client credentials
//              */
//             if (get_payment_settings('airtel_environment') == 'test') {
//                 $clientId = get_payment_settings('airtel_test_client_id');
//                 $clientSecret = get_payment_settings('airtel_test_client_secret_key');
//                 $baseUrl = 'https://openapiuat.airtel.africa'; // sandbox endpoint works with same base
//             } else {
//                 $clientId = get_payment_settings('airtel_live_client_id');
//                 $clientSecret = get_payment_settings('airtel_live_client_secret_key');
//                 $baseUrl = 'https://openapiuat.airtel.africa';
//             }
// // dd($clientId,$clientSecret);
//             /**
//              * STEP 2: Get Airtel access token
//              */
//             $authResponse = $this->guzzle->post("$baseUrl/auth/oauth2/token", [
//                 'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
//                 'json' => [
//                     'client_id' => $clientId,
//                     'client_secret' => $clientSecret,
//                     'grant_type' => 'client_credentials'
//                 ],
//             ]);

//             $authBody = json_decode($authResponse->getBody()->getContents(), true);
//             $token = $authBody['access_token'] ?? null;

//             if (!$token) {
//                 Log::error('Airtel auth failed', ['response' => $authBody]);
//                 return response()->json(['error' => 'Payment gateway authentication failed'], 500);
//             }

//             /**
//              * STEP 3: Create collection payment request
//              */
//             $collectionUrl = "$baseUrl/merchant/v1/payments/";

//             $airtelBody = [
//                 // 'reference' => $productname,
//                 'subscriber' => [
//                     'country' => $country,
//                     'currency' => $currency,
//                     'msisdn' => $msisdn,
//                 ],
//                 'transaction' => [
//                     'amount' => (int) $amount,
//                     'country' => $country,
//                     'currency' => $currency,
//                     'id' => $transactionId,
//                 ],
//             ];

//             $headers = [
//                 'Accept' => 'application/json',
//                 'Content-Type' => 'application/json',
//                 'Authorization' => 'Bearer ' . $token,
//                 'X-Country' => $country,
//                 'X-Currency' => $currency,
//             ];

//             $response = $this->guzzle->post($collectionUrl, [
//                 'headers' => $headers,
//                 'json' => $airtelBody,
//             ]);
//             // dd($response->getBody()->getContents());
//             $body = json_decode($response->getBody()->getContents(), true);
//             Log::info('Airtel Payment Response', ['body' => $body]);

//             /**
//              * STEP 4: Check transaction success
//              */
//             $success = false;
//             if (
//                 (isset($body['status']['success']) && $body['status']['success'] === true) ||
//                 (isset($body['data']['transaction']['status']) && strtoupper($body['data']['transaction']['status']) === 'SUCCESS')
//             ) {
//                 $success = true;
//             }

//             if ($success) {
//                 $airtelTxnId = $body['data']['transaction']['id'] ?? $transactionId;

//                 // Your helper or function to record success (like wallet update / subscription)
//                 return $this->handleSuccessAfterPayment([
//                     'payment_for' => $payment_for,
//                     'currency' => $currency,
//                     'amount' => $amount,
//                     'user_id' => $user_id,
//                     'request_id' => $request_id,
//                     'plan_id' => $plan_id,
//                     'transaction_id' => $airtelTxnId,
//                     'gateway' => 'airtel'
//                 ]);
//             }

//             Log::warning('Airtel Payment Failed', ['body' => $body]);
//             return response()->json(['error' => 'Payment not successful'], 400);

//         } catch (BadResponseException $e) {
//             // dd( $e->getResponse()?->getBody()?->getContents());
//             Log::error('Airtel API BadResponse: ' . $e->getMessage(), [
//                 'response' => $e->getResponse()?->getBody()?->getContents()
//             ]);
//             dd('Airtel Payment Exception: ' . $e->getMessage());
//             return response()->json(['error' => 'Payment gateway error'], 500);

//         } catch (\Exception $e) {
//             Log::error('Airtel Payment Exception: ' . $e->getMessage());
//             return response()->json(['error' => 'Payment processing error'], 500);
//         }
//     }

    public function airtelCheckout(ValidatorRequest $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string',
            'payment_for' => 'required|string',
            'user_id' => 'required|integer',
        ]);

        $amount = (int) $request->input('amount');
        $currency = $request->input('currency') ?? 'MWK';
        $payment_for = $request->input('payment_for');
        $user_id = $request->input('user_id');
        $plan_id = $request->input('plan_id');
        $request_id = $request->input('request_id');
        $country = $request->input('country') ?? 'MW';
        $msisdn = $request->input('msisdn') ?? $request->input('mobile') ?? '999959580'; // Test number
        $transactionId = (string) Str::uuid();

        try {
            /**
             * STEP 1: Environment setup
             */
            if (get_payment_settings('airtel_environment') == 'test') {
                $clientId = get_payment_settings('airtel_test_client_id');
                $clientSecret = get_payment_settings('airtel_test_client_secret_key');
                $baseUrl = 'https://openapiuat.airtel.africa'; // UAT endpoint
            } else {
                $clientId = get_payment_settings('airtel_live_client_id');
                $clientSecret = get_payment_settings('airtel_live_client_secret_key');
                $baseUrl = 'https://openapi.airtel.africa';
            }

            /**
             * STEP 2: Get OAuth token
             */
            $authResponse = $this->guzzle->post("$baseUrl/auth/oauth2/token", [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'client_credentials'
                ],
            ]);

            $authBody = json_decode($authResponse->getBody()->getContents(), true);
            $token = $authBody['access_token'] ?? null;

            if (!$token) {
                Log::error('Airtel auth failed', ['response' => $authBody]);
                return response()->json(['error' => 'Payment gateway authentication failed'], 500);
            }

            /**
             * STEP 3: Create payment request
             */
            $collectionUrl = "$baseUrl/merchant/v1/payments/";

            $airtelBody = [
                "reference" => "Testing transaction",
                "subscriber" => [
                    "country" => $country,
                    "currency" => $currency,
                    "msisdn" => $msisdn
                ],
                "transaction" => [
                    "amount" => $amount,
                    "country" => $country,
                    "currency" => $currency,
                    "id" => $transactionId
                ]
            ];

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'X-Country' => $country,
                'X-Currency' => $currency,
            ];

            $response = $this->guzzle->post($collectionUrl, [
                'headers' => $headers,
                'json' => $airtelBody,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            Log::info('Airtel Payment Response', ['body' => $body]);

            /**
             * STEP 4: Check success
             */
            if (
                isset($body['status']['success']) &&
                $body['status']['success'] === true &&
                isset($body['data']['transaction']['status']) &&
                stripos($body['data']['transaction']['status'], 'Success') !== false
            ) {
                $airtelTxnId = $body['data']['transaction']['id'] ?? $transactionId;

                // Handle wallet/subscription success
                return $this->handleSuccessAfterPayment([
                    'payment_for' => $payment_for,
                    'currency' => $currency,
                    'amount' => $amount,
                    'user_id' => $user_id,
                    'request_id' => $request_id,
                    'plan_id' => $plan_id,
                    'transaction_id' => $airtelTxnId,
                    'gateway' => 'airtel'
                ]);
            }

            Log::warning('Airtel Payment Failed', ['body' => $body]);
            return response()->json([
                'error' => $body['status']['message'] ?? 'Payment not successful',
                'details' => $body
            ], 400);

        } catch (BadResponseException $e) {
            $errorBody = $e->getResponse()?->getBody()?->getContents();
            Log::error('Airtel API BadResponse: ' . $e->getMessage(), ['response' => $errorBody]);
            return response()->json([
                'error' => 'Payment gateway error',
                'details' => json_decode($errorBody, true)
            ], 500);

        } catch (\Exception $e) {
            Log::error('Airtel Payment Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Payment processing error'], 500);
        }
    }
    /**
     * Handle what happens after payment is successful.
     * This is adapted from your AirtelCheckoutSuccess() logic: wallet update, subscription, and request payment flow.
     * The method returns the same success view you used for Airtel.
     */
    protected function handleSuccessAfterPayment(array $params)
    {
        $payment_for = $params['payment_for'];
        $currency = $params['currency'];
        $amount = $params['amount'];
        $user_id = $params['user_id'];
        $request_id = $params['request_id'];
        $plan_id = $params['plan_id'];
        $transaction_id = $params['transaction_id'] ?? null;

        $web_booking_value = 0;

        if ($payment_for == "wallet") {

            $request_id = null;
            $user = User::find($user_id);

            if ($user->hasRole('user')) {
                $wallet_model = new UserWallet();
                $wallet_add_history_model = new UserWalletHistory();
                $wallet_owner_id = $user->id;
            } elseif ($user->hasRole('driver')) {
                $wallet_model = new DriverWallet();
                $wallet_add_history_model = new DriverWalletHistory();
                $wallet_owner_id = $user->driver->id;
            } else {
                $wallet_model = new OwnerWallet();
                $wallet_add_history_model = new OwnerWalletHistory();
                $wallet_owner_id = $user->owner->id;
            }

            $user_wallet = $wallet_model::firstOrCreate(['user_id' => $wallet_owner_id]);
            $user_wallet->amount_added += $amount;
            $user_wallet->amount_balance += $amount;
            $user_wallet->save();
            $user_wallet->fresh();

            $wallet_add_history_model::create([
                'user_id' => $wallet_owner_id,
                'amount' => $amount,
                'transaction_id' => $transaction_id,
                'remarks' => WalletRemarks::MONEY_DEPOSITED_TO_E_WALLET,
                'is_credit' => true
            ]);

            // Push notification logic (same as Airtel controller)
            $notification = \DB::table('notification_channels')
                ->where('topics', 'User Wallet Amount')
                ->first();

            if ($notification && $notification->push_notification == 1) {
                $userLang = $user->lang ?? 'en';
                $translation = \DB::table('notification_channels_translations')
                    ->where('notification_channel_id', $notification->id)
                    ->where('locale', $userLang)
                    ->first();

                if (!$translation) {
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', 'en')
                        ->first();
                }

                $title = $translation->push_title ?? $notification->push_title;
                $body = strip_tags($translation->push_body ?? $notification->push_body);
                dispatch(new SendPushNotification($user, $title, $body));
            }

            // Return view similar to AirtelCheckoutSuccess
            if ($user->hasRole(Role::USER)) {
                $result = fractal($user_wallet, new \App\Transformers\Payment\WalletTransformer);
            } elseif ($user->hasRole(Role::DRIVER)) {
                $result = fractal($user_wallet, new \App\Transformers\Payment\DriverWalletTransformer);
            } else {
                $result = fractal($user_wallet, new \App\Transformers\Payment\OwnerWalletTransformer);
            }

        } elseif ($payment_for == 'subscription') {
            // replicate subscription logic from Airtel
            $plan = \App\Models\Admin\Subscription::find($plan_id);
            $user = User::find($user_id);

            $params['transaction_id'] = Str::random(6);
            $driver_wallet = $user->driver->DriverWallet;
            $driver_wallet->amount_spent += $amount;
            $driver_wallet->save();

            $user->driver->driverWalletHistory()->create([
                'amount' => $amount,
                'transaction_id' => Str::random(6),
                'remarks' => WalletRemarks::SUBSCRIPTION_FEE,
                'is_credit' => false,
            ]);

            $driver = $user->driver;
            $expire_at = Carbon::parse(now())->addDay($plan->subscription_duration)->toDateTimeString();
            $params = [
                'driver_id' => $driver->id,
                'subscription_id' => $plan_id,
                'amount' => $amount,
                'payment_opt' => 0,
                'expired_at' => $expire_at,
            ];
            $params['transaction_id'] = Str::random(6);
            $params['subscription_type'] = 1;
            $subscription = \App\Models\Admin\SubscriptionDetail::create($params);
            $driver->update([
                'is_subscribed' => true,
                'subscription_detail_id' => $subscription->id,
            ]);

            $result = fractal($driver_wallet, new \App\Transformers\Payment\DriverWalletTransformer);

        } else {
            // treat as trip payment
            $request_detail = RequestModel::where('id', $request_id)->first();
            $web_booking_value = $request_detail->web_booking;
            $request_detail->update(['is_paid' => true]);

            $driver_commision = $request_detail->requestBill->driver_commision;
            if ($request_detail->driverDetail->owner()->exists()) {
                $wallet_model = new OwnerWallet();
                $wallet_add_history_model = new OwnerWalletHistory();
                $user_id_for_wallet = $request_detail->driverDetail->owner_id;
            } else {
                $wallet_model = new DriverWallet();
                $wallet_add_history_model = new DriverWalletHistory();
                $user_id_for_wallet = $request_detail->driver_id;
            }

            $user_wallet = $wallet_model::firstOrCreate(['user_id' => $user_id_for_wallet]);
            $user_wallet->amount_added += $driver_commision;
            $user_wallet->amount_balance += $driver_commision;
            $user_wallet->save();
            $user_wallet->fresh();

            $wallet_add_history_model::create([
                'user_id' => $user_id_for_wallet,
                'amount' => $driver_commision,
                'transaction_id' => $transaction_id,
                'remarks' => WalletRemarks::TRIP_COMMISSION_FOR_DRIVER,
                'is_credit' => true
            ]);

            $additional_charges_amount = $request_detail->requestBill->additional_charges_amount;
            if ($additional_charges_amount > 0) {
                if ($request_detail->driverDetail->owner()->exists()) {
                    $owner_wallet = $request_detail->driverDetail->owner->ownerWalletDetail;
                    $owner_wallet->amount_added += $additional_charges_amount;
                    $owner_wallet->amount_balance += $additional_charges_amount;
                    $owner_wallet->save();

                    $owner_wallet_history = $request_detail->driverDetail->owner->ownerWalletHistoryDetail()->create([
                        'amount' => $additional_charges_amount,
                        'transaction_id' => Str::random(6),
                        'remarks' => WalletRemarks::ADDITIONAL_CHARGE_AMOUNT,
                        'is_credit' => true
                    ]);
                } else {
                    $driver_wallet = $request_detail->driverDetail->driverWallet;
                    $driver_wallet->amount_added += $additional_charges_amount;
                    $driver_wallet->amount_balance += $additional_charges_amount;
                    $driver_wallet->save();

                    $driver_wallet_history = $request_detail->driverDetail->driverWalletHistory()->create([
                        'amount' => $additional_charges_amount,
                        'transaction_id' => Str::random(6),
                        'remarks' => WalletRemarks::ADDITIONAL_CHARGE_AMOUNT,
                        'is_credit' => true
                    ]);
                }
            }

            $notification = \DB::table('notification_channels')
                ->where('topics', 'User Wallet Amount')
                ->first();

            if ($notification && $notification->push_notification == 1) {
                $userLang = $request_detail->driverDetail->user->lang ?? 'en';
                $translation = \DB::table('notification_channels_translations')
                    ->where('notification_channel_id', $notification->id)
                    ->where('locale', $userLang)
                    ->first();

                if (!$translation) {
                    $translation = \DB::table('notification_channels_translations')
                        ->where('notification_channel_id', $notification->id)
                        ->where('locale', 'en')
                        ->first();
                }

                $title = $translation->push_title ?? $notification->push_title;
                $body = strip_tags($translation->push_body ?? $notification->push_body);
                dispatch(new SendPushNotification($request_detail->driverDetail->user, $title, $body));
            }

            if ($request_detail->promo_id) {
                $discount = $request_detail->requestBill->promo_discount;
                if ($discount > 0) {
                    if ($request_detail->driverDetail->owner()->exists()) {
                        $owner_wallet = $request_detail->driverDetail->owner->ownerWalletDetail;
                        $owner_wallet->amount_added += $discount;
                        $owner_wallet->amount_balance += $discount;
                        $owner_wallet->save();

                        $owner_wallet_history = $request_detail->driverDetail->owner->ownerWalletHistoryDetail()->create([
                            'amount' => $discount,
                            'transaction_id' => Str::random(6),
                            'remarks' => WalletRemarks::DISCOUNTED_AMOUNT,
                            'is_credit' => true
                        ]);
                    } else {
                        $driver_wallet = $request_detail->driverDetail->driverWallet;
                        $driver_wallet->amount_added += $discount;
                        $driver_wallet->amount_balance += $discount;
                        $driver_wallet->save();

                        $driver_wallet_history = $request_detail->driverDetail->driverWalletHistory()->create([
                            'amount' => $discount,
                            'transaction_id' => Str::random(6),
                            'remarks' => WalletRemarks::DISCOUNTED_AMOUNT,
                            'is_credit' => true
                        ]);
                    }
                }
            }

            $this->database->getReference('requests/'.$request_detail->id)
                ->update(['is_paid' => 1, 'is_user_paid' => true, 'modified_by_driver' => Database::SERVER_TIMESTAMP]);
        }

        // Return the existing success view to keep UI identical to Airtel
        return view('success', ['success'], compact('web_booking_value','request_id'));
    }

    /**
     * Simple failure view handler
     */
    public function airtelCheckoutError(ValidatorRequest $request)
    {
        return view('failure', ['failure']);
    }

    /**
     * Obtain Airtel OAuth token. Cached for expires_in seconds.
     */
    // protected function getAirtelToken()
    // {
    //     // Try cached
    //     if (Cache::has('airtel_access_token')) {
    //         return Cache::get('airtel_access_token');
    //     }

    //     $env = env('AIRTEL_ENV', 'uat');

    //     $clientIdKey = $env === 'prod' ? 'AIRTEL_LIVE_CLIENT_ID' : 'AIRTEL_TEST_CLIENT_ID';
    //     $clientSecretKey = $env === 'prod' ? 'AIRTEL_LIVE_CLIENT_SECRET' : 'AIRTEL_TEST_CLIENT_SECRET';
    //     $authUrl = $env === 'prod' ? env('AIRTEL_LIVE_AUTH_URL') : env('AIRTEL_UAT_AUTH_URL');

    //     $clientId = env($clientIdKey);
    //     $clientSecret = env($clientSecretKey);

    //     if (!$clientId || !$clientSecret || !$authUrl) {
    //         Log::error('Airtel credentials or URL missing in .env');
    //         return null;
    //     }

    //     try {
    //         $resp = $this->guzzle->post($authUrl, [
    //             'headers' => [
    //                 'Content-Type' => 'application/json',
    //                 'Accept' => '*/*',
    //             ],
    //             'json' => [
    //                 'client_id' => $clientId,
    //                 'client_secret' => $clientSecret,
    //                 'grant_type' => 'client_credentials'
    //             ]
    //         ]);

    //         $body = json_decode($resp->getBody()->getContents(), true);

    //         $token = $body['access_token'] ?? null;
    //         $expires = isset($body['expires_in']) ? (int)$body['expires_in'] : 180;

    //         if ($token) {
    //             // store token slightly less than expiry
    //             $ttl = max(60, $expires - 10);
    //             Cache::put('airtel_access_token', $token, $ttl);
    //             return $token;
    //         }

    //         Log::error('Airtel token not found in response', ['resp' => $body]);
    //         return null;

    //     } catch (BadResponseException $e) {
    //         Log::error('Airtel auth error: '.$e->getMessage(), ['resp' => $e->getResponse()?->getBody()?->getContents()]);
    //         return null;
    //     } catch (\Exception $e) {
    //         Log::error('Airtel auth exception: '.$e->getMessage());
    //         return null;
    //     }
    // }

    // /**
    //  * Choose correct collection URL from env
    //  */
    // protected function getAirtelCollectionUrl()
    // {
    //     $env = env('AIRTEL_ENV', 'uat');
    //     return $env === 'prod' ? env('AIRTEL_LIVE_COLLECTION_URL') : env('AIRTEL_UAT_COLLECTION_URL');
    // }
}
