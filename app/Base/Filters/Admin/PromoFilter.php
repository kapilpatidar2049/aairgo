<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;

class PromoFilter implements FilterContract
{
    public function filters()
    {
        return [
            'status',
            'transport_type',
            'service_location_id',
        ];
    }

    public function defaultSort()
    {
        return '-created_at';
    }

    public function status($builder, $value = null)
    {
        $builder->where('active', (int) $value);
    }
    
    public function transport_type($builder, $value = null)
    {
        if($value != 'all') {
            $builder->where('transport_type', $value);
        }
    }
      public function service_location_id($builder, $value=null) {
        $builder->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($value !== "all"){
            $builder->where('service_location_id',$value);
        }
    }


}
