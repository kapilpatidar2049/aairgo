<?php

namespace App\Exports;

use App\Models\User;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DriverBulkExport implements FromView, ShouldAutoSize, WithTitle, WithEvents
{
    use Exportable;
    /**
     *
     */
    public function __construct($driver,$title,$driver_needed_document) {
        $this->driver = $driver;
        $this->title = $title;
        $this->driver_needed_document = $driver_needed_document;
    }

    public function view(): View
    {
        return view('driver_bulk_uploads.driver_bulk', [
            'results' => $this->driver,
            'driver_needed_document' => $this->driver_needed_document,
        ]);
    }

     // Add sheet name
    public function title(): string
    {
        return $this->title;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Find the last row with data in column A (or any reliable column)
                $rowCount = $sheet->getHighestRow();

                // Column → Comment mapping
                $comments = [
                    'A' => "Refer 'ServiceLocation List' sheet for valid values",
                    'F' => "Refer 'Countries List' sheet for valid values",
                    'G' => "Refer 'VehicleType List' sheet for valid values",
                    'L' => "After the Uploading the Sheet, you can upload a document in mobile app or admin panel",
                ];

                // Loop through each mapping
                foreach ($comments as $column => $text) {
                    for ($row = 1; $row <= $rowCount; $row++) {
                        $cell = $column . $row;
                        $sheet->getComment($cell)
                            ->getText()->createTextRun($text);
                    }
                }
            },
        ];
    }
}
