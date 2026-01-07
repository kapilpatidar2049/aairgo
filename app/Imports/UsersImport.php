<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Storage;
class UsersImport implements ToCollection, WithHeadingRow
{
    public $errors = [];
    public $validData = [];

    public function collection(Collection $rows)
    {
        $counter = 1;
        $seenEmails = [];
        $seenMobiles = [];

        foreach ($rows as $row) {
            $rowArray = [
                'Name'   => $row['name'] ?? null,
                'Email'  => $row['email'] ?? null,
                'Mobile' => $row['mobile'] ?? null,
                'Gender' => $row['gender'] ?? null,
                'Country' => $row['country'] ?? null,
            ];

            // Check if row is completely empty
            if (empty(array_filter($rowArray))) {
                continue;
            }

            //  Validate row
            $validator = Validator::make($rowArray, [
                'Name'   => 'required|string|max:255',
                'Mobile' => 'required|max:15|unique:users,mobile',
                'Gender' => 'required',
                'Country' => 'required',
            ]);

            if ($validator->fails()) {
                //  Store row with error
                $rowArray['Error'] = implode(", ", $validator->errors()->all());
                $this->errors[] = $rowArray;
            } else {
                //  Store valid row
                $this->validData[] = $rowArray;

                  // Directly save to DB
                $user = User::create([
                    'name'       => $rowArray['Name'],
                    'email'      => $rowArray['Email'],
                    'mobile'     => $rowArray['Mobile'],
                    'active'     => 1,
                    'gender'     => $rowArray['Gender'],
                    'country'     => $rowArray['Country'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                $user->userWallet()->create(['amount_added'=>0]);

                // Attach role
                $user->attachRole('user');
            }
        }
    }
}
