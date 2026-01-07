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
use App\Models\Master\UserImported;

class UserImportedFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $validData;
    protected $errors ;
    protected $userImportId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($validData,$errors,$userImportId)
    {
        $this->validData = $validData;
        $this->errors  = $errors;
        $this->userImportId = $userImportId;
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
            $errorFile = 'invalid_users_' .  date('Y-m-d_H-i-s') . '.xlsx';
            Excel::store(new class($this->errors) implements FromCollection, WithHeadings {
                private $errors;
                public function __construct($errors) { $this->errors = $errors; }
                public function collection() { return new Collection($this->errors); }
                public function headings(): array {
                    return ['Name', 'Email', 'Mobile','Gender','Country','Error'];
                }
            }, $errorFile, 'local');
            // Update the DB record with paths
            $importRecord = UserImported::find($this->userImportId);
            $importRecord->update([
                'invalid_file_path' => $errorFile,
                'status' => 'Completed'
            ]);
        }
        else{
             $importRecord = UserImported::find($this->userImportId);
             $importRecord->delete();
            // $importRecord->update([
            //     'invalid_file_path' => null,
            // ]);
        }
    }
}
