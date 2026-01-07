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
                <th>Error</th>

            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $driver)
            @php
            $email = $driver['Email'];
            $mobile = $driver['Mobile'];
            if(env('APP_FOR') == 'demo'){
                $mobile = "**********";
                $email = "**********";
            }
            @endphp
                <tr>
                    <td>{{ $driver['ServiceLocation'] }}</td>
                    <td>{{ $driver['Name'] }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $mobile }}</td>
                    <td>{{ $driver['Gender'] }}</td>
                    <td>{{ $driver['Country'] }}</td>
                    <td>{{ $driver['VehicleType'] }}</td>
                    <td>{{ $driver['TransportType'] }}</td>
                    <td>{{ $driver['CustomMake'] }}</td>
                    <td>{{ $driver['CustomModel'] }}</td>
                    <td>{{ $driver['CarColor'] }}</td>
                    <td>{{ $driver['CarNumber'] }}</td>
                     <td>{{ $driver['Error'] }}</td>
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
