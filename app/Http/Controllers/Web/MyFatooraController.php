<?php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request as ValidatorRequest;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Base\Constants\Masters\PushEnums;
use App\Models\Payment\OwnerWallet;
use App\Models\Payment\OwnerWalletHistory;
use App\Transformers\Payment\OwnerWalletTransformer;
use App\Jobs\Notifications\SendPushNotification;
use App\Models\Payment\UserWalletHistory;
use App\Models\Payment\DriverWalletHistory;
use App\Transformers\Payment\WalletTransformer;
use App\Transformers\Payment\DriverWalletTransformer;
use App\Http\Requests\Payment\AddMoneyToWalletRequest;
use App\Transformers\Payment\UserWalletHistoryTransformer;
use App\Transformers\Payment\DriverWalletHistoryTransformer;
use App\Models\Payment\UserWallet;
use App\Models\Admin\SubscriptionDetail;
use App\Models\Admin\Subscription;
use App\Models\Payment\DriverWallet;
use App\Base\Constants\Masters\WalletRemarks;
use App\Base\Constants\Auth\Role;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use App\Models\User;
use Log;
use Kreait\Firebase\Contract\Database;
use App\Base\Constants\Setting\Settings;
use Illuminate\Support\Facades\Http;

class MyFatooraController extends Controller
{

    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function myfatoora(ValidatorRequest $request)
    {
// dd(config('myfatoora.pk'));

        // Retrieve URL parameters
        $amount = $request->input('amount');
        $payment_for = $request->input('payment_for');
        $user_id = $request->input('user_id');
        $request_id = $request->input('request_id');
        $plan_id = $request->input('plan_id');

        $user = User::find($user_id);
        
        $currency = $user->countryDetail->currency_code ?? "KWD";


        $env = get_payment_settings('myfatoora_environment');
        if($env == 'test'){
            $base_url = 'https://apitest.myfatoorah.com/v2/';
            $token = get_payment_settings('myfatoora_test_token');
        }else{
            if ($currency == 'SAU') {
                $base_url = 'https://api-sa.myfatoorah.com/v2/';
            } elseif ($currency == 'QAT') {
                $base_url = 'https://api-qa.myfatoorah.com/v2/';
            } else {
                $base_url = 'https://api.myfatoorah.com/v2/';
            }
            $token = get_payment_settings('myfatoora_live_token');
        }


        $initiatePaymentResponse = Http::withToken($token)->post($base_url . 'InitiatePayment', [
            'InvoiceAmount' => $amount,
            'CurrencyIso' => $currency, // You can change this based on your logic
        ]);

        if ($initiatePaymentResponse->successful()) {
            $payment_methods = $initiatePaymentResponse['Data']['PaymentMethods'];
        } else {
            $payment_methods = [];
        }

        $response = Http::withToken($token)->post($base_url . 'InitiateSession', [
            'CustomerIdentifier' => str_random(10),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $session_id = $data['Data']['SessionId'];
            $payment_data = [
                'country_code' => $data['Data']['CountryCode'],
                'session_id' => $data['Data']['SessionId'],
                'mode'=>$env,
            ];
            return view('myfatoora.myfatoora', compact('amount', 'payment_for', 'currency', 'user_id','request_id','plan_id','payment_data','payment_methods'));
        }

            Log::info('myFatoora Failed');
            Log::info($response->json());
            Log::info($initiatePaymentResponse->json());
        
        return redirect()->route('failure');
    }

    public function myfatooraCheckout(ValidatorRequest $request)
    {

        $PaymentMethodId=($request->input('payment_method_id') );
        $sessionId=($request->input('session_id') );
        $amount=($request->input('amount') );
        $productname = $request->get('productname');
        $payment_for = $request->get('payment_for');
        $currency = $request->get('currency');
        $user_id = $request->get('user_id');
        $request_id = $request->get('request_id');
        $plan_id = $request->input('plan_id');
        
        $user = User::find($user_id);

        $currency = $user->countryDetail->currency_code ?? "KWD";
        $env = get_payment_settings('myfatoora_environment');
        if($env == 'test'){
            $base_url = 'https://apitest.myfatoorah.com/v2/';
            $token = get_payment_settings('myfatoora_test_token');
        }else{
            if ($currency == 'SAU') {
                $base_url = 'https://api-sa.myfatoorah.com/v2/';
            } elseif ($currency == 'QAT') {
                $base_url = 'https://api-qa.myfatoorah.com/v2/';
            } else {
                $base_url = 'https://api.myfatoorah.com/v2/';
            }
            $token = get_payment_settings('myfatoora_live_token');
        }
        
        // $token = "rLtt6JWvbUHDDhsZnfpAhpYk4dxYDQkbcPTyGaKp2TYqQgG7FGZ5Th_WD53Oq8Ebz6A53njUoo1w3pjU1D4vs_ZMqFiz_j0urb_BH9Oq9VZoKFoJEDAbRZepGcQanImyYrry7Kt6MnMdgfG5jn4HngWoRdKduNNyP4kzcp3mRv7x00ahkm9LAK7ZRieg7k1PDAnBIOG3EyVSJ5kK4WLMvYr7sCwHbHcu4A5WwelxYK0GMJy37bNAarSJDFQsJ2ZvJjvMDmfWwDVFEVe_5tOomfVNt6bOg9mexbGjMrnHBnKnZR1vQbBtQieDlQepzTZMuQrSuKn-t5XZM7V6fCW7oP-uXGX-sMOajeX65JOf6XVpk29DP6ro8WTAflCDANC193yof8-f5_EYY-3hXhJj7RBXmizDpneEQDSaSz5sFk0sV5qPcARJ9zGG73vuGFyenjPPmtDtXtpx35A-BVcOSBYVIWe9kndG3nclfefjKEuZ3m4jL9Gg1h2JBvmXSMYiZtp9MR5I6pvbvylU_PP5xJFSjVTIz7IQSjcVGO41npnwIxRXNRxFOdIUHn0tjQ-7LwvEcTXyPsHXcMD8WtgBh-wxR8aKX7WPSsT1O8d8reb2aR7K3rkV3K82K_0OgawImEpwSvp9MNKynEAJQS6ZHe_J_l77652xwPNxMRTMASk1ZsJL";

        $body = [
            "PaymentMethodId" => $PaymentMethodId,
            "SessionId" => $sessionId,
            "InvoiceValue" => round($amount, 3),
            "CustomerName" => $user->name,
            "CustomerAddress"=>[
                "Block"=>"Test",
                "Street"=>"Test",
                "HouseBuildingNo"=>"Test",
                "AddressInstructions"=>"Test"
            ],
            "InvoiceItems"=>[
                (object)[
                    "ItemName"=>"Ride",
                    "Quantity"=>1,
                    "UnitPrice"=>$amount,
                ]
            ],
            "DisplayCurrencyIso" => $env == 'test' ? 'KWD' : ($currency ?? 'KWD'),
            "CallBackUrl" => route('myfatoora.checkout.success') . '?payment_for=' . urlencode($payment_for) . '&currency=' . urlencode($currency) . '&amount=' . urlencode($amount) . '&user_id=' . urlencode($user_id). '&request_id=' . urlencode($request_id). '&plan_id=' . urlencode($plan_id),
            "ErrorUrl" => route('failure'),
            "Language" => "ar",
            "CustomerReference" => "noshipping-nosupplier"
        ];
        

        $response = Http::withToken($token)->post($base_url . 'ExecutePayment', $body);
        if ($response->successful()) {
            session()->put('transaction_reference', $response->json()['Data']['InvoiceId']);
            return response()->json($response->json()['Data']['PaymentURL'], $response->status());
        } else {
            dd($response->json());
            return redirect()->route('failure');
        }

    }

    public function myfatooraCheckoutSuccess(ValidatorRequest $request)
    {

        $web_booking_value=0;


        $payment_for = $request->get('payment_for');
        $currency = $request->get('currency');
        $amount = $request->get('amount');
        $user_id = $request->get('user_id');
        $request_id = $request->get('request_id');
        $plan_id = $request->get('plan_id');


         if ($payment_for=="wallet") {

             $request_id = null;

             $user = User::find($user_id);

            if ($user->hasRole('user')) {
                $wallet_model = new UserWallet();
                $wallet_add_history_model = new UserWalletHistory();
                $user_id = $user->id;
            } elseif($user->hasRole('driver')) {
                        $wallet_model = new DriverWallet();
                        $wallet_add_history_model = new DriverWalletHistory();
                        $user_id = $user->driver->id;
            }else {
                        $wallet_model = new OwnerWallet();
                        $wallet_add_history_model = new OwnerWalletHistory();
                        $user_id = $user->owner->id;
            }

            $user_wallet = $wallet_model::firstOrCreate([
                'user_id'=>$user_id]);
            $user_wallet->amount_added += $amount;
            $user_wallet->amount_balance += $amount;
            $user_wallet->save();
            $user_wallet->fresh();

            $wallet_add_history_model::create([
                'user_id'=>$user_id,
                'amount'=>$amount,
                'transaction_id'=>$request->PayerID,
                'remarks'=>WalletRemarks::MONEY_DEPOSITED_TO_E_WALLET,
                'is_credit'=>true]);


                    // $title = custom_trans('amount_credited_to_your_wallet_title');
                    // $body = custom_trans('amount_credited_to_your_wallet_body');

                    // dispatch(new SendPushNotification($user,$title,$body));

                    $notification = \DB::table('notification_channels')
                            ->where('topics', 'User Wallet Amount') // Match the correct topic
                            ->first();

                        //    send push notification 
                            if ($notification && $notification->push_notification == 1) {
                                 // Determine the user's language or default to 'en'
                                $userLang = $user->lang ?? 'en';
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
                                dispatch(new SendPushNotification($user, $title, $body));
                            }

                    if ($user->hasRole(Role::USER)) {
                    $result =  fractal($user_wallet, new WalletTransformer);
                    } elseif($user->hasRole(Role::DRIVER)) {
                        $result =  fractal($user_wallet, new DriverWalletTransformer);
                    }else{
                        $result =  fractal($user_wallet, new OwnerWalletTransformer);

                   }

                } elseif ($payment_for == 'subscription') {
                    $plan = Subscription::find($request->input('plan_id'));
                    $user = User::find($user_id);
                    $params['transaction_id'] = str_random(6);
                    $driver_wallet = $user->driver->DriverWallet;
                    $driver_wallet->amount_spent += $amount;
                    $driver_wallet->save();
    
                    $user->driver->driverWalletHistory()->create([
                        'amount'=>$amount,
                        'transaction_id'=>str_random(6),
                        'remarks'=>WalletRemarks::SUBSCRIPTION_FEE,
                        'is_credit'=>false,
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
                    $params['transaction_id'] = str_random(6);
                    $params['subscription_type'] = 1;
                    $subscription = SubscriptionDetail::create($params);
                    $driver->update([
                        'is_subscribed' => true,
                        'subscription_detail_id' => $subscription->id,
                    ]);
    
                    $result =  fractal($driver_wallet, new DriverWalletTransformer);

            }else{

                $request_id = $request->get('request_id');


                 $request_detail = RequestModel::where('id', $request_id)->first();

                 $web_booking_value = $request_detail->web_booking;

                $request_detail->update(['is_paid' => true]);

                    $driver_commision = $request_detail->requestBill->driver_commision;
                    if($request_detail->driverDetail->owner()->exists())
                    {
                        $wallet_model = new OwnerWallet();
                        $wallet_add_history_model = new OwnerWalletHistory();
                        $user_id = $request_detail->driverDetail->owner_id;
                    }else{
                        $wallet_model = new DriverWallet();
                        $wallet_add_history_model = new DriverWalletHistory();
                        $user_id = $request_detail->driver_id;
                    }
                    /*wallet Modal*/
                    $user_wallet = $wallet_model::firstOrCreate([
                    'user_id'=>$user_id]);
                    $user_wallet->amount_added += $driver_commision;
                    $user_wallet->amount_balance += $driver_commision;
                    $user_wallet->save();
                    $user_wallet->fresh();
                    /*wallet history*/
                    $wallet_add_history_model::create([
                    'user_id'=>$user_id,
                    'amount'=>$driver_commision,
                    'transaction_id'=>$request->PayerID,
                    'remarks'=>WalletRemarks::TRIP_COMMISSION_FOR_DRIVER,
                    'is_credit'=>true]);


                    $additional_charges_amount = $request_detail->requestBill->additional_charges_amount;

                    if($additional_charges_amount > 0){


                        if($request_detail->driverDetail->owner()->exists())
                        {
                            $owner_wallet = $request_detail->driverDetail->owner->ownerWalletDetail;
                            $owner_wallet->amount_added += $additional_charges_amount;
                            $owner_wallet->amount_balance += $additional_charges_amount;
                            $owner_wallet->save();
                
                            $owner_wallet_history = $request_detail->driverDetail->owner->ownerWalletHistoryDetail()->create([
                                'amount'=>$additional_charges_amount,
                                'transaction_id'=>str_random(6),
                                'remarks'=>WalletRemarks::ADDITIONAL_CHARGE_AMOUNT,
                                'is_credit'=>true
                            ]);
                        }else{
                            $driver_wallet = $request_detail->driverDetail->driverWallet;
                            $driver_wallet->amount_added += $additional_charges_amount;
                            $driver_wallet->amount_balance += $additional_charges_amount;
                            $driver_wallet->save();
                
                            $driver_wallet_history = $request_detail->driverDetail->driverWalletHistory()->create([
                                'amount'=>$additional_charges_amount,
                                'transaction_id'=>str_random(6),
                                'remarks'=>WalletRemarks::ADDITIONAL_CHARGE_AMOUNT,
                                'is_credit'=>true
                            ]);
                        }
                    }

                    // $title = custom_trans('amount_credited_to_your_wallet_title');
                    // $body = custom_trans('amount_credited_to_your_wallet_body');

                    // dispatch(new SendPushNotification($request_detail->driverDetail->user,$title,$body));

                    $notification = \DB::table('notification_channels')
                            ->where('topics', 'User Wallet Amount') // Match the correct topic
                            ->first();

                        //    send push notification 
                            if ($notification && $notification->push_notification == 1) {
                                 // Determine the user's language or default to 'en'
                                $userLang = $request_detail->driverDetail->user->lang ?? 'en';
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
                                dispatch(new SendPushNotification($request_detail->driverDetail->user, $title, $body));
                            }
                            // 
                    if ($request_detail->promo_id){
                        $discount = $request_detail->requestBill->promo_discount;
        
                        if($discount>0) {
                            if($request_detail->driverDetail->owner()->exists())
                            {
                                $owner_wallet = $request_detail->driverDetail->owner->ownerWalletDetail;
                                $owner_wallet->amount_added += $discount;
                                $owner_wallet->amount_balance += $discount;
                                $owner_wallet->save();
                    
                                $owner_wallet_history = $request_detail->driverDetail->owner->ownerWalletHistoryDetail()->create([
                                    'amount'=>$discount,
                                    'transaction_id'=>str_random(6),
                                    'remarks'=>WalletRemarks::DISCOUNTED_AMOUNT,
                                    'is_credit'=>true
                                ]);
                            }else{
                                $driver_wallet = $request_detail->driverDetail->driverWallet;
                                $driver_wallet->amount_added += $discount;
                                $driver_wallet->amount_balance += $discount;
                                $driver_wallet->save();
                    
                                $driver_wallet_history = $request_detail->driverDetail->driverWalletHistory()->create([
                                    'amount'=>$discount,
                                    'transaction_id'=>str_random(6),
                                    'remarks'=>WalletRemarks::DISCOUNTED_AMOUNT,
                                    'is_credit'=>true
                                ]);
                            }
                        }
                    }    
                 $this->database->getReference('requests/'.$request_detail->id)->update(['is_paid'=>1]);
                

            }




        return view('success',['success'],compact('web_booking_value','request_id'));
    }

    public function myfatooraCheckoutError(ValidatorRequest $request)
    {
        return view('failure',['failure']);

    }
}
