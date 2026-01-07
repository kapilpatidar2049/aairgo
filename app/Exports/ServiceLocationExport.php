<?php

namespace App\Exports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithTitle;

class ServiceLocationExport implements FromView, ShouldAutoSize, WithTitle
{
    use Exportable;
    /**
     *
     */
    public function __construct($service_location,$title) {
        $this->service_location = $service_location;
         $this->title = $title;
    }

    public function view(): View
    {
        return view('driver_bulk_uploads.service_location', [
            'results' => $this->service_location
        ]);
    }

     // Add sheet name
    public function title(): string
    {
        return $this->title;
    }
}
