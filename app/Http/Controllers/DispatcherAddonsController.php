<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Master\BannerImage;
use Illuminate\Http\Request;
use App\Base\Services\ImageUploader\ImageUploader;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use ZipArchive;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Jobs\DispatcherZipFilesAddons;


class DispatcherAddonsController extends Controller
{
    public function index() {
        return Inertia::render('pages/dispatcher_addons/index');
    }
     public function verification_submit(Request $request)
    {    
            $format = check_code_format($request->purchase_code);

            if($format['success'])
            {
                $UpdateSettingcontract = app("update-service");
                $softwarecheck = $UpdateSettingcontract->codeVerify(); 
               return json_encode($softwarecheck);
            }
            else{
                return json_encode($format);
            } 
       

    }
    public function dispatcher_files_uploads(Request $request){

        try {
            $request->validate([
                'dispatcher_zip_file' => 'required|file|mimes:zip',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $uploadedFile = $request->file('dispatcher_zip_file');
        $uploadPath = storage_path('app/public/');

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Move ZIP file to temporary folder
        $zipFileName = $uploadedFile->getClientOriginalName();
        $uploadedFile->move($uploadPath, $zipFileName);
        $zipFilePath = $uploadPath . '/' . $zipFileName;

        DispatcherZipFilesAddons::dispatch($zipFileName, $zipFilePath);

         return response()->json([
                 'success' => true,
                'message' => 'Module files extracted and stored successfully!',
            ], 201);

    }
}
