   <table>
        <thead>
            <tr>
                <th>Id</th>
                <th>Country Name</th>
                <th>Dail Code</th>
                <th>Status</th>

            </tr>
        </thead>
        <tbody>
             @forelse($results as $key => $country)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $country['Country Name'] }}</td>
                    <td>{{ $country['Code'] }}</td>
                    <td>{{ $country['Status'] }}</td>

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
