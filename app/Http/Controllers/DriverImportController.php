<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Master\BannerImage;
use Illuminate\Http\Request;
use App\Base\Services\ImageUploader\ImageUploader;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DriverSheetImport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use App\Jobs\DriverImportedFilesJob;
use App\Models\Admin\Driver;
use App\Models\Master\DriverImported;
use Carbon\Carbon;
use Storage;
use App\Models\Country;
use App\Exports\DriverAndCountriesExport;
use App\Models\Admin\DriverNeededDocument;
use App\Models\Admin\ServiceLocation;
use App\Models\Admin\VehicleType;


class DriverImportController extends Controller
{

    public function index() {

         $user = auth()->user();
        return Inertia::render('pages/driver_import/index',['user'=> $user]);
    }
    public function sampleDownload()
    {
        // $filePath = public_path("sample-bulk-upload/bulk _upload_sample.xlsx");

        $driver = Driver::with(['driverDocument' => function ($q) {
            $q->get()->keyBy('document_id');
        }])->limit(2)->get();
        $driver_needed_document = DriverNeededDocument::active()->get();
        $service_location = ServiceLocation::active()->get();
        $vehicle_type = VehicleType::active()->get();

         $countries = Country::active()
        ->get()
        ->map(function ($country) {
            return [
                'Country Name' => $country->name,
                'Code'         => $country->dial_code,
                'Status'       => $country->active,
            ];
        });

       return Excel::download(new DriverAndCountriesExport($driver,$countries,$driver_needed_document,$service_location,$vehicle_type), 'drivers_countries.xlsx');
    }


     public function list(QueryFilterContract $queryFilter ,Request $request)
    {
        $query = DriverImported::query()->paginate();
        // dd($query);
        // $results = $queryFilter->builder($query)->paginate();
        // dd($results);

        return response()->json([
            'results' => $query->items(),
            'paginator' => $query,
        ]);
    }
    public function create() {
        return Inertia::render('pages/driver_import/create');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new DriverSheetImport();
        Excel::import($import, $request->file('file'));   

        $user = auth()->user();         
         
         $driverImport = DriverImported::create([
            'user_id' => $user->id,
            'valid_file'   => !empty($import->validData) ? 1 : 0,
            'invalid_file' => !empty($import->errors) ? 1 : 0,
        ]);
        // dd($import->errors);

        
        $service_location = ServiceLocation::active()->get();
        $vehicle_type = VehicleType::active()->get();
           $countries = Country::active()
        ->get()
        ->map(function ($country) {
            return [
                'Country Name' => $country->name,
                'Code'         => $country->dial_code,
                'Status'       => $country->active,
            ];
        });


        dispatch(new DriverImportedFilesJob($import->validData, $import->errors,$driverImport->id,$service_location,$vehicle_type,$countries));


        return response()->json([
            'success' => true,
            'message' => 'Import completed',
        ],201);
    }

    public function downloadInvalidFile($id)
    {
        $driversImported = DriverImported::find($id);

        $fileName = $driversImported->invalid_file_path;// build the absolute path
        $filePath = storage_path('app/public/' . $fileName);

        // check if file exists first (good safety)
        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, 'invalid_drivers.xlsx');
    }

     public function reuploadInvalidFile($id)
    {
        $DriverImported = DriverImported::find($id);
         return Inertia::render(
            'pages/driver_import/create',
            ['DriverImported' => $DriverImported,]
        );
    }

     public function reuploadInvalidFileStore(DriverImported $driverImport, Request $request)
    {

        // dd($userImport);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);
        // dd($driverImport);

        //  Delete old file if it exists
        if ($driverImport->invalid_file_path && \Storage::disk('local')->exists($driverImport->invalid_file_path)) {
            \Storage::disk('local')->delete($driverImport->invalid_file_path);
        }

        $import = new DriverSheetImport();
        Excel::import($import, $request->file('file'));
         
         $driverImport->update(([
                'valid_file'   => !empty($import->validData) ? 1 : 0,
                'invalid_file' => !empty($import->errors) ? 1 : 0,
            ])
         );
         $service_location = ServiceLocation::active()->get();
        $vehicle_type = VehicleType::active()->get();
           $countries = Country::active()
        ->get()
        ->map(function ($country) {
            return [
                'Country Name' => $country->name,
                'Code'         => $country->dial_code,
                'Status'       => $country->active,
            ];
        });


            dispatch(new DriverImportedFilesJob($import->validData, $import->errors,$driverImport->id,$service_location,$vehicle_type,$countries));


        return response()->json([
            'success' => true,
            'message' => 'Import completed',
        ],201);
    }


}
