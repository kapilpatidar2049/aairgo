<?php

namespace App\Http\Controllers\Api\V1\Auth\Registration;

use App\Models\User;
use Illuminate\Http\Request;
use App\Base\Constants\Auth\Role;
use App\Transformers\User\UserTransformer;
use App\Base\Constants\Masters\WalletRemarks;
use App\Transformers\User\ReferralTransformer;
use App\Http\Controllers\Api\V1\BaseController;
use App\Jobs\Notifications\AndroidPushNotification;
use App\Jobs\Notifications\SendPushNotification;
use Illuminate\Support\Facades\Log;
use App\Mail\ReferralMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\Mails\SendReferralMailNotification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Request\Request as RideRequest;
// use App\Models\Request\Request;
use App\Base\Filters\Admin\RequestFilter;
use App\Models\Request\RequestBill;
use App\Models\Referral;
use App\Models\Admin\Driver;

/**
 * @group SignUp-And-Otp-Validation
 *
 * APIs for User-Management
 */
class ReferralController extends BaseController
{
    /**
     * The user model instance.
     *
     * @var \App\Models\User
     */
    protected $user;


    /**
     * ReferralController constructor.
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
    * Get Referral code
    * @responseFile responses/auth/get-referral.json
    */
    public function index()
    {
        $user = fractal(auth()->user(), new ReferralTransformer);

        return $this->respondOk($user);
    }
    /**
    * Update User Referral
    * @bodyParam refferal_code string required refferal_code of the another user
    * @response {"success":true,"message":"success"}
    */

// user to new user working code

public function updateUserReferral(Request $request)
{
    $request->validate([
        'refferal_code' => 'required|string'
    ]);

    $referrer = $this->user->where('refferal_code', $request->refferal_code)->first();
    if (!$referrer) {
        return $this->respondFailed('Invalid referral code.');
    }

    $authUser = auth()->user();

    if ($referrer->id === $authUser->id) {
        return $this->respondFailed('You cannot use your own referral code.');
    }

    if ($authUser->referred_by && $authUser->referred_by != $referrer->id) {
        return $this->respondFailed('Referral code already applied.');
    }

    $referralType = (string)(
        $referrer->hasRole('user')
            ? (get_settings('referral_type') ?: 'instant')
            : (get_settings('driver_referral_type') ?: 'instant')
    );

    // Configs
    $enableByRideCount = (int)(get_settings('enable_referral_condition_by_ride_count') ?? 0);
    $enableByEarning   = (int)(get_settings('enable_referral_condition_by_earning') ?? 0);

    $userRideCountCond   = (int)(get_settings('referral_condition_user_ride_count') ?? 0);
    $driverRideCountCond = (int)(get_settings('referral_condition_driver_ride_count') ?? 0);
    $userSpentCond       = (float)(get_settings('referral_condition_user_spent_amount') ?? 0);
    $driverEarnCond      = (float)(get_settings('referral_condition_driver_earning_amount') ?? 0);

    // Base referrer amounts
    $referrerAmount      = (float)(get_settings('referral_commission_amount_for_user') ?? 0);

    // Base new user/driver amounts
    $newUserCommission   = (float)(get_settings('referral_commission_for_new_user_from_referer_user') ?? 0);
    $newDriverCommission = (float)(get_settings('referral_commission_for_new_driver_from_referer_user') ?? 0);

    // ⭐ NEW — For driver → new user referrals
    $driverReferrerAmount = (float)(get_settings('referral_commission_amount_for_driver') ?? 0);
    $newUserFromDriver    = (float)(get_settings('referral_commission_for_new_user_from_referer_driver') ?? 0);
    // (This does NOT affect existing user→user or user→driver logic)

    $isNewDriver = $authUser->hasRole('driver');
    $isNewUser   = $authUser->hasRole('user');

    $referredUserId = $isNewDriver && $authUser->driver
        ? $authUser->driver->id
        : $authUser->id;

    // ⭐ ORIGINAL LOGIC — unchanged (dual → gives bonus to new user/driver)
    $newAmount = in_array($referralType, ['dual_instant', 'dual_conditional'])
        ? ($isNewDriver ? $newDriverCommission : $newUserCommission)
        : 0.0;

    // ⭐ NEW — Override ONLY when referrer = driver & new = user & dual-type
    if ($referrer->hasRole('driver') && $isNewUser && in_array($referralType, ['dual_instant', 'dual_conditional'])) {
        $newAmount = $newUserFromDriver;   // driver → new user referral bonus
    }

    // ⭐ NEW — Override referrer amount only when referrer = driver
    if ($referrer->hasRole('driver')) {
        $referrerAmount = $driverReferrerAmount;
    }

    // Create referral
    $referral = Referral::firstOrCreate(
        [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUserId,
        ],
        [
            'referral_type' => $referralType,
            'referrer_amount' => $referrerAmount,
            'new_amount' => $newAmount,
            'status' => 'pending',
            'meta' => ['applied_at' => now()->toDateTimeString()],
        ]
    );

    if (!$authUser->referred_by) {
        $authUser->update(['referred_by' => $referrer->id]);
    }

    // Condition checker — unchanged
    $checkConditions = function () use (
        $enableByRideCount, $enableByEarning,
        $userRideCountCond, $driverRideCountCond,
        $userSpentCond, $driverEarnCond,
        $isNewUser, $isNewDriver, $authUser
    ) {
        $conditionsMet = false;

        if ($enableByRideCount === 1) {
            if ($isNewUser) {
                $rides = RideRequest::where('user_id', $authUser->id)
                    ->where('is_completed', 1)
                    ->count();
                if ($rides >= $userRideCountCond) $conditionsMet = true;
            } elseif ($isNewDriver && $authUser->driver) {
                $rides = RideRequest::where('driver_id', $authUser->driver->id)
                    ->where('is_completed', 1)
                    ->count();
                if ($rides >= $driverRideCountCond) $conditionsMet = true;
            }
        }

        if ($enableByEarning === 1 && !$conditionsMet) {
            if ($isNewUser) {
                $spent = DB::table('request_bills')
                    ->join('requests', 'request_bills.request_id', '=', 'requests.id')
                    ->where('requests.user_id', $authUser->id)
                    ->where('requests.is_completed', 1)
                    ->sum('request_bills.total_amount');
                if ($spent >= $userSpentCond) $conditionsMet = true;
            } elseif ($isNewDriver && $authUser->driver) {
                $earnings = DB::table('request_bills')
                    ->join('requests', 'request_bills.request_id', '=', 'requests.id')
                    ->where('requests.driver_id', $authUser->driver->id)
                    ->where('requests.is_completed', 1)
                    ->sum('request_bills.driver_commision');
                if ($earnings >= $driverEarnCond) $conditionsMet = true;
            }
        }

        return $conditionsMet;
    };

    $applyNow = in_array($referralType, ['instant', 'dual_instant'])
        ? true
        : $checkConditions();

    if ($referral->status === 'credited') {
        return $this->respondSuccess('Referral already credited.');
    }

    if (!$applyNow && in_array($referralType, ['conditional', 'dual_conditional'])) {
        $referral->update([
            'meta' => array_merge($referral->meta ?? [], ['last_checked_at' => now()->toDateTimeString()]),
        ]);
        return $this->respondSuccess('Referral recorded. Pending conditions to meet.');
    }

    DB::beginTransaction();
    try {
        // CREDIT REFERRER — unchanged (driver OR user wallet)
        if ($referrerAmount > 0 && in_array($referralType, ['dual_instant', 'instant'])) {
            if ($referrer->hasRole('driver')) {
                $driver = Driver::where('user_id', $referrer->id)->first();
                if ($driver) {
                    $wallet = $driver->driverWallet
                        ?: $driver->driverWallet()->create(['amount_added' => 0, 'amount_balance' => 0]);

                    $wallet->increment('amount_added', $referrerAmount);
                    $wallet->increment('amount_balance', $referrerAmount);

                    $driver->driverWalletHistory()->create([
                        'user_id' => $driver->id,
                        'transaction_id' => Str::upper(Str::random(8)),
                        'amount' => $referrerAmount,
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }
            } else {
                $wallet = $referrer->userWallet
                    ?: $referrer->userWallet()->create(['amount_added' => 0, 'amount_balance' => 0]);
                $wallet->increment('amount_added', $referrerAmount);
                $wallet->increment('amount_balance', $referrerAmount);

                $referrer->userWalletHistory()->create([
                    'transaction_id' => Str::upper(Str::random(8)),
                    'amount' => $referrerAmount,
                    'remarks' => WalletRemarks::REFERRAL_COMMISION,
                    'is_credit' => true,
                ]);
            }
        }

        // CREDIT REFERRED — unchanged (only newAmount changed earlier)
        if ($newAmount > 0 && in_array($referralType, ['dual_instant', 'instant'])) {
            if ($isNewDriver) {
                $driver = Driver::where('user_id', $authUser->id)->first();
                if ($driver) {
                    $wallet = $driver->driverWallet
                        ?: $driver->driverWallet()->create(['amount_added' => 0, 'amount_balance' => 0]);

                    $wallet->increment('amount_added', $newAmount);
                    $wallet->increment('amount_balance', $newAmount);

                    $driver->driverWalletHistory()->create([
                        'user_id' => $driver->id,
                        'transaction_id' => Str::upper(Str::random(8)),
                        'amount' => $newAmount,
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }
            } else {
                $wallet = $authUser->userWallet
                    ?: $authUser->userWallet()->create(['amount_added' => 0, 'amount_balance' => 0]);
                $wallet->increment('amount_added', $newAmount);
                $wallet->increment('amount_balance', $newAmount);

                $authUser->userWalletHistory()->create([
                    'transaction_id' => Str::upper(Str::random(8)),
                    'amount' => $newAmount,
                    'remarks' => WalletRemarks::REFERRAL_COMMISION,
                    'is_credit' => true,
                ]);
            }
        }

        $referral->update([
            'status' => 'credited',
            'meta' => array_merge($referral->meta ?? [], ['credited_at' => now()->toDateTimeString()]),
        ]);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('User referral credit error: ' . $e->getMessage());
        return $this->respondFailed('Failed to apply referral credits.');
    }

    try {
        if ($referrerAmount > 0)
            dispatch(new SendPushNotification($referrer, 'Referral Bonus', 'You earned a referral reward!'));
        if ($newAmount > 0)
            dispatch(new SendPushNotification($authUser, 'Referral Bonus', 'You earned a referral bonus for signing up!'));
        SendReferralMailNotification::dispatch($authUser, $referrer);
    } catch (\Throwable $e) {
        Log::warning('Referral notification failed: ' . $e->getMessage());
    }

    return $this->respondSuccess('Referral processed successfully.', [
        'referral_type' => $referralType,
        'status' => 'credited',
    ]);
}

    
    

    /**
    * Update Driver Referral code
    * @bodyParam refferal_code string required refferal_code of the another user
    * @response {"success":true,"message":"success"}
    */
   




public function updateDriverReferral(Request $request)
{
    $request->validate([
        'refferal_code' => 'required|string'
    ]);

    $referrer = $this->user->where('refferal_code', $request->refferal_code)->first();
    if (!$referrer) {
        $this->throwCustomException('Provided Referral code is not valid', 'refferal_code');
    }

    $authUser = auth()->user();

    if ($referrer->id === $authUser->id) {
        return $this->respondFailed('You cannot use your own referral code.');
    }

    if ($authUser->referred_by && $authUser->referred_by != $referrer->id) {
        return $this->respondFailed('Referral code already applied.');
    }

    $isReferrerDriver = $referrer->hasRole('driver');
    $isReferrerUser   = $referrer->hasRole('user');

    $isNewDriver = $authUser->hasRole('driver');
    $isNewUser   = $authUser->hasRole('user');

    if (!$isNewDriver) {
        return $this->respondFailed('Referral only applicable for drivers.');
    }

    if ($isReferrerDriver) {
        $referralType = get_settings('driver_referral_type') ?: 'instant';

        $referrerAmount      = (float)(get_settings('referral_commission_amount_for_driver') ?? 0);
        $newDriverCommission = (float)(get_settings('referral_commission_for_new_driver_from_referer_driver') ?? 0);

        $newAmount = in_array($referralType, ['dual_instant', 'dual_conditional'])
            ? $newDriverCommission
            : 0.0;

    } else {
        $referralType = get_settings('referral_type') ?: 'instant';

        $referrerAmount      = (float)(get_settings('referral_commission_amount_for_user') ?? 0);
        $newDriverCommission = (float)(get_settings('referral_commission_for_new_driver_from_referer_user') ?? 0);

        $newAmount = in_array($referralType, ['dual_instant', 'dual_conditional'])
            ? $newDriverCommission
            : 0.0;
    }

    $referral = Referral::firstOrCreate(
        [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $authUser->id,
        ],
        [
            'referral_type' => $referralType,
            'referrer_amount' => $referrerAmount,
            'new_amount' => $newAmount,
            'status' => 'pending',
            'meta' => ['applied_at' => now()->toDateTimeString()],
        ]
    );

    if (!$authUser->referred_by) {
        $authUser->update(['referred_by' => $referrer->id]);
    }

    $applyNow = in_array($referralType, ['instant', 'dual_instant']);

    if ($referral->status === 'credited') {
        return $this->respondSuccess('Referral already credited.', [
            'referral_type' => $referral->referral_type,
            'status' => $referral->status,
        ]);
    }

    if (!$applyNow && in_array($referralType, ['conditional', 'dual_conditional'])) {
        return $this->respondSuccess('Referral recorded. Pending conditions to meet.');
    }

    DB::beginTransaction();
    try {
        if ($referrerAmount > 0) {
            if ($isReferrerDriver) {
                $driver = Driver::where('user_id', $referrer->id)->first();
                if ($driver) {
                    $wallet = $driver->driverWallet ?: $driver->driverWallet()->create([
                        'amount_added' => 0,
                        'amount_balance' => 0
                    ]);

                    $wallet->increment('amount_added', $referrerAmount);
                    $wallet->increment('amount_balance', $referrerAmount);

                    $driver->driverWalletHistory()->create([
                        'user_id' => $driver->id,
                        'transaction_id' => Str::upper(Str::random(8)),
                        'amount' => $referrerAmount,
                        'remarks' => WalletRemarks::REFERRAL_COMMISION,
                        'is_credit' => true,
                    ]);
                }
            } else {
                $wallet = $referrer->userWallet ?: $referrer->userWallet()->create([
                    'amount_added' => 0,
                    'amount_balance' => 0
                ]);

                $wallet->increment('amount_added', $referrerAmount);
                $wallet->increment('amount_balance', $referrerAmount);

                $referrer->userWalletHistory()->create([
                    'transaction_id' => Str::upper(Str::random(8)),
                    'amount' => $referrerAmount,
                    'remarks' => WalletRemarks::REFERRAL_COMMISION,
                    'is_credit' => true,
                ]);
            }
        }

        if ($newAmount > 0) {
            $driver = $authUser->driver ?? Driver::where('user_id', $authUser->id)->first();

            $wallet = $driver->driverWallet ?: $driver->driverWallet()->create([
                'amount_added' => 0,
                'amount_balance' => 0
            ]);

            $wallet->increment('amount_added', $newAmount);
            $wallet->increment('amount_balance', $newAmount);

            $driver->driverWalletHistory()->create([
                'user_id' => $driver->id,
                'transaction_id' => Str::upper(Str::random(8)),
                'amount' => $newAmount,
                'remarks' => WalletRemarks::REFERRAL_COMMISION,
                'is_credit' => true,
            ]);
        }

        $referral->update([
            'status' => 'credited',
            'meta' => array_merge($referral->meta ?? [], [
                'credited_at' => now()->toDateTimeString(),
            ]),
        ]);

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Driver referral credit error: ' . $e->getMessage());
        return $this->respondFailed('Failed to apply referral credits.');
    }

    try {
        if ($referrerAmount > 0) {
            dispatch(new SendPushNotification($referrer, 'Referral Bonus', 'You earned a referral reward!'));
        }
        if ($newAmount > 0) {
            dispatch(new SendPushNotification($authUser, 'Referral Bonus', 'You earned a referral bonus for signing up!'));
        }
        SendReferralMailNotification::dispatch($authUser, $referrer);
    } catch (\Throwable $e) {}

    return $this->respondSuccess('Driver referral processed successfully.', [
        'referral_type' => $referralType,
        'status' => 'credited',
    ]);
}                                               




}
