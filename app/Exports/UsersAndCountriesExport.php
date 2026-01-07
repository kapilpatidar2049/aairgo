<?php

namespace App\Exports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\CountryExport;
use App\Exports\UsersBulkExport;



class UsersAndCountriesExport implements WithMultipleSheets

{
    protected $users;
    protected $countries;

    public function __construct($users, $countries)
    {
        $this->users = $users;
        $this->countries = $countries;
    }

    public function sheets(): array
    {
         return [
            new UsersBulkExport($this->users,'Users List'),
            new CountryExport($this->countries, 'Countries List'),
        ];
    }

}
