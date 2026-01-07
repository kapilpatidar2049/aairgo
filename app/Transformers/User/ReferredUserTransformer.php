<?php

namespace App\Transformers\User;

use App\Models\User;
use App\Base\Constants\Auth\Role;
use App\Models\Referral;
use App\Transformers\Transformer;

class ReferredUserTransformer extends Transformer
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected array $availableIncludes = [

    ];
    /**
     * Resources that can be included default.
     *
     * @var array
     */
    protected array $defaultIncludes = [

    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(User $user)
    {

        $referral = Referral::where('referrer_id',auth()->user()->id)->where('referred_user_id',$user->id)->first();
        $earning = 0;
        $status = null;
        $creditedAt = null;
        if($referral){
            $earning = $referral->referrer_amount ?? 0;
            $status = $referral->status;
            $meta = is_array($referral->meta) ? $referral->meta : json_decode($referral->meta, true);
            $creditedAt = $meta['credited_at'] ?? null;
        }
        return [
            'id' => $user->id,
            'name' => $user->name,
            'profile_picture' => $user->profile_picture,
            'earning' => $earning,
            'currency_symbol'=>$user->countryDetail->currency_symbol,
            'role_name'=>$user->role_name,
            'referral_status' => $status,
            'credited_at' => $creditedAt,
        ];
    }
}
