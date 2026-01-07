<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use Inertia\Inertia;
use App\Models\Setting;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\Master\MobileAppSetting;
use App\Base\Filters\Master\CommonMasterFilter;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Common\MobileAppCreateRequest;
use App\Http\Requests\Common\MobileAppUpdateRequest;

/**
 * @resource Settings
 *
 * vechicle types Apis
 */
class MobileAppSettingController extends BaseController
{
    /**
     * The Setting model instance.
     *
     * @var \App\Models\Setting
     */
    protected $settings;

    protected $imageUploader;

    /**
     * VehicleTypeController constructor.
     *
     * @param \App\Models\Setting $settings
     */
    public function __construct(ImageUploaderContract $imageUploader)
    {
        $this->imageUploader = $imageUploader;
    }

    /**
    * Get all vehicle types
    * @return \Illuminate\Http\JsonResponse
    */
    public function index()
    {
        return Inertia::render('app_setting/index');
    }
    public function fetch(QueryFilterContract $queryFilter)
    {
        $query = MobileAppSetting::orderBy('order_by', 'asc');

        $results = $queryFilter->builder($query)->customFilter(new CommonMasterFilter)->paginate();
      
        return response()->json([
            'results' => $results->items(),
            'paginator' => $results,
        ]);
    }


      public function create()
    {
        return Inertia::render('app_setting/create',['app_setting' => null]);
    }

   public function store(MobileAppCreateRequest $request)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $created_params = $request->only(['name','service_type','order_by','short_description','description','transport_type',]);


        $OrderBy_exists = MobileAppSetting::where('order_by', $request->order_by)->exists();
        if ($OrderBy_exists) {
            throw ValidationException::withMessages(['order_by' => __('Order By Already Exists')]);
        }

        if ($request->hasFile('mobile_menu_icon')) {

            if ($uploadedFile = $this->getValidatedUpload('mobile_menu_icon', $request)) {
                $created_params['mobile_menu_icon'] = $this->imageUploader->file($uploadedFile)
                    ->saveMobileMenuImage();
            }
        }
  
        if ($request->hasFile('mobile_menu_cover_image')) {

            if ($uploadedFile = $this->getValidatedUpload('mobile_menu_cover_image', $request)) {
                $created_params['mobile_menu_cover_image'] = $this->imageUploader->file($uploadedFile)
                    ->saveMobileMenuImage();
            }
        }
  
        $created_params['active'] = true;

        $mobile_menu = MobileAppSetting::create($created_params);

        return response()->json([
            'successMessage' => 'mobile_menu created successfully.',
            'mobile_menu' => $mobile_menu,
        ], 201);
    }
    public function getById(MobileAppSetting $setting)
    {
        return Inertia::render('app_setting/create',['setting' => $setting]);

    }   
    public function update(MobileAppUpdateRequest $request, MobileAppSetting $setting)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }

        $updated_params = $request->only(['name','service_type','order_by','short_description','description','transport_type',]);
        
        if ($request->hasFile('mobile_menu_icon')) {

            if ($uploadedFile = $this->getValidatedUpload('mobile_menu_icon', $request)) {
                $updated_params['mobile_menu_icon'] = $this->imageUploader->file($uploadedFile)
                    ->saveMobileMenuImage();
            }
        }
  
        if ($request->hasFile('mobile_menu_cover_image')) {

            if ($uploadedFile = $this->getValidatedUpload('mobile_menu_cover_image', $request)) {
                $updated_params['mobile_menu_cover_image'] = $this->imageUploader->file($uploadedFile)
                    ->saveMobileMenuImage();
            }
        }
  

        $mobile_menu = $setting->update($updated_params);

        return response()->json([
            'successMessage' => 'mobile_menu updated successfully.',
            'mobile_menu' => $mobile_menu,
        ], 201);
    }
    public function updateStatus(Request $request, MobileAppSetting $setting)
    {
        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }

        $status =  !$setting->active;

        // dd($status);

        $setting->update([
            'active'=>$status
        ]);


        return response()->json([
            'successMessage' => 'Mobile Setting updated successfully',
        ]);
    }
    public function delete(MobileAppSetting $setting)
    {

        if(env('APP_FOR') == 'demo'){
            return response()->json([
                'alertMessage' => 'You are not Authorized'
            ], 403);
        }
        $setting->delete();
        return response()->json([
            'successMessage' => 'Mobile Setting deleted successfully',
        ]);
    }
}
