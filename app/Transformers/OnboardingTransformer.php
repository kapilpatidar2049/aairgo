<?php

namespace App\Transformers;

use App\Models\Admin\Onboarding;
use App\Base\Constants\Setting\Settings;

class OnboardingTransformer extends Transformer
{
    /**
     * A Fractal transformer.
     *
     * @param Onboarding $onboarding
     * @return array
     */
    public function transform(Onboarding $onboarding)
    {
        $baseUrl = config('app.url');
        $imagePath = $baseUrl . 'storage/uploads/onboarding/' . $onboarding->onboarding_image;
        $params= [

            'order' => $onboarding->order,
            'id' => $onboarding->sn_o,
            'screen' => $onboarding->screen,
            'title' => $onboarding->title,
            'onboarding_image'=>$imagePath,
            'description'=>strip_tags($onboarding->description),
            'active'=>$onboarding->active,
            'translation_dataset'=> $onboarding->translation_dataset,	
        ];

        $current_locale = request()->input('locales');

		if($onboarding->translation_dataset){
			foreach (json_decode($onboarding->translation_dataset) as $key => $tranlation) {

				if($tranlation->locale=='en'){
					$params['title'] = $tranlation->title;
                    $params['description'] = strip_tags($tranlation->description);
				   
				   
				}
				if($tranlation->locale==$current_locale){
	
					$params['title'] = $tranlation->title;
                    $params['description'] = strip_tags($tranlation->description);
	
					break; 
				}
				
				
			}

		}



        return $params;
    }
}
