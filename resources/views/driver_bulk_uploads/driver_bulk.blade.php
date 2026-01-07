   <table>
        <thead>
            <tr>
                <th>ServiceLocation</th>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Gender</th>
                <th>Country</th>
                <th>Vehicle Type</th>
                <th>Transport Type</th>
                <th>CustomMake</th>
                <th>CustomModel</th>
                <th>CarColor</th>
                <th>CarNumber</th>
            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $driver)
            @php
            $email = $driver->email;
            $dial = $driver->user->countryDetail ? $driver->user->countryDetail->dial_code : '0';
            $mobile = $driver->mobile;
            if(env('APP_FOR') == 'demo'){
                $mobile = "**********";
                $email = "**********";
            }
            @endphp
                <tr>
                    <td>{{ $driver->service_location_id }}</td>
                    <td>{{ $driver->name }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $mobile }}</td>
                    <td>{{ $driver->gender }}</td>
                    <td>{{ $driver->country }}</td>
                    <td>{{ $driver->vehicle_type }}</td>
                    <td>{{ $driver->transport_type }}</td>
                    <td>{{ $driver->custom_make }}</td>
                    <td>{{ $driver->custom_model }}</td>
                    <td>{{ $driver->car_color }}</td>
                    <td>{{ $driver->car_number }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="11">
                        <h4 class="text-center" style="color:#333;font-size:25px;">No Data Found</h4>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
