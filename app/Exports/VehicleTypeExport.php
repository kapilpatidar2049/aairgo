<?php

namespace App\Exports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithTitle;

class VehicleTypeExport implements FromView, ShouldAutoSize, WithTitle
{
    use Exportable;
    /**
     *
     */
    public function __construct($vehicle_type,$title) {
        $this->vehicle_type = $vehicle_type;
         $this->title = $title;
    }

    public function view(): View
    {
        return view('driver_bulk_uploads.vehicle_type', [
            'results' => $this->vehicle_type
        ]);
    }

     // Add sheet name
    public function title(): string
    {
        return $this->title;
    }
}
