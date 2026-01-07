<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;
use Carbon\Carbon;
use App\Models\Admin\DriverNeededDocument;
use App\Base\Constants\Masters\DriverDocumentStatus;

/**
 * Test filter to demonstrate the custom filter usage.
 * Delete later.
 */
class DriverFilter implements FilterContract {
	/**
	 * The available filters.
	 *
	 * @return array
	 */
	public function filters() {
		return [
			'approve','service_location_id','transport_type','vehicle_type',
            'owner_id','fleet_vehicle_type','driver_id','driver_service_location',
            'upload_pending','document_status','subscription',
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

    public function approve($builder,$value=0)
    {
        $builder->where('approve',$value);
    }
    public function service_location_id($builder,$value="all")
    {
        if($value !== "all"){
             $builder->whereHas('driverDetail', function ($q) use ($value) {
                $q->where('service_location_id',$value)->whereIn('service_location_id',get_user_location_ids(auth()->user()));
            });
        }
    }
    public function driver_service_location($builder,$value="all")
    {
         $builder->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($value !== "all"){
            $builder->where('service_location_id',$value);
        }
    }
    public function transport_type($builder,$value='both')
    {
        if($value !== "all"){
            $builder->where('transport_type',$value);
        }
    }
    public function vehicle_type($builder,$value=null)
    {
        $builder->whereHas('driverVehicleTypeDetail', function ($q) use ($value) {
            $q->whereIn('vehicle_type',$value);
        });
    }
    public function owner_id($builder,$value=null)
    {
        $builder->where('owner_id',$value);
    }
    public function fleet_vehicle_type($builder,$value=null)
    {
        $builder->whereHas('fleetDetail', function ($q) use ($value) {
            $q->whereIn('vehicle_type',$value);
        });
    }
    

    public function driver_id($builder,$value=null)
    {
        $builder->where('driver_id',$value);
    }

    public function upload_pending($builder,$value=null)
    {
        $needed_count = DriverNeededDocument::active()->count();
        $builder->withCount('driverDocument')->having('driver_document_count',  $value ? '>' : '=' , $value ? 0 : $needed_count);
    }

    public function document_status($builder,$value=null)
    {
        if($value){
            $builder->withCount(['driverDocument as valid_count' => function($query){
                $query->where('document_status' , DriverDocumentStatus::REUPLOADED_AND_WAITING_FOR_APPROVAL);
            }])->having('valid_count', '>', 0);
        }else{
            $needed_count = DriverNeededDocument::active()->where('is_required',true)->count();
            $builder->withCount(['driverDocument as valid_count' => function($query){
                $query->where('document_status' , DriverDocumentStatus::UPLOADED_AND_WAITING_FOR_APPROVAL);
            }])->having('valid_count', '>=', $needed_count);
        }
    }

    public function subscription($builder,$value=null)
    {
        if($value == 'subscribed'){
            $builder->where('is_subscribed',true);
        }elseif ($value == 'expired') {
            $builder->where('is_subscribed',false)->whereNotNull('subscription_detail_id');
        }else{
            $builder->where('is_subscribed',false)->whereNull('subscription_detail_id');
        }

    }
}
