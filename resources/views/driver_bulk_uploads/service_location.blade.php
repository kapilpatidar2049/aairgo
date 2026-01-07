   <table>
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th>Currency Code</th>
                <th>Currency Name</th>
                <th>Currency Pointer</th>
                <th>Currency Symbol</th>
                <th>Timezone</th>

            </tr>
        </thead>
        <tbody>
             @forelse($results as $key => $service_location)
                <tr>
                    <td>{{ $service_location->id }}</td>
                    <td>{{ $service_location->name }}</td>
                    <td>{{ $service_location->currency_code }}</td>
                    <td>{{ $service_location->currency_name }}</td>
                    <td>{{ $service_location->currency_pointer }}</td>
                    <td>{{ $service_location->currency_symbol }}</td>
                    <td>{{ $service_location->timezone }}</td>

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
