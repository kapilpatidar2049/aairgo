<?php

namespace App\Transformers\Common;

use App\Transformers\Transformer;
use App\Models\Admin\ReferralCondition;

class ReferralConditionTransformer extends Transformer
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected array $availableIncludes = [

    ];

    /**
     * A Fractal transformer.
     *
     * @param ReferralCondition $referral_condition
     * @return array
     */
    public function transform(ReferralCondition $referral_condition)
     
    {
        // dd($referral_condition);
        $params =  [
            'id' => $referral_condition->id,
            'referral_type' => $referral_condition->referral_type,
            'description' => $referral_condition->description,
            'label_referral' => $referral_condition->label_referral,
            'translation_dataset'=> $referral_condition->translation_dataset,
        ];


        $user = auth()->user();

        if($user!=null){

        $current_locale = $user->lang;

        }else{

            $current_locale='en';
            
        }

        if(!$current_locale){
            
            $current_locale='en';

        }

        if($referral_condition->translation_dataset){
            foreach (json_decode($referral_condition->translation_dataset) as $key => $tranlation) {

                if($tranlation->locale=='en'){
    
                    $params['description'] = $tranlation->description;
                   
                   
                }
                if($tranlation->locale==$current_locale){
    
                    $params['description'] = $tranlation->description;
    
                    break; 
                }
                
                
            }

        }
        

        return $params;

    }
}
