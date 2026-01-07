<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;

/**
 * Test filter to demonstrate the custom filter usage.
 * Delete later.
 */
class ZoneFilter implements FilterContract {
	/**
	 * The available filters.
	 *
	 * @return array
	 */
	public function filters() {
        return [
            'status','search','service_location_id',
        ];
	}
    /**
    * Default column to sort.
    *
    * @return string
    */
    public function defaultSort()
    {
        return '-created_at';
    }
	public function status($builder, $value = 0) 
    {
		$builder->where('active', $value);
    }
    public function search($builder, $value=null) {
        $builder->where('name','LIKE','%'.$value.'%');
    }
    public function service_location_id($builder, $value=null) {
        $builder->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($value !== "all"){
            $builder->where('service_location_id',$value);
        }
    }

}
