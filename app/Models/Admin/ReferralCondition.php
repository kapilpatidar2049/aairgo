<?php

namespace App\Models\Admin;

use App\Base\Uuid\UuidModel;
use App\Models\Traits\HasActive;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\ReferralConditionTranslation;

class ReferralCondition extends Model
{
    use HasActive;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'referral_conditions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'referral_type', 'description','translation_dataset','label_referral',
    ];

   
    public function referralConditionTranslationWords(){
        return $this->hasMany(ReferralConditionTranslation::class, 'referral_id', 'id');
    }
}
