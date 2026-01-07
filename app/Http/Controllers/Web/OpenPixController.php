<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Base\Constants\Masters\WalletRemarks;
use App\Models\Payment\UserWallet;
use App\Models\Payment\DriverWallet;
use App\Models\Payment\OwnerWallet;
use App\Models\Payment\OwnerWalletHistory;
use App\Models\Payment\UserWalletHistory;
use App\Models\Payment\DriverWalletHistory;
use App\Models\Admin\Subscription;
use App\Models\Admin\SubscriptionDetail;
use App\Base\Constants\Masters\PushEnums;
use App\Jobs\Notifications\SendPushNotification;
use Kreait\Firebase\Contract\Database;
use App\Base\Constants\Auth\Role;
use Carbon\Carbon;
use App\Models\Request\Request as RequestModel;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Resources\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class OpenPixController extends BaseController
{
    protected $database;


    public function __construct(Database $database)
    {
        $this->database = $database;
    }

   
    public function openPix(Request $request)
    {
        $environment = get_payment_settings('openpix_environment'); // 'test' or 'live'

        if ($environment === "test") {
            $apiKey = get_payment_settings('openpix_test_api_key');
        } else {
            $apiKey = get_payment_settings('openpix_live_api_key');
        }

        // $apiKey = 'Q2xpZW50X0lkXzNkYjkyNjEyLTU2NjktNDI2Ny1iMzZkLTc1NTczMGU5MTVhNzpDbGllbnRfU2VjcmV0X2grOUZ3OGhqSld0eE5JeDE0M0RpZyt6ZGNDcUR0Z0V1aHVwQ21NcllEajA9';


        $user = User::find($request->input('user_id'));
        $plan_id = $request->input('plan_id');
        $payment_for = $request->input('payment_for');
        $amount = (float) $request->input('amount');
        $currency = "BRL";

        $payer = [
            "name" => $user->name ?? 'Guest',
            "email" => $user->email ?? 'guest@example.com',
        ];

        $description = now()->timestamp . "----" . $user->id . "----" . $payment_for . "----" . $amount;
        if ($request->request_id) {
            $description .= "----" . $request->request_id;
        }
        if($plan_id){

            $description .= "----" . $request->plan_id;

        }

        $correlationID = Str::uuid();

        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://api.openpix.com.br/api/v1/charge', [
                'correlationID' => $correlationID,
                'value' => intval($amount * 100), // in centavos
                'comment' => $description,
                'customer' => $payer,
            ]);

            $data = $response->json();

            if (isset($data['charge']['qrCodeImage']) && isset($data['charge']['brCode'])) {
                return view('openpix.pix', [
                    'payment' => $data['charge'],
                    'amount' => $amount,
                    'currency' => $currency,
                ]);
            }

            return $this->throwCustomException('Failed to create OpenPix charge. ' . json_encode($data));
        } catch (\Exception $e) {
            return $this->throwCustomException('OpenPix Error: ' . $e->getMessage());
        }
    }


    public function webhook(Request $request)
    {
        Log::info($request->all());

        if(count($request->all()) == 4 && $request->input('authorization') == null){
            return response()->json(['success' => true]);
        }

        $exploded_reference = explode('----', $request->charge['comment']);

        \Log::info($exploded_reference);

        $web_booking_value = 0;
        $payment_for = $exploded_reference[2];
        $user_id = $exploded_reference[1];
        $user = User::find($user_id);
        $amount = $exploded_reference[3];

    if ($payment_for=="wallet") {

        $amount = $exploded_reference[3];

        if ($user->hasRole('user')) {
        $wallet_model = new UserWallet();
        $wallet_add_history_model = new UserWalletHistory();
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
            'transaction_id'=>$request->charge['transactionID'],
            'remarks'=>WalletRemarks::MONEY_DEPOSITED_TO_E_WALLET,
            'is_credit'=>true]);


                
               
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

            } elseif ($payment_for == 'subscription') {

            $plan_id = $exploded_reference[4];

                $plan = Subscription::find($plan_id);

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
               
                $notification = \DB::table('notification_channels')
                            ->where('topics', 'User Subscription') // Match the correct topic
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
            }else{

        

                $request_id =  $exploded_reference[4];
                // Log::info($request_id);
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
                    'transaction_id'=>$request->charge['transactionID'],
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
                    $this->database->getReference('requests/'.$request_detail->id)->update(['is_paid'=>1,'is_user_paid'=>true,'modified_by_driver'=>Database::SERVER_TIMESTAMP]);

            }


        return response()->json(['success' => true]);

    }



   
}
