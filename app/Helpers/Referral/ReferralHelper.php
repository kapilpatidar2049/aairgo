<?php

namespace App\Helpers\Referral;

use App\Models\Referral;
use App\Models\Request\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Base\Constants\Masters\WalletRemarks;
use App\Jobs\Notifications\AndroidPushNotification;
use App\Jobs\Notifications\SendPushNotification;
use Illuminate\Support\Facades\Log;
use App\Models\Request\RequestBill;
use App\Models\Admin\Driver;

class ReferralHelper
{




public static function checkAndApplyReferral(User $user)
{
    $referral = Referral::where('referred_user_id', $user->id)
        ->where('status', 'pending')
        ->first();

    if (!$referral) {
        return false;
    }

    $referralType = $referral->referral_type;

    // --- Config flags
    $enableByRideCount = (int)(get_settings('enable_referral_condition_by_ride_count') ?? 0);
    $enableByEarning   = (int)(get_settings('enable_referral_condition_by_earning') ?? 0);

    // --- Thresholds (user + driver)
    $userRideCountCond   = (int)(get_settings('referral_condition_user_ride_count') ?? 0);
    $driverRideCountCond = (int)(get_settings('referral_condition_driver_ride_count') ?? 0);
    $userSpentCond       = (float)(get_settings('referral_condition_user_spent_amount') ?? 0);
    $driverEarnCond      = (float)(get_settings('referral_condition_driver_earning_amount') ?? 0);

    $isDriver = $user->hasRole('driver');
    $isUser   = $user->hasRole('user');
    $conditionsMet = false;

    $referrer = User::find($referral->referrer_id);
    $referrerIsDriver = $referrer && $referrer->hasRole('driver');

    // Load correct condition keys
$userRideCountCond   = (int) (get_settings('referral_condition_user_ride_count') ?? 0);
$userSpentCond       = (float)(get_settings('referral_condition_user_spent_amount') ?? 0);

$driverUserRideCond  = (int) (get_settings('driver_referral_condition_user_ride_count') ?? 0);
$driverUserSpentCond = (float)(get_settings('driver_referral_condition_user_spent_amount') ?? 0);

$driverRideCountCond = (int) (get_settings('referral_condition_driver_ride_count') ?? 0);
$driverEarnCond      = (float)(get_settings('referral_condition_driver_earning_amount') ?? 0);

// Decide which thresholds apply
$requiredRideCount = 0;
$requiredSpentOrEarn = 0;

// -----------------------------------------------------------
// CASE 1: USER → NEW USER
// -----------------------------------------------------------
if ($referrer && $referrer->hasRole('user') && $isUser) {
    $requiredRideCount = $userRideCountCond;
    $requiredSpentOrEarn = $userSpentCond;
}

// -----------------------------------------------------------
// CASE 2: USER → NEW DRIVER
// -----------------------------------------------------------
elseif ($referrer && $referrer->hasRole('user') && $isDriver) {
    $requiredRideCount = $driverRideCountCond;
    $requiredSpentOrEarn = $driverEarnCond;
}

// -----------------------------------------------------------
// CASE 3: DRIVER → NEW USER   ❗(THIS IS THE FIX)
// -----------------------------------------------------------
elseif ($referrer && $referrer->hasRole('driver') && $isUser) {
    $requiredRideCount = $driverUserRideCond;      // FIX
    $requiredSpentOrEarn = $driverUserSpentCond;   // FIX
}

// -----------------------------------------------------------
// CASE 4: DRIVER → NEW DRIVER
// -----------------------------------------------------------
elseif ($referrer && $referrer->hasRole('driver') && $isDriver) {
    $requiredRideCount = $driverRideCountCond;
    $requiredSpentOrEarn = $driverEarnCond;
}

// Ride count condition
if ($enableByRideCount === 1) {

    if ($isUser) {
        $completedRides = Request::where('user_id', $user->id)
            ->where('is_completed', 1)
            ->count();

    } elseif ($isDriver && $user->driver) {
        $completedRides = Request::where('driver_id', $user->driver->id)
            ->where('is_completed', 1)
            ->count();
    } else {
        $completedRides = 0;
    }

    if ($completedRides >= $requiredRideCount) {
        $conditionsMet = true;
    }
}

// Earning/spent condition
if (!$conditionsMet && $enableByEarning === 1) {

    if ($isUser) {
        $totalSpent = DB::table('request_bills')
            ->join('requests', 'request_bills.request_id', '=', 'requests.id')
            ->where('requests.user_id', $user->id)
            ->where('requests.is_completed', 1)
            ->sum('request_bills.total_amount');

        if ($totalSpent >= $requiredSpentOrEarn) {
            $conditionsMet = true;
        }

    } elseif ($isDriver && $user->driver) {

        $totalEarned = DB::table('request_bills')
            ->join('requests', 'request_bills.request_id', '=', 'requests.id')
            ->where('requests.driver_id', $user->driver->id)
            ->where('requests.is_completed', 1)
            ->sum('request_bills.driver_commision');

        if ($totalEarned >= $requiredSpentOrEarn) {
            $conditionsMet = true;
        }
    }
}



    /**
     * ==========================================================
     * NEW: Handle INSTANT + DUAL_INSTANT referral types
     * (These require NO condition checks)
     * ==========================================================
     */
    if (in_array($referralType, ['instant', 'dual_instant'])) {

        $referrer = User::find($referral->referrer_id);
        if (!$referrer) return false;

        $credited = ['referrer' => 0.0, 'new' => 0.0];

        DB::beginTransaction();
        try {
     
            if ($referral->referrer_amount > 0) {

                if ($referrer->hasRole('driver') && $referrer->driver) {
                    // DRIVER WALLET
                    $wallet = $referrer->driver->driverWallet ?: $referrer->driver->driverWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
                    $wallet->amount_added += $referral->referrer_amount;
                    $wallet->amount_balance += $referral->referrer_amount;
                    $wallet->save();
            
                    $referrer->driver->driverWalletHistory()->create([
                        'amount' => $referral->referrer_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
            
                } else {
                    // USER WALLET (original code)
                    $wallet = $referrer->userWallet ?: $referrer->userWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
                    $wallet->amount_added += $referral->referrer_amount;
                    $wallet->amount_balance += $referral->referrer_amount;
                    $wallet->save();
            
                    $referrer->userWalletHistory()->create([
                        'amount' => $referral->referrer_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }
            
                $credited['referrer'] = $referral->referrer_amount;
            }

            // --- Credit referred user (driver or user) ---
            if ($referral->new_amount > 0) {
                if ($isDriver && $user->driver) {
                    $wallet = $user->driver->driverWallet ?: $user->driver->driverWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
                    $wallet->amount_added += $referral->new_amount;
                    $wallet->amount_balance += $referral->new_amount;
                    $wallet->save();

                    $user->driver->driverWalletHistory()->create([
                        'amount' => $referral->new_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                } else {
                    $wallet = $user->userWallet ?: $user->userWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
                    $wallet->amount_added += $referral->new_amount;
                    $wallet->amount_balance += $referral->new_amount;
                    $wallet->save();

                    $user->userWalletHistory()->create([
                        'amount' => $referral->new_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }

                $credited['new'] = $referral->new_amount;
            }

            // Mark referral as credited
            $referral->update([
                'status' => 'credited',
                'meta' => array_merge($referral->meta ?? [], [
                    'credited_at' => now()->toDateTimeString(),
                ]),
            ]);

            DB::commit();

            // Notifications
            try {
                if ($credited['referrer'] > 0) {
                    dispatch(new SendPushNotification($referrer, 'Referral Bonus', 'You earned a referral reward!'));
                }
                if ($credited['new'] > 0) {
                    dispatch(new SendPushNotification($user, 'Referral Bonus', 'You earned a referral bonus for signing up!'));
                }

                SendReferralMailNotification::dispatch($user, $referrer);
            } catch (\Throwable $e) {
                \Log::warning('ReferralHelper notification failed: ' . $e->getMessage());
            }

            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('ReferralHelper error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ==========================================================
     * Existing CONDITIONAL + DUAL_CONDITIONAL logic (unchanged)
     * ==========================================================
     */
    // if (


    if (in_array($referralType, ['conditional', 'dual_conditional']) && $conditionsMet) {

        $referrer = User::find($referral->referrer_id);
        if (!$referrer) return false;
    
        $credited = ['referrer' => 0.0, 'new' => 0.0];
    
        DB::beginTransaction();
        try {
    
            /**
             * ------------------------------------------------------
             * 1️⃣ Credit REFERRER (user or driver)
             * ------------------------------------------------------
             */
            if ($referral->referrer_amount > 0) {
    
                if ($referrer->hasRole('driver') && $referrer->driver) {
    
                    // DRIVER WALLET
                    $wallet = $referrer->driver->driverWallet ?: $referrer->driver->driverWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
    
                    $wallet->amount_added += $referral->referrer_amount;
                    $wallet->amount_balance += $referral->referrer_amount;
                    $wallet->save();
    
                    $referrer->driver->driverWalletHistory()->create([
                        'amount' => $referral->referrer_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
    
                } else {
    
                    // USER WALLET
                    $wallet = $referrer->userWallet ?: $referrer->userWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
    
                    $wallet->amount_added += $referral->referrer_amount;
                    $wallet->amount_balance += $referral->referrer_amount;
                    $wallet->save();
    
                    $referrer->userWalletHistory()->create([
                        'amount' => $referral->referrer_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }
    
                $credited['referrer'] = $referral->referrer_amount;
            }
    
    
            /**
             * ------------------------------------------------------
             * 2️⃣ Credit NEW USER (driver or user)
             * ------------------------------------------------------
             */
            if ($referral->new_amount > 0) {
    
                if ($user->hasRole('driver') && $user->driver) {
    
                    // NEW DRIVER WALLET
                    $wallet = $user->driver->driverWallet ?: $user->driver->driverWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
                    $wallet->amount_added += $referral->new_amount;
                    $wallet->amount_balance += $referral->new_amount;
                    $wallet->save();
    
                    $user->driver->driverWalletHistory()->create([
                        'amount' => $referral->new_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
    
                } else {
    
                    // NEW USER WALLET
                    $wallet = $user->userWallet ?: $user->userWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);
                    $wallet->amount_added += $referral->new_amount;
                    $wallet->amount_balance += $referral->new_amount;
                    $wallet->save();
    
                    $user->userWalletHistory()->create([
                        'amount' => $referral->new_amount,
                        'transaction_id' => Str::upper(Str::random(6)),
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }
    
                $credited['new'] = $referral->new_amount;
            }
    
    
            /**
             * ------------------------------------------------------
             * 3️⃣ Mark as credited
             * ------------------------------------------------------
             */
            $referral->update([
                'status' => 'credited',
                'meta' => array_merge($referral->meta ?? [], [
                    'credited_at' => now()->toDateTimeString(),
                ]),
            ]);
    
            DB::commit();
    
    
            /**
             * ------------------------------------------------------
             * 4️⃣ Notifications (same as your code)
             * ------------------------------------------------------
             */
            try {
                if ($credited['referrer'] > 0) {
                    dispatch(new SendPushNotification($referrer, 'Referral Bonus', 'You earned a referral reward!'));
                }
                if ($credited['new'] > 0) {
                    dispatch(new SendPushNotification($user, 'Referral Bonus', 'You earned a referral bonus for signing up!'));
                }
    
                SendReferralMailNotification::dispatch($user, $referrer);
            } catch (\Throwable $e) {
                \Log::warning('ReferralHelper notification failed: ' . $e->getMessage());
            }
    
            return true;
    
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('ReferralHelper error: ' . $e->getMessage());
            return false;
        }
    }

    return false;
}





public static function checkAndApplyDriverReferral(User $user)
{
    $driver = $user->driver;
    $driverId = $driver ? $driver->id : null;

    $referral = Referral::where('status', 'pending')
        ->where('referred_user_id', $user->id)
        ->first();

    if (!$referral) {
        return false;
    }

    $referralType = $referral->referral_type;

    $referrer = User::find($referral->referrer_id);
    if (!$referrer) {
        return false;
    }

    $isUserReferrer   = $referrer->hasRole('user');
    $isDriverReferrer = $referrer->hasRole('driver');

    $conditionsMet = false;
    $completedRides = 0;
    $totalEarning = 0;

    if ($referralType === 'instant' || $referralType === 'dual_instant') {
        $conditionsMet = true;
    }

    if (!$conditionsMet) {
        if ($isUserReferrer) {

            $enableRideCond = (int)get_settings('enable_referral_condition_by_ride_count');
            $enableEarnCond = (int)get_settings('enable_referral_condition_by_earning');

            $rideTarget  = (int)get_settings('referral_condition_driver_ride_count');
            $earnTarget  = (float)get_settings('referral_condition_driver_earning_amount');

            if ($enableRideCond == 1 && $rideTarget > 0) {
                $completedRides = Request::where('driver_id', $driverId)
                    ->where('is_completed', 1)
                    ->count();

                if ($completedRides >= $rideTarget) {
                    $conditionsMet = true;
                }
            }

            if (!$conditionsMet && $enableEarnCond == 1 && $earnTarget > 0) {
                $totalEarning = DB::table('request_bills')
                    ->join('requests', 'request_bills.request_id', '=', 'requests.id')
                    ->where('requests.driver_id', $driverId)
                    ->where('requests.is_completed', 1)
                    ->sum('request_bills.driver_commision');

                if ($totalEarning >= $earnTarget) {
                    $conditionsMet = true;
                }
            }

        } else {

            $enableRideCond = (int)get_settings('enable_driver_referral_condition_by_ride_count');
            $enableEarnCond = (int)get_settings('enable_driver_referral_condition_by_earning');

            $rideTarget  = (int)get_settings('driver_referral_condition_driver_ride_count');
            $earnTarget  = (float)get_settings('driver_referral_condition_driver_earning_amount');

            if ($enableRideCond == 1 && $rideTarget > 0) {
                $completedRides = Request::where('driver_id', $driverId)
                    ->where('is_completed', 1)
                    ->count();

                if ($completedRides >= $rideTarget) {
                    $conditionsMet = true;
                }
            }

            if (!$conditionsMet && $enableEarnCond == 1 && $earnTarget > 0) {
                $totalEarning = DB::table('request_bills')
                    ->join('requests', 'request_bills.request_id', '=', 'requests.id')
                    ->where('requests.driver_id', $driverId)
                    ->where('requests.is_completed', 1)
                    ->sum('request_bills.driver_commision');

                if ($totalEarning >= $earnTarget) {
                    $conditionsMet = true;
                }
            }
        }
    }

    if (!$conditionsMet) {
        $referral->update([
            'meta' => array_merge($referral->meta ?? [], [
                'last_checked_at' => now()->toDateTimeString(),
                'completed_rides' => $completedRides,
                'total_earning' => $totalEarning,
            ])
        ]);
        return false;
    }

    DB::beginTransaction();
    try {
        $credited = ['referrer' => 0.0, 'new' => 0.0];

        if ($referral->referrer_amount > 0) {
            if ($isDriverReferrer) {
                $rDriver = $referrer->driver;

                $wallet = $rDriver->driverWallet ?: $rDriver->driverWallet()->create([
                    'amount_added' => 0,
                    'amount_balance' => 0,
                ]);

                $wallet->increment('amount_added', $referral->referrer_amount);
                $wallet->increment('amount_balance', $referral->referrer_amount);

                $rDriver->driverWalletHistory()->create([
                    'transaction_id' => Str::upper(Str::random(8)),
                    'amount' => $referral->referrer_amount,
                    'remarks' => WalletRemarks::REFERRAL_COMMISION,
                    'is_credit' => true,
                ]);
            } else {
                $wallet = $referrer->userWallet ?: $referrer->userWallet()->create([
                    'amount_added' => 0,
                    'amount_balance' => 0,
                ]);

                $wallet->increment('amount_added', $referral->referrer_amount);
                $wallet->increment('amount_balance', $referral->referrer_amount);

                $referrer->userWalletHistory()->create([
                    'transaction_id' => Str::upper(Str::random(8)),
                    'amount' => $referral->referrer_amount,
                    'remarks' => WalletRemarks::REFERRAL_COMMISION,
                    'is_credit' => true,
                ]);
            }

            $credited['referrer'] = $referral->referrer_amount;
        }

        if ($referral->new_amount > 0) {
            $d = $driver;

            $wallet = $d->driverWallet ?: $d->driverWallet()->create([
                'amount_added' => 0,
                'amount_balance' => 0,
            ]);

            $wallet->increment('amount_added', $referral->new_amount);
            $wallet->increment('amount_balance', $referral->new_amount);

            $d->driverWalletHistory()->create([
                'transaction_id' => Str::upper(Str::random(8)),
                'amount' => $referral->new_amount,
                'remarks' => WalletRemarks::REFERRAL_COMMISION,
                'is_credit' => true,
            ]);

            $credited['new'] = $referral->new_amount;
        }

        $referral->update([
            'status' => 'credited',
            'meta' => array_merge($referral->meta ?? [], [
                'credited_at' => now()->toDateTimeString(),
                'completed_rides' => $completedRides,
                'total_earning' => $totalEarning,
            ]),
        ]);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Driver Referral Conditional Error: ' . $e->getMessage());
        return false;
    }

    try {
        if ($credited['referrer'] > 0) {
            dispatch(new SendPushNotification(
                $referrer,
                'Referral Bonus',
                'You received a referral reward!'
            ));
        }

        if ($credited['new'] > 0) {
            dispatch(new SendPushNotification(
                $user,
                'Referral Bonus',
                'You earned a referral bonus!'
            ));
        }

        SendReferralMailNotification::dispatch($user, $referrer);
    } catch (\Throwable $e) {}

    return true;
}


}
