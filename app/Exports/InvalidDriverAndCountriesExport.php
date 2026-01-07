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
use App\Exports\InvalidDriverBulkExport;
use App\Exports\ServiceLocationExport;
use App\Exports\VehicleTypeExport;




class InvalidDriverAndCountriesExport implements WithMultipleSheets

{
    protected $errors;

    public function __construct($errors,$service_location,$vehicle_type,$countries,)
    {
        $this->errors = $errors;
        $this->countries = $countries;
        $this->service_location = $service_location;
        $this->vehicle_type = $vehicle_type;
    }

    public function sheets(): array
    {
         return [
            new InvalidDriverBulkExport($this->errors,'Invalid_Drivers'),
            new CountryExport($this->countries, 'Countries List'),
            new ServiceLocationExport($this->service_location, 'ServiceLocation List'),
            new VehicleTypeExport($this->vehicle_type, 'VehicleType List'),
        ];
    }

}
