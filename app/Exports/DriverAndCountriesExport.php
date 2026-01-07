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
use App\Exports\DriverBulkExport;
use App\Exports\ServiceLocationExport;
use App\Exports\VehicleTypeExport;




class DriverAndCountriesExport implements WithMultipleSheets

{
    protected $driver;
    protected $countries;

    public function __construct($driver, $countries,$driver_needed_document,$service_location,$vehicle_type)
    {
        $this->driver = $driver;
        $this->countries = $countries;
        $this->driver_needed_document = $driver_needed_document;
        $this->service_location = $service_location;
        $this->vehicle_type = $vehicle_type;
    }

    public function sheets(): array
    {
         return [
            new DriverBulkExport($this->driver,'Drivers List',$this->driver_needed_document),
            new CountryExport($this->countries, 'Countries List'),
            new ServiceLocationExport($this->service_location, 'ServiceLocation List'),
            new VehicleTypeExport($this->vehicle_type, 'VehicleType List'),
        ];
    }

}
