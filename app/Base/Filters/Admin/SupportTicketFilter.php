<?php

namespace App\Base\Filters\Admin;

use App\Base\Libraries\QueryFilter\FilterContract;

class SupportTicketFilter implements FilterContract
{
    public function filters()
    {
        return [
            'support_type',
            'status',
            'service_location_id',
            'user_type',
        ];
    }

    public function defaultSort()
    {
        return '-created_at';
    }

    public function support_type($builder, $value = null) 
    {
        $builder->where('support_type', $value);
    }
    public function status($builder, $value = null) 
    {
        $builder->where('status', $value);
    }
    public function service_location_id($builder, $value = null) 
    {
        $builder->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($value !== "all"){
            $builder->where('service_location_id',$value);
        }
    }
    public function ticket_id($builder, $value = null)
    {
        if ($value) {
            $builder->where('ticket_id', 'LIKE', '%' . $value . '%');
        }
    }
    public function user_type($builder, $value = null) 
    {
        $builder->whereHas('ticketTitle',function($userQuery) use ($value){
            $userQuery->where('user_type', $value);
        });
    }
}
