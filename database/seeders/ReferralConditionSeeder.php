<?php

namespace Database\Seeders;

use App\Models\Admin\ReferralCondition;
use App\Models\Admin\ReferralConditionTranslation;
use Illuminate\Database\Seeder;

class ReferralConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    protected $referral = [
        [   
            'label_referral' => 'user',
            'referral_type' => 'instant_referrer_user',
            'description' => '
            <ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer user will get {referred_user_amount} instantly as a reward after a new user or driver signs up.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',
        ],

        [   
            'label_referral' => 'user', 
            'referral_type' => 'instant_referrer_user_and_new_user',
            'description' => 
            '<ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer user receives {referred_user_amount} as a reward for referring a new driver or user, the new user receives {new_user_amount} and new driver gets {new_driver_amount} as a reward immediately after signing up.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',    
        ],

        [   
            'label_referral' => 'user',
            'referral_type' => 'conditional_for_referrer_user_ride_count',
            'description' => 
            '<ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer user get {referred_user_amount} when your referred user completes {user_ride_count} rides and the driver finishes {driver_ride_count} rides.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',
        ],

        [   
            'label_referral' => 'user',
            'referral_type' => 'conditional_for_referrer_user_earnings',
            'description' => '
            <ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                      <li>The referrer user receives {referred_user_amount} when the user meets the spending requirement of {user_spent_amount} and the driver meets the earning requirement of {driver_earning_amount}.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',
        ],

        [   
            'label_referral'=> 'user',
            'referral_type' => 'dual_conditional_for_referrer_user_and_new_user_ride_count',
            'description' => 
            '<ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer user gets {referred_user_amount} when the referred user completes {user_ride_count} rides and the referred driver finishes {driver_ride_count} rides. The new user earns {new_user_amount}, and the new driver earns {new_driver_amount}.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',          
        ],
    
        [   
            'label_referral'=> 'user',
            'referral_type' => 'dual_conditional_for_referrer_user_and_new_user_earnings',
            'description' => 
            '<ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer user gets {referred_user_amount} when the referred user meets the spending requirement of {user_spent_amount} and the driver meets the earning requirement of {driver_earning_amount}. The new user earns {new_user_amount}, and the new driver earns {new_driver_amount}.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',                          
        ],

        [   
            'label_referral'=> 'user',
            'referral_type' => 'user_banner_text',
            'description' => 'Refer and Earn',                
        ],

        // driver referral //
        [   
            'label_referral'=> 'driver',
            'referral_type' => 'instant_referrer_driver',
            'description' => 
            '<ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer driver will get {referred_driver_amount} instantly as a reward after a new driver or user signs up.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',           
        ],

        [   
            'label_referral'=> 'driver',
            'referral_type' => 'instant_referrer_driver_and_new_driver',
            'description' => 
            '<ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer driver receives {referred_driver_amount} as a reward for referring a new driver and user. The new driver gets {new_driver_amount} and the new user receives {new_user_amount} as a reward immediately after signing up.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>', 
        ],

        [   
            'label_referral'=> 'driver',
            'referral_type' => 'conditional_for_referrer_driver_ride_count',
            'description' => '
            <ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer driver get {referred_driver_amount} when your referred user completes {user_ride_count} rides and the driver finishes {driver_ride_count} rides.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',
        ],
        [   
            'label_referral'=> 'driver',
            'referral_type' => 'conditional_for_referrer_driver_earnings',
            'description' => '
               <ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer driver receives {referred_driver_amount} when the user meets the spending requirement of {user_spent_amount} and the driver meets the earning requirement of {driver_earning_amount}.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',                            
        ],


        [   
            'label_referral'=> 'driver',
            'referral_type' =>  'dual_conditional_for_referrer_driver_and_new_driver_or_new_user_ride_count',
            'description' => '
            <ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer driver gets {referred_driver_amount} when the referred user completes {user_ride_count} rides and the referred driver finishes {driver_ride_count} rides. The new driver earns {new_driver_amount}, and the new user earns {new_user_amount}.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>',
        ],

        [   
            'label_referral'=> 'driver',
            'referral_type' =>  'dual_conditional_for_referrer_driver_and_new_driver_or_new_user_earnings',
            'description' => '
            <ul>
                <li>
                    <strong>Share your code</strong>
                    <ul>
                        <li>The referrer driver gets {referred_driver_amount} when the referred user meets the spending requirement of {user_spent_amount} and the driver meets the earning requirement of {driver_earning_amount}. The new driver earns {new_driver_amount}, and the new user earns {new_user_amount}.</li>
                    </ul>
                </li>

                <li>
                    <strong>Ask friend to download the app</strong>
                    <ul>
                        <li>Ask your friends to download the redBus app.</li>
                    </ul>
                </li>

                <li>
                    <strong>Get rewarded</strong>
                    <ul>
                        <li>Earn cash back when your friends register and travel with redBus.</li>
                    </ul>
                </li>
            </ul>
            ',
        ],

        [   
            'label_referral'=> 'driver',
            'referral_type' => 'driver_banner_text',
            'description' => 'Refer and Earn',                
        ],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $created_params = $this->referral;

        $value = ReferralCondition::first();
        if(!$value){
            foreach ($created_params as $referral) 
            {
                $value = ReferralCondition::create($referral);
                $translationData = [
                    'description' => $referral['description'], 
                    'locale' => 'en', 'referral_id' => $value->id];
                $translations_data['en'] = new \stdClass();
                $translations_data['en']->locale = 'en';
                $translations_data['en']->description = $referral['description'];
                $value->referralConditionTranslationWords()->insert($translationData);
                $value->translation_dataset = json_encode($translations_data);
                $value->save();
            }
        }

    }
    
}
