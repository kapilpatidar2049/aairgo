<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;
use App\Models\Admin\ZoneType;

/**
 * Test filter to demonstrate the custom filter usage.
 * Delete later.
 */
class PriceFilter implements FilterContract {
	/**
	 * The available filters.
	 *
	 * @return array
	 */
	public function filters() {
        return [
            'zone_id',
            'type_id',
            'status',
            'transport_type',
            'service_location_id',
            'zone_type',
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
	public function status($builder, $value = 0) {
		$builder->where('active', $value);
    }
    public function zone_id($builder, $value = null)
    {
        $builder->whereHas('zoneTypePrice', function ($q) use ($value) {
            $q->whereIn('zone_id',$value);
        });
    }

    public function type_id($builder, $value = null)
    {
        $builder->whereHas('vehicleType', function ($q) use ($value) {
            $q->whereIn('type_id',$value);
        });
    }


    public function service_location_id($builder, $value=null) {

        $builder->whereHas('zone',function($zoneQuery) {
            $zoneQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        });
        if($value !== "all"){
            $builder->whereHas('zone',function($zoneQuery) use($value) {
                $zoneQuery->where('service_location_id',$value);
            });
        }

    }


    public function zone_type($builder, $value=null) {
        $zonetype = ZoneType::find($value);
        if($zonetype){
            $builder->where('zone_id',$zonetype->zone_id)->where('type_id',$zonetype->type_id);
        }
    }

}
