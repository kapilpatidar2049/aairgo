<?php

namespace App\Console\Commands;
use Carbon\Carbon;
use App\Models\MailOtp;
use App\Models\Admin\PeakZone;
use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Database;
use Illuminate\Support\Facades\Log;

class ClearOtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
   protected $signature = 'clear:otp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '3 hours completed OTP Deleted';

    protected $database;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $currentTime = Carbon::now();


        $otps = MailOtp::where('created_at', '<', $currentTime)->get();


        $peak_current_time = $currentTime->timestamp;




      
        foreach ($otps as $otp) 
        {
            $created_time = strtotime($otp->created_at);

            $time =strtotime($currentTime);

            $difference = abs($time - $created_time)/3600;

                if ($difference >= 3)
                 {
                $otp->delete();
                }         
        }

        $peak_zones = PeakZone::whereRaw('UNIX_TIMESTAMP(end_time) < ?', [$currentTime->timestamp])->get();

        foreach ($peak_zones as $peakZone) {
            $this->database->getReference('peak-zones/'.$peakZone->id)->remove();
            $peakZone->delete();
        }

       $this->info(' OTP Records cleard ');
    }
}