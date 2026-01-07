<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasActive;


class ReferralConditionTranslation  extends Model
{
    use HasActive;
    protected $table = 'referral_condition_translations';

    protected $fillable = ['referral_id','description','locale'];

  
      public function language()
    {
        return $this->belongsTo(Language::class, 'code', 'locale');
    }
}
