<?php

namespace App\Http\Controllers\Api\V1\Common;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Api\V1\BaseController;
use App\Transformers\User\ReferredUserTransformer;
use App\Models\Referral;
use App\Models\Admin\ReferralCondition;
use App\Transformers\Common\ReferralConditionTransformer;

/**
 * @group Referral
 *
 */
class ReferralController extends BaseController
{

    /**
     * Get Referral Progress
     * 
     * @response
     * {
     *      "success": true,
     *      "message": "referrals_listed",
     *      "data": [
     *          'instant' => false,
     *          'enable_ride_count' => true,
     *          'target_ride_count' => 5,
     *          'acheived_ride_count' => 3,
     *          'enable_earning' => false,
     *          'target_earning' => 0,
     *          'acheived_earning' => 0,
     *          'status' => false,
     *      ]
     * }
    */
    public function progress()
    {

        $user = auth()->user();
        $referrer = $user->referrer;

        if(!$referrer || !$referrer->role_name){
            $this->throwAuthorizationException();
        }
        $referrer_role = $referrer->role_name;

        $instant = false;

        $enableByRideCount = 0;
        $enableByEarning   = 0;

        $targetRideCount = 0;
        $acheivedRideCount = 0;

        $targetEarning = 0;
        $acheivedEarning = 0;


        if(access()->hasRole('user')){
            if(get_settings('referral_type') == 'instant'){
                $instant = true;
                goto refer_end;
            }

            $enableByRideCount = (int)(get_settings('enable_referral_condition_by_ride_count') ?? 0);
            $enableByEarning   = (int)(get_settings('enable_referral_condition_by_earning') ?? 0);

            if($enableByRideCount == 1){

                if($referrer_role == 'user'){
                    $targetRideCount = (int)(get_settings('referral_condition_user_ride_count') ?? 0);
                }elseif($referrer_role == 'driver'){
                    $targetRideCount = (int)(get_settings('driver_referral_condition_user_ride_count') ?? 0);
                }

                $acheivedRideCount = $user->requestDetail()->where('is_completed',true)->count();

            }elseif($enableByEarning == 1){

                if($referrer_role == 'user'){
                    $targetEarning = (float)(get_settings('referral_condition_user_spent_amount') ?? 0);
                }elseif($referrer_role == 'driver'){
                    $targetEarning = (float)(get_settings('driver_referral_condition_user_spent_amount') ?? 0);
                }

                $acheivedEarning = $user->driverWallet?->amount_added ?? 0;

            }else{
                $instant = true;
                goto refer_end;
            }
        }elseif(access()->hasRole('driver')){
            if(get_settings('driver_referral_type') == 'instant'){
                $instant = true;
                $this->throwAuthorizationException();
            }

            $enableByRideCount = (int)(get_settings('enable_driver_referral_condition_by_ride_count') ?? 0);
            $enableByEarning   = (int)(get_settings('enable_driver_referral_condition_by_earning') ?? 0);

            if($enableByRideCount == 1){

                if($referrer_role == 'user'){
                    $targetRideCount = (int)(get_settings('referral_condition_driver_ride_count') ?? 0);
                }elseif($referrer_role == 'driver'){
                    $targetRideCount = (int)(get_settings('driver_referral_condition_driver_ride_count') ?? 0);
                }

                $acheivedRideCount = $user->driver->requestDetail()->where('is_completed',true)->count();

            }elseif($enableByEarning == 1){

                if($referrer_role == 'user'){
                    $targetEarning = (float)(get_settings('referral_condition_driver_earning_amount') ?? 0);
                }elseif($referrer_role == 'driver'){
                    $targetEarning = (float)(get_settings('driver_referral_condition_driver_earning_amount') ?? 0);
                }

                $acheivedEarning = $user->driverWallet?->amount_added ?? 0;
                
            }else{
                $instant = true;
                goto refer_end;
            }
        }else{
            $this->throwAuthorizationException();
        }

        if($targetRideCount < $acheivedRideCount){
            $acheivedRideCount = $targetRideCount;
        }

        if($targetEarning < $acheivedEarning){
            $acheivedEarning = $targetEarning;
        }

        refer_end:

        $results = [
            'instant' => $instant,
            'enable_ride_count' => $enableByRideCount == 1,
            'target_ride_count' => $targetRideCount,
            'acheived_ride_count' => $acheivedRideCount,
            'enable_earning' => $enableByEarning == 1,
            'target_earning' => $targetEarning,
            'acheived_earning' => $acheivedEarning,
            'status' => $user->referralRecord()->exists(),
        ];

        return $this->respondSuccess($results, 'referrals_listed');
    }
    

    /**
     * List Referrals
     * 
     * @response
     * {
     *      "success": true,
     *      "message": "referrals_listed",
     *      "data": [
     *          'id' => 2456,
     *          'name' => John Krasynski,
     *          'profile_picture' => $user->profile_picture,
     *          'role_name'=>'user',
     *      ]
     * }
    */
    public function history() {
        if(!access()->hasRole('user') && !access()->hasRole('driver')){
            $this->throwAuthorizationException();
        }

        $query = User::where('referred_by',auth()->user()->id)->orderBy('created_at','DESC');
        
        $result  = filter($query, new ReferredUserTransformer)->paginate();

        return $this->respondSuccess($result, 'referrals_listed');

    }
    public function referralCondition(){
        $referral_type = get_settings('referral_type');
        $user_banner = ReferralCondition::where('referral_type', 'user_banner_text')->first();
        //dd($user_banner);

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
