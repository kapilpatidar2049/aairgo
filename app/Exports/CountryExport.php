<?php

namespace App\Exports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithTitle;

class CountryExport implements FromView, ShouldAutoSize, WithTitle
{
    use Exportable;
    /**
     *
     */
    public function __construct($countries,$title) {
        $this->countries = $countries;
        $this->title = $title;
    }

    public function view(): View
    {
        return view('country', [
            'results' => $this->countries
        ]);
    }

     // Add sheet name
    public function title(): string
    {
        return $this->title;
    }
}
