<?php

namespace App\Http\Controllers;
use App\Models\Admin\VehicleType;
use App\Models\Admin\Zone;
use Inertia\Inertia;
use App\Models\Admin\ZoneTypePrice;
use App\Models\Admin\ZoneType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Base\Filters\Admin\PriceFilter;
use App\Transformers\User\ZoneTypeTransformer;
use App\Base\Filters\Master\CommonMasterFilter;
use App\Models\Master\Preference;

class FareFixController extends Controller
{
    public function index(ZoneType $zonetype) 
    {
        return Inertia::render('pages/fare_fix/index', [
            'zonetype' => $zonetype,
        ]);
    }
    
    
    public function list(Request $request, QueryFilterContract $queryFilter)
    {
        // dd("djbfshdf");
        $query = ZoneType::orderBy('order_number','ASC')->whereNotNull('drop_zone');
        // dd($query->transport_type);
    
        $results = $queryFilter->builder($query)->customFilter(new PriceFilter)->paginate();
    
        $transformedData = fractal()
            ->collection($results)
            ->transformWith(new ZoneTypeTransformer())
            ->toArray();
    
        return response()->json([
            'results' => $transformedData['data'],
            'paginator' => $results,
        ]);
    }

    public function create(ZoneType $zonetype) 
    {
        $existing_fixed_zone = ZoneType::where('type_id',$zonetype->type_id)->whereNotNull('drop_zone')->pluck('drop_zone');
        $existing_fixed_zone[] = $zonetype->zone_id;
        $zones = Zone::where('active', true)
            ->whereIn('service_location_id',get_user_location_ids(auth()->user()))
            ->whereNotIn('id',$existing_fixed_zone)
            ->get();

        return Inertia::render('pages/fare_fix/create', ['zones' => $zones,'setprice'=>$zonetype]);
    }

    public function store(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $transportType = $request->transport_type;
        // dd($request);
        $zone  = Zone::whereId($request->zone_id)->first();
        $payment = implode(',', $request->payment_type);
        // To save default type
        $zoneType = $zone->zoneType()->create([
            'type_id' => $request->vehicle_type,
            'payment_type' => $payment,
            'transport_type' => $transportType,
            'admin_commision_type' => $request->admin_commision_type,
            'admin_commision' => $request->admin_commision,
            'admin_commission_type_from_driver' => $request->admin_commission_type_from_driver,
            'admin_commission_from_driver' => $request->admin_commission_from_driver,
            'admin_commission_type_for_owner' => $request->admin_commission_type_for_owner,
            'admin_commission_for_owner' => $request->admin_commission_for_owner,
            'airport_surge' => $request->airport_surge,
            'service_tax' => $request->service_tax,
            'order_number' => $request->order_number,
            'bill_status' => true,
            'support_airport_fee' => 0,
            'support_outstation' => 0,
            'enable_shared_ride' => 0,
            'price_per_seat' => 0,
            'shared_price_per_distance' => 0,
            'shared_cancel_fee' => 0,
            'drop_zone' => $request->drop_zone,
        ]);
// dd($zoneType);
        $vehiclePrice = $zoneType->zoneTypePrice()->create([
            'price_type' => 1,
            'base_price' => $request->base_price,
            'price_per_distance' => 0,
            'base_distance' => 0,
            'price_per_time' => 0.00,
             'waiting_charge' => 0.00,
            'free_waiting_time_in_mins_before_trip_start' => 0,
            'free_waiting_time_in_mins_after_trip_start' =>  0,
            'outstation_base_price' => 0.00,
            'outstation_price_per_distance' => 0,
            'outstation_base_distance' => 0,
            'outstation_price_per_time' => 0.00,
            'cancellation_fee_for_user' => 0,
            'cancellation_fee_for_driver' => 0,
            'fee_goes_to' => "",
        ]); 

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Vehicle Price created successfully.',
            'vehiclePrice' => $vehiclePrice,
        ], 201);
    }
    public function edit($id)
    {

        $zoneType = ZoneType::find($id);
        $zoneTypePrice = $zoneType->zoneTypePrice()->first();

        $setprice = ZoneType::where('zone_id',$zoneType->zone_id)->where('type_id',$zoneType->type_id)->whereNull('drop_zone')->first();
        $zones = [$zoneType->dropZoneDetail];
        return Inertia::render(
            'pages/fare_fix/create',
            [
                'zoneTypePrice' => $zoneTypePrice,
                'zoneType' => $zoneType,
                'zones' => $zones,
                'setprice'=>$setprice,
            ]);
    }

    public function update(Request $request, ZoneTypePrice $zoneTypePrice) 
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
    // dd($request->all());
        $transportType = $request->transport_type;

        $payment = implode(',', $request->payment_type);
        // To save default type
        $zoneTypePrice->zoneType()->update([
            'type_id' => $request->vehicle_type,
            'payment_type' => $payment,
            'transport_type' => $transportType,
            'admin_commision_type' => $request->admin_commision_type,
            'admin_commision' => $request->admin_commision,
            'admin_commission_type_from_driver' => $request->admin_commission_type_from_driver,
            'admin_commission_from_driver' => $request->admin_commission_from_driver,
            'admin_commission_type_for_owner' => $request->admin_commission_type_for_owner,
            'admin_commission_for_owner' => $request->admin_commission_for_owner,
            'airport_surge' => $request->airport_surge,
            'service_tax' => $request->service_tax,
            'order_number' => $request->order_number,
            'bill_status' => true,
            'support_airport_fee' => 0,
            'support_outstation' => 0,
            'enable_shared_ride' => 0,
            'price_per_seat' => 0,
            'shared_price_per_distance' => 0,
            'shared_cancel_fee' => 0,
            'drop_zone' => $request->drop_zone,
        ]);
        // dd($zoneType);
        $vehiclePrice = $zoneTypePrice->update([
            'price_type' => 1,
            'base_price' => $request->base_price,
            'price_per_distance' => 0,
            'base_distance' => 0,
            'price_per_time' => 0.00,
             'waiting_charge' => 0.00,
            'free_waiting_time_in_mins_before_trip_start' => 0,
            'free_waiting_time_in_mins_after_trip_start' =>  0,
            'outstation_base_price' => 0.00,
            'outstation_price_per_distance' => 0,
            'outstation_base_distance' => 0,
            'outstation_price_per_time' => 0.00,
            'cancellation_fee_for_user' => 0,
            'cancellation_fee_for_driver' => 0,
            'fee_goes_to' => "",
        ]); 

        $setprice = $zoneTypePrice->zoneType;

       // Optionally, return a response
        return response()->json([
            'successMessage' => 'Vehicle Price created successfully.',
            'vehiclePrice' => $vehiclePrice,
        ], 201);
    
    }
    public function destroy($id)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }

        $zoneTypePrice = ZoneTypePrice::where('zone_type_id', $id)->delete();

        $zoneType = ZoneType::where('id', $id)->delete();

        return response()->json([
            'successMessage' => 'Vehicle Price deleted successfully',
        ]);
    }  
    public function updateStatus(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // ZoneTypePrice::where('zone_type_id', $request->id)->update(['active'=> $request->status]);
        ZoneType::where('id', $request->id)->update(['active'=> $request->status]);
        // dd($request->all());

        return response()->json([
            'successMessage' => 'Vehicle Price status updated successfully',
        ]);
    }
}
