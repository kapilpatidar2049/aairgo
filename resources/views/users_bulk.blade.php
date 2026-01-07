   <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Gender</th>
                <th>Country</th>

            </tr>
        </thead>
        <tbody>
            @php $i= 1; @endphp

            @forelse($results as $key => $user)
            @php
            $email = $user->email;
            $dial = $user->countryDetail ? $user->countryDetail->dial_code : '0';
            $mobile = $user->mobile;
            if(env('APP_FOR') == 'demo'){
                $mobile = "**********";
                $email = "**********";
            }
            @endphp
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $mobile }}</td>
                   <td>{{ $user->gender }}</td>
                    <td>{{ $user->country }}</td>

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
