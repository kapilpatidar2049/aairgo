<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Storage;
use App\Models\Admin\Driver;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Imports\DriverImport;

class DriverSheetImport implements WithMultipleSheets
{
     public $errors = [];
    public $validData = [];

    public function sheets(): array
    {
        //  Only take sheet 0
        return [
            0 => new DriverImport($this), 
        ];
    }
}
