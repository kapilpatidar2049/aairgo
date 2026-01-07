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

class DriverImport implements ToCollection, WithHeadingRow
{

    private $parent;

    public function __construct($parent)
    {
        $this->parent = $parent; // pass reference to store errors/validData
    }

    public function collection(Collection $rows)
    {
        $counter = 1;
        $seenEmails = [];
        $seenMobiles = [];

            foreach ($rows as $row) {
        $rowArray = [
            'ServiceLocation' => $row['servicelocation'] ?? null,
            'Name'   => $row['name'] ?? null,
            'Email'  => $row['email'] ?? null,
            'Mobile' => $row['mobile'] ?? null,
            'Gender' => $row['gender'] ?? null,
            'Country' => $row['country'] ?? null,
            'VehicleType' => $row['vehicle_type'] ?? null,
            'TransportType' => $row['transport_type'] ?? null,
            'CustomMake' => $row['custommake'] ?? null,
            'CustomModel' => $row['custommodel'] ?? null,
            'CarColor' => $row['carcolor'] ?? null,
            'CarNumber' => $row['carnumber'] ?? null,
        ];

        // Skip completely empty rows
        if (empty(array_filter($rowArray))) {
            continue;
        }
        // Validate row
        $validator = Validator::make($rowArray, [
            'ServiceLocation' => 'required',
            'Name'   => 'required|string|max:255',
            'Mobile' => 'required|max:15|unique:drivers,mobile',
            'Email'  => 'nullable|email|unique:drivers,email',
            'Gender' => 'required',
            'Country' => 'required',
            'VehicleType' => 'required',
            'TransportType' => 'required',
            'CustomMake' => 'required',
            'CustomModel' => 'required',
            'CarColor' => 'required',
            'CarNumber' => 'required',
        ]);

        if ($validator->fails()) {
            // Collect validation errors
            $rowArray['Error'] = implode(", ", $validator->errors()->all());
            $this->parent->errors[] = $rowArray;
            continue; // Skip DB insert
        }

        // Store valid row
        $this->parent->validData[] = $rowArray;

        // Wrap DB save in try–catch for safety
        try {
            $user = User::create([
                'name'       => $rowArray['Name'],
                'email'      => $rowArray['Email'],
                'mobile'     => $rowArray['Mobile'],
                'country'    => $rowArray['Country'],
                'mobile_confirmed' => true,
                'active' => 1,
                'refferal_code' => str_random(6),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $driver = Driver::create([
                'name'       => $rowArray['Name'],
                'email'      => $rowArray['Email'],
                'mobile'     => $rowArray['Mobile'],
                'country'    => $rowArray['Country'],
                'service_location_id' => $rowArray['ServiceLocation'],
                'vehicle_type'   => $rowArray['VehicleType'],
                'car_color'      => $rowArray['CarColor'],
                'car_number'     => $rowArray['CarNumber'],
                'transport_type' => $rowArray['TransportType'],
                'gender'         => $rowArray['Gender'],
                'custom_model'   => $rowArray['CustomModel'],
                'custom_make'    => $rowArray['CustomMake'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'user_id' =>  $user->id,
                'active' => 1,
                'approve' => 0
            ]);

            $driver->driverWallet()->create(['amount_added'=>0]);
            $driver->user->rewardPoint()->create(['amount'=>0]);
            $driver->driverVehicleTypeDetail()->create(['vehicle_type'=>$driver->vehicle_type]); 

            $user->attachRole('driver');

        } catch (\Exception $e) {
            // Collect DB-related errors
            $rowArray['Error'] = "Database error: " . $e->getMessage();
            $this->parent->errors[] = $rowArray;
        }

    } 
    }
}
