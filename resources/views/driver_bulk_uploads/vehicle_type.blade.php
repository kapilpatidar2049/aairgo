   <table>
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Trip Dispatch Type</th>
                <th>Transport Type</th>
                <th>Capacity</th>
                <th>Size</th>

            </tr>
        </thead>
        <tbody>
             @forelse($results as $key => $vehicle_type)
                <tr>
                    <td>{{ $vehicle_type->id }}</td>
                    <td>{{ $vehicle_type->name }}</td>
                    <td>{{ $vehicle_type->trip_dispatch_type }}</td>
                    <td>{{ $vehicle_type->is_taxi }}</td>
                    <td>{{ $vehicle_type->capacity }}</td>
                    <td>{{ $vehicle_type->size }}</td>

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
