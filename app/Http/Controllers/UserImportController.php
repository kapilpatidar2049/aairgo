<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Master\BannerImage;
use Illuminate\Http\Request;
use App\Base\Services\ImageUploader\ImageUploader;
use App\Base\Services\ImageUploader\ImageUploaderContract;
use App\Base\Libraries\QueryFilter\QueryFilterContract;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use App\Jobs\UserImportedFilesJob;
use App\Models\User;
use App\Models\Master\UserImported;
use Carbon\Carbon;
use Storage;
use App\Models\Country;
use App\Exports\UsersAndCountriesExport;


class UserImportController extends Controller
{

    public function index() {

         $user = auth()->user();
        return Inertia::render('pages/user_import/index',['user'=> $user]);
    }

    public function list(QueryFilterContract $queryFilter ,Request $request)
    {
        $query = UserImported::query()->paginate();
        // dd($query);
        // $results = $queryFilter->builder($query)->paginate();
        // dd($results);

        return response()->json([
            'results' => $query->items(),
            'paginator' => $query,
        ]);
    }
    public function create() {
        return Inertia::render('pages/user_import/create');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new UsersImport();
        Excel::import($import, $request->file('file'));

        //  if (!empty($import->validData)) {
        //     foreach ($import->validData as $row) {
        //         $user = User::create([
        //             'name'       => $row['Name'],
        //             'email'      => $row['Email'],
        //             'mobile'     => $row['Mobile'],
        //             'active'     => $row['Active'],
        //             'gender'     => $row['Gender'],
        //             'created_at' => Carbon::now(),
        //             'updated_at' => Carbon::now(),
        //         ]);

        //         //  Attach role
        //         $user->attachRole('user');
        //     }    
        //  }   

        $user = auth()->user();
         
         
         $userImport = UserImported::create([
            'user_id' => $user->id,
            'valid_file'   => !empty($import->validData) ? 1 : 0,
            'invalid_file' => !empty($import->errors) ? 1 : 0,
        ]);


        dispatch(new UserImportedFilesJob($import->validData, $import->errors,$userImport->id));


        return response()->json([
            'success' => true,
            'message' => 'Import completed',
        ],201);
    }
    public function downloadInvalidFile($id)
    {
        $UserImported = UserImported::find($id);

        $fileName = $UserImported->invalid_file_path;// build the absolute path
        $filePath = storage_path('app/public/' . $fileName);

        // check if file exists first (good safety)
        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, 'invalid_users.xlsx');
    }

      public function reuploadInvalidFile($id)
    {
        $UserImported = UserImported::find($id);
         return Inertia::render(
            'pages/user_import/create',
            ['UserImported' => $UserImported,]
        );
    }

     public function reuploadInvalidFileStore(UserImported $userImport, Request $request)
    {

        // dd($userImport);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        //  Delete old file if it exists
        if ($userImport->invalid_file_path && \Storage::disk('local')->exists($userImport->invalid_file_path)) {
            \Storage::disk('local')->delete($userImport->invalid_file_path);
        }

        $import = new UsersImport();
        Excel::import($import, $request->file('file'));

        //  if (!empty($import->validData)) {
        //     User::insert(array_map(function ($row) {
        //         return [
        //             'name'   => $row['Name'],
        //             'email'  => $row['Email'],
        //             'mobile' => $row['Mobile'],
        //             'active' => $row['Active'],
        //             'gender' => $row['Gender'],
        //             'created_at'   => Carbon::now(),
        //             'updated_at'   => Carbon::now(),
        //         ];
        //     }, $import->validData));     
        //  }     
         
         $userImport->update(([
                'valid_file'   => !empty($import->validData) ? 1 : 0,
                'invalid_file' => !empty($import->errors) ? 1 : 0,
            ])
         );


            dispatch(new UserImportedFilesJob($import->validData, $import->errors,$userImport->id));


        return response()->json([
            'success' => true,
            'message' => 'Import completed',
        ],201);
    }


    public function sampleDownload()
    {
        // $filePath = public_path("sample-bulk-upload/bulk _upload_sample.xlsx");

        $users = User::belongsToRole('user')->limit(5)->get();

         $countries = Country::active()
        ->get()
        ->map(function ($country) {
            return [
                'Country Name' => $country->name,
                'Code'         => $country->dial_code,
                'Status'       => $country->active,
            ];
        });

       return Excel::download(new UsersAndCountriesExport($users,$countries), 'users_countries.xlsx');
    }

}
