<?php

namespace App\Jobs;

use App\Jobs\NotifyViaMqtt;
use Illuminate\Bus\Queueable;
use App\Models\Request\RequestMeta;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Base\Constants\Masters\PushEnums;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Kreait\Firebase\Contract\Database;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Master\DriverImported;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Exports\InvalidDriverAndCountriesExport;


class DriverImportedFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $validData;
    protected $errors ;
    protected $driverImportId;
    protected $service_location;
    protected $vehicle_type;
    protected $countries;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($validData,$errors,$driverImportId,$service_location,$vehicle_type,$countries)
    {
        $this->validData = $validData;
        $this->errors  = $errors;
        $this->driverImportId = $driverImportId;
        $this->service_location = $service_location;
        $this->vehicle_type = $vehicle_type;
        $this->countries = $countries;
        // dd($errors);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //  Save invalid rows into Excel file
        if (!empty($this->errors)) {
            $errorFile = 'invalid_drivers_' .  date('Y-m-d_H-i-s') . '.xlsx';

            Excel::store(
                new InvalidDriverAndCountriesExport($this->errors, $this->service_location, $this->vehicle_type, $this->countries),
                $errorFile,
                'local'
            );
            // Update the DB record with paths
            $importRecord = DriverImported::find($this->driverImportId);
            $importRecord->update([
                'invalid_file_path' => $errorFile,
                'status' => 'Completed'
            ]);
        }
        else{
             $importRecord = DriverImported::find($this->driverImportId);
             $importRecord->delete();
            // $importRecord->update([
            //     'invalid_file_path' => null,
            // ]);
        }
    }
}
