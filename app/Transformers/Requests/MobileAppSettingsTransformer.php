<?php

namespace App\Transformers\Requests;

use App\Transformers\Transformer;
use App\Models\Master\MobileAppSetting;

class MobileAppSettingsTransformer extends Transformer
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
     * @param MobileAppSetting $reason
     * @return array
     */
    public function transform(MobileAppSetting $mobile)
    {
        return [
            'id' => $mobile->id,
            'name' => $mobile->name,
            'transport_type' => $mobile->transport_type,
            'service_type' => $mobile->service_type,
            'menu_icon' => $mobile->mobile_menu_icon,
            'description' => $mobile->description,
            'short_description' => $mobile->short_description,
        ];
    }
}
