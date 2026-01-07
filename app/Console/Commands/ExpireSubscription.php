<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin\SubscriptionDetail;
use App\Models\Admin\SubscriptionDiscount;
use App\Models\Admin\Driver;
use App\Jobs\Notifications\SendPushNotification;
use Carbon\Carbon;
use Log;

class ExpireSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscription plans are checked and expired';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $conditional_time = Carbon::today();
        $expired = SubscriptionDetail::leftJoin('drivers','subscription_details.id','=','drivers.subscription_detail_id')
                    ->where('drivers.is_subscribed',true)
                    ->whereDate('subscription_details.expired_at', '<=', $conditional_time)
                    ->get();
                    
        foreach ($expired as $key => $plan) {
            $driver = Driver::where('id',$plan->driver_id)->first();
            Driver::where('id',$plan->driver_id)->update(['is_subscribed'=>false]);

            // $title = trans('push_notifications.subscription_expired_title',[],$driver->user->lang);
            // $body = trans('push_notifications.subscription_expired_body',[],$driver->user->lang);

            // dispatch(new SendPushNotification($driver->user,$title,$body));
            $this->info('Expired successfully');
        }
        $this->info('Subscription Expired successfully');

    }
}
