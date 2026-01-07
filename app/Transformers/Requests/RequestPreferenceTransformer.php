<?php

namespace App\Transformers\Requests;

use App\Transformers\Transformer;
use App\Models\Request\RequestPreference;

class RequestPreferenceTransformer extends Transformer
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
     * @param RequestPreference $preference
     * @return array
     */
    public function transform(RequestPreference $preference)
    {
        $preferencePrice = $preference->preferencePrice;
        return [
            'id' => $preference->id,
            'preference_price_id' => $preference->preference_price_id,
            'preference_id' => $preferencePrice->preference_id,
            'name' => $preferencePrice->name,
            'icon' => $preferencePrice->icon,
            'price' => $preference->price,
        ];
    }
}
