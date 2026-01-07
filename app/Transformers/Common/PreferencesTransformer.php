<?php

namespace App\Transformers\Common;

use App\Transformers\Transformer;
use App\Models\Master\Preference;

class PreferencesTransformer extends Transformer
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
     * @param Preference $goods_type
     * @return array
     */
    public function transform(Preference $preference)
    {
        $params =  [
            'id' => $preference->id,
            'name' => $preference->name,
            'icon' => $preference->icon,
            'created_at'  => $preference->created_at,
            'updated_at'  => $preference->updated_at,
        ];

        if(access()->hasRole('driver')){
            $params['driver_selected'] = auth()->user()->driver->preference()->where('preference_id',$preference->id)->exists();
        }

        return $params;

    }
}
