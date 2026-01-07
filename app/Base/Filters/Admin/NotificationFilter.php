<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;

class NotificationFilter implements FilterContract
{

    public function filters() {
        return [
           'service_location_id'
        ];
	}
     

      public function defaultSort()
    {
        return '-created_at';
    }
     public function service_location_id($builder, $value=null) {
        $builder->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($value !== "all"){
            $builder->where('service_location_id',$value);
        }
     }

}