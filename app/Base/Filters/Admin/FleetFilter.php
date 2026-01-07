<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;

/**
 * Test filter to demonstrate the custom filter usage.
 * Delete later.
 */
class FleetFilter implements FilterContract {
	/**
	 * The available filters.
	 *
	 * @return array
	 */
	public function filters() {
		return [
			'status','search','owner_id','approveStatus','service_location_id'
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

	public function owner_id($builder, $value = null) {
        $builder->where('owner_id', $value);
    }
	public function status($builder, $value = 0) 
    {
		$builder->where('active', $value);
    }

	public function approveStatus($builder, $value = 0) 
    {
        if($value){
            $builder->whereApprove(true);
        }else{
            $builder->whereApprove(false);
        }
    }    
    // public function search($builder, $value=null) {
    //     $builder->whereHas('vehicleType', function ($q) use ($value) {
    //         $q->where('name','Like',"%" .$value."%");
    //     })->orWhereHas('ownerDetail', function ($q) use ($value) {
    //         $q->where('name', "%" .$value."%" )->orWhere('mobile', "%" .$value. "%") ;
    //     })->orWhereHas('driverDetail',function ($q) use ($value){
    //         $q->where('name', "%" .$value. "%")->orWhere('mobile', "%" .$value. "%");
    //     });
    // }

    public function service_location_id($builder, $value = null) {
        if($value !== "all"){
            $builder->whereHas('user', function ($q) use ($value) {
                $q->whereHas('owner', function ($query) use ($value) {
                    $query->where('service_location_id',$value);
                });
            });
        }
    }
    public function search($builder, $value=null) {
        // $builder->where('vehicle_type','LIKE','%'.$value.'%');
        $builder->whereHas('vehicleTypeDetail', function ($query) use ($value) {
            $query->where('name', 'LIKE', '%' . $value . '%');
        });
    }
}
