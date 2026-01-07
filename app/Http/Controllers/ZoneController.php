<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Admin\Zone;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\LineString;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Validation\ValidationException;
use Grimzy\LaravelMysqlSpatial\Types\MultiPolygon;
use Illuminate\Support\Facades\Log;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Base\Filters\Admin\ZoneFilter;
use App\Models\Admin\Setting;

class ZoneController extends Controller
{
    //

    public function index() {
        $settings = Setting::where('category', 'peak_zone_settings')->get()->pluck('value', 'name')->toArray();
        return inertia('pages/zone/index', ['app_for'=>env('APP_FOR'),'settings' => $settings]);
    }
    public function updateZoneFeature(Request $request)
    {
        $validated = $request->validate([
            'enable_peak_zone_feature' => 'required|boolean',
        ]);
    
        Setting::updateOrCreate(
            ['name' => 'enable_peak_zone_feature', 'category' => 'peak_zone_settings'],
            ['value' => $validated['enable_peak_zone_feature']]
        );
    
        return response()->json(['success' => true]);
    }
    public function fetch(QueryFilterContract $queryFilter)
    {
        $query = Zone::query();

        $results = $queryFilter->builder($query)->customFilter(new ZoneFilter)->paginate();
    //   dd($results);
        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }
    public function create() 
    {
        $googleMapKey =  get_map_settings('google_map_key'); // Retrieve the Google Map API key
        $settings = Setting::where('category', 'peak_zone_settings')->get()->pluck('value', 'name')->toArray();

        // dd($requestData);
        $map_type = get_map_settings('map_type');

        $existingZones = Zone::companyKey()->get();
        $existing_coordinates = [];

        foreach ($existingZones as $zone) {
            $multiPolygon = $zone->coordinates;

            if ($multiPolygon instanceof \Grimzy\LaravelMysqlSpatial\Types\MultiPolygon) {
                foreach ($multiPolygon as $polygon) {
                    foreach ($polygon as $lineString) {
                        $polygonPoints = [];

                        foreach ($lineString->getPoints() as $point) {
                            $polygonPoints[] = [
                                'lat' => $point->getLat(),
                                'lng' => $point->getLng(),
                            ];
                        }

                        $existing_coordinates[] = $polygonPoints;
                    }
                }
            }
        }

        // dd($existingZones,$existing_coordinates);
        if($map_type=="open_street_map")
        {
            return inertia('pages/zone/open-create',[
                'enable_maximum_distance_feature'=>get_settings('enable_maximum_distance_feature') == 1,
                'default_lat'=>get_settings('default_latitude'),
                'default_lng'=>get_settings('default_longitude'),
                'existingZones'=>$existing_coordinates,
                'settings' => $settings
            ]);
        }else{
            return inertia('pages/zone/create',[
                'enable_maximum_distance_feature'=>get_settings('enable_maximum_distance_feature') == 1,
                'default_lat'=>get_settings('default_latitude'),
                'default_lng'=>get_settings('default_longitude'),
                'googleMapKey' => $googleMapKey, // Pass the Google Map API key to the Vue component
                'existingZones'=>$existing_coordinates,
                'settings' => $settings
            ]);
        }

    }
    public function store(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // dd($request->all());
        $validated = $request->validate(['languageFields' => 'required|array']);
        $created_params = $request->only(['service_location_id','unit','maximum_outstation_distance','maximum_distance',
        'peak_zone_radius',
        'peak_zone_duration',
        'peak_zone_history_duration',
        'peak_zone_ride_count',
        'distance_price_percentage',]);
        $created_params['unit'] = (int) $request->unit;
        $set = [];
        if ($request->coordinates == null) {
            throw ValidationException::withMessages(['name' => __('Please Complete the shape before submit')]);
        }

        // Decode the coordinates JSON string
        $decodedCoordinates = json_decode($request->coordinates, true);

        // Check if the decoding was successful
        if ($decodedCoordinates === null) {
            throw ValidationException::withMessages(['coordinates' => __('Invalid coordinates format')]);
        }

        foreach ($decodedCoordinates as $coordinates) {
            $points = [];
            foreach ($coordinates as $key => $coordinate) {

                // Check if the coordinate is an array with exactly two elements (lng, lat)
                if (is_array($coordinate) && count($coordinate) === 2)
                 {

                    if ($key == 0) {
                        $created_params['lat'] = $coordinate[1];
                        $created_params['lng'] = $coordinate[0];
                    }

                    $point = new Point($coordinate[1], $coordinate[0]); // Point(lat, lng)

                    $check_if_exists = Zone::companyKey()->contains('coordinates', $point)->exists();
                    if ($check_if_exists) {
                        throw ValidationException::withMessages(['zone_name' => __('Coordinates already exists with our exists zone')]);
                    }

                    $points[] = $point;
                } else {
                    throw ValidationException::withMessages(['coordinates' => __('Invalid coordinate data')]);
                }
            }
            // Close the polygon by repeating the first point
            if (count($points) > 0) {
                array_push($points, $points[0]);
            }

            $lineStrings = [new LineString($points)];
            $set[] = new Polygon($lineStrings);
        }

        $multi_polygon = new MultiPolygon($set);

        $created_params['coordinates'] = $multi_polygon;


        $created_params['name'] = $validated['languageFields']['en'];

        $zone = Zone::create($created_params);
        foreach ($validated['languageFields'] as $code => $language) {
            $translationData[] = ['name' => $language, 'locale' => $code, 'zone_id' => $zone->id];
            $translations_data[$code] = new \stdClass();
            $translations_data[$code]->locale = $code;
            $translations_data[$code]->name = $language;
        }
        $zone->zoneTranslationWords()->insert($translationData);
        $zone->translation_dataset = json_encode($translations_data);
        $zone->save();

        return response()->json(['zone' => $zone], 201);
    }
    

    public function list() 
    {
        $results = get_user_locations(auth()->user());
        return response()->json(['results' => $results]);
    }
    public function edit($id)
    {
        $zone = Zone::findOrFail($id);
        $googleMapKey = get_map_settings('google_map_key'); // Retrieve the Google Map API key
        $settings = Setting::where('category', 'peak_zone_settings')->get()->pluck('value', 'name')->toArray();

        $existingZones = Zone::companyKey()->where('id','!=',$id)->get();
        $existing_coordinates = [];

        foreach ($existingZones as $existingZone) {
            $multiPolygon = $existingZone->coordinates;

            if ($multiPolygon instanceof \Grimzy\LaravelMysqlSpatial\Types\MultiPolygon) {
                foreach ($multiPolygon as $polygon) {
                    foreach ($polygon as $lineString) {
                        $polygonPoints = [];
                        foreach ($lineString->getPoints() as $point) {
                            $polygonPoints[] = [
                                'lat' => $point->getLat(),
                                'lng' => $point->getLng(),
                            ];
                        }
                        $existing_coordinates[] = $polygonPoints;
                    }
                }
            }
        }

        foreach ($zone->zoneTranslationWords as $language) {
            $languageFields[$language->locale]  = $language->name;
        }
        $zone->languageFields = $languageFields ?? null;

                // dd($zone->coordinates);
                $map_type = get_map_settings('map_type');

                if($map_type=="open_street_map")
                {
                    return inertia('pages/zone/open-edit',['zone' => $zone,
                    'enable_maximum_distance_feature'=>get_settings('enable_maximum_distance_feature') == 1,
                    'default_lat'=>get_settings('default_latitude'),
                    'default_lng'=>get_settings('default_longitude'),
                    'existingZones'=>$existing_coordinates,
                    'settings' => $settings
                    ]);

                }else{
               return inertia('pages/zone/edit',['zone' => $zone,
               'enable_maximum_distance_feature'=>get_settings('enable_maximum_distance_feature') == 1,
               'default_lat'=>get_settings('default_latitude'),
               'default_lng'=>get_settings('default_longitude'),
                'existingZones'=>$existing_coordinates,
               'googleMapKey' => $googleMapKey,'app_for'=>env('APP_FOR'),
                'settings' => $settings]);

                }


    } 
    public function update(Request $request, Zone $zone)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // Validate request data
        $validated = $request->validate([
            // 'coordinates' => 'required|array', // Ensure coordinates is required and is an array
            // 'name' => 'required', // Example: Assuming zone_name is required
            'unit' => 'required', // Example: Assuming zone_name is required
            'languageFields' => 'required|array',
        ]);
        $updated_params['unit'] = (int) $request->unit;
        $updated_params['maximum_distance'] = (double) $request->maximum_distance ?? 0;
        $updated_params['maximum_outstation_distance'] = (double) $request->maximum_outstation_distance ?? 0;
        $updated_params['peak_zone_radius'] = (double) $request->peak_zone_radius ?? 0;
        $updated_params['peak_zone_ride_count'] = (double) $request->peak_zone_ride_count ?? 0;
        $updated_params['distance_price_percentage'] = (double) $request->distance_price_percentage ?? 0;
        $updated_params['peak_zone_duration'] = (double) $request->peak_zone_duration ?? 0;
        $updated_params['peak_zone_history_duration'] = (double) $request->peak_zone_history_duration ?? 0;

        // Prepare updated parameters
        $updated_params['service_location_id'] = $request->service_location_id;
        
        $set = [];

        if ($request->coordinates == null) {
            throw ValidationException::withMessages(['name' => __('Please Complete the shape before submit')]);
        }

        // Decode the coordinates JSON string
        $decodedCoordinates = json_decode($request->coordinates, true);

        // Check if the decoding was successful
        if ($decodedCoordinates === null) {
            throw ValidationException::withMessages(['coordinates' => __('Invalid coordinates format')]);
        }

        foreach ($decodedCoordinates as $coordinates) {
            $points = [];
            foreach ($coordinates as $key => $coordinate) {

                // Check if the coordinate is an array with exactly two elements (lng, lat)
                if (is_array($coordinate) && count($coordinate) === 2)
                 {

                    if ($key == 0) {
                        $created_params['lat'] = $coordinate[1];
                        $created_params['lng'] = $coordinate[0];
                    }

                    $point = new Point($coordinate[1], $coordinate[0]); // Point(lat, lng)

                    $check_if_exists = Zone::companyKey()->contains('coordinates', $point)->where('id','!=',$zone->id)->exists();
                    if ($check_if_exists) {
                        throw ValidationException::withMessages(['zone_name' => __('Coordinates already exists with our exists zone')]);
                    }

                    $points[] = $point;
                } else {
                    throw ValidationException::withMessages(['coordinates' => __('Invalid coordinate data')]);
                }
            }
            // Close the polygon by repeating the first point
            if (count($points) > 0) {
                array_push($points, $points[0]);
            }

            $lineStrings = [new LineString($points)];
            $set[] = new Polygon($lineStrings);
        }


        // Create a MultiPolygon from the set of polygons
        $multi_polygon = new MultiPolygon($set);

        // Update additional parameters
        $updated_params['name'] = $validated['languageFields']['en'];
        $updated_params['coordinates'] = $multi_polygon;
        // Update New translated names
        $zone->zoneTranslationWords()->delete();
        foreach ($validated['languageFields'] as $code => $language) {
            $translationData[] = ['name' => $language, 'locale' => $code, 'zone_id' => $zone->id];
            $translations_data[$code] = new \stdClass();
            $translations_data[$code]->locale = $code;
            $translations_data[$code]->name = $language;
        }
        $zone->zoneTranslationWords()->insert($translationData);
        $updated_params['translation_dataset'] = json_encode($translations_data);
        // Update the zone with the updated parameters
        $zone->update($updated_params);

        // Return a response indicating success
        return response()->json(['zone' => $zone], 200);
    }
    public function destroy(Zone $zone)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $zone->delete();

        return response()->json([
            'successMessage' => 'Zone deleted successfully',
        ]);
    }   
    public function updateStatus(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // dd($request->all());
        Zone::where('id', $request->id)->update(['active'=> $request->status]);

        return response()->json([
            'successMessage' => 'Zone status updated successfully',
        ]);


    }
    public function map($id)
    {
        $zone = Zone::findOrFail($id);
        $googleMapKey = get_map_settings('google_map_key'); // Retrieve the Google Map API key
    // dd($googleMapKey);
        // Pass the zone data and Google Map API key to the Inertia view
                // dd($requestData);
                $map_type = get_map_settings('map_type');

                if($map_type=="open_street_map")
                {

                    return inertia('pages/zone/open-map', [
                        'zone' => $zone,
                    ]);
                 }else{
           
                    return inertia('pages/zone/map', [
                        'zone' => $zone,
                        'googleMapKey' => $googleMapKey, // Pass the Google Map API key to the Vue component
                    ]);         
                 }
    }
    
}


