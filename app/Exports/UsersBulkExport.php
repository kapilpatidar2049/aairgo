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

class UsersBulkExport implements FromView, ShouldAutoSize, WithTitle, WithEvents
{
    use Exportable;
    /**
     *
     */
    public function __construct($users,$title) {
        $this->users = $users;
        $this->title = $title;
    }

    public function view(): View
    {
        return view('users_bulk', [
            'results' => $this->users
        ]);
    }
      // Add sheet name
    public function title(): string
    {
        return $this->title;
    }
     // Add events (for comments, formatting, etc.)
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Find the last row with data in column A (or any reliable column)
                $rowCount = $sheet->getHighestRow();

                // Loop through each row in column F (starting from 1, header included)
                for ($row = 1; $row <= $rowCount; $row++) {
                    $cell = 'E' . $row;
                    $sheet->getComment($cell)
                        ->getText()->createTextRun("Refer 'Countries List' sheet for valid values");
                }
            },
        ];
    }

}
