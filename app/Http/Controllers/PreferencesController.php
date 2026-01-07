<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Master\Preference;
use Illuminate\Http\Request;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Filters\Admin\PreferenceFilter;

class PreferencesController extends Controller
{

    protected $imageUploader;

    public function __construct(ImageUploaderContract $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }
    public function index() {
        return Inertia::render('preference/index',[ 'app_for' => env('APP_FOR'), ]);
    }
    // List of Vehicle Type
    public function list(QueryFilterContract $queryFilter, Request $request)
    {
        $query = Preference::orderBy('created_at','DESC');
// dd("ssss");
        $results = $queryFilter->builder($query)->paginate();

        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }
    public function store(Request $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // dd($request->all());
         // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required',
            'icon' => 'required',
        ]);

        if ($uploadedFile = $request->file('icon')) {
            $validated['icon'] = $this->imageUploader->file($uploadedFile)
                ->savePreferenceIcon();
        }

        // Create a new service location
        $preference = Preference::create($validated);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Preference created successfully.',
            'preference' => $preference,
        ], 201);
    }
    public function update(Request $request, Preference $preference)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        // dd($request->all());
         // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required',
            'icon' => 'required',
        ]);

        if ($uploadedFile = $request->file('icon')) {
            $validated['icon'] = $this->imageUploader->file($uploadedFile)
                ->savePreferenceIcon();
        }
        // Create a new service location
        $preference->update($validated);

        // Optionally, return a response
        return response()->json([
            'successMessage' => 'Preference Updated successfully.',
            'preference' => $preference,
        ], 201);

    }
    public function destroy(Preference $preference)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $preference->delete();

        return response()->json([
            'successMessage' => 'Preference deleted successfully',
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
        Preference::where('id', $request->id)->update(['active'=> $request->status]);

        return response()->json([
            'successMessage' => 'Preference status updated successfully',
        ]);


    }

}
