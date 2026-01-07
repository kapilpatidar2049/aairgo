<?php

namespace App\Models\Request;

use Storage;
use App\Models\Traits\HasActive;
use App\Models\Traits\HasActiveCompanyKey;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Models\Master\PreferencePrices;
use App\Models\Request\Request;

class RequestPreference extends Model
{
    use SearchableTrait;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'request_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['preference_price_id','request_id','price'];

    /**
     * The relationships that can be loaded with query string filtering includes.
     *
     * @var array
     */
    public $includes = [
    ];


    public function preferencePrice()
    {
        return $this->belongsTo(PreferencePrices::class, 'preference_price_id', 'id');
    }

    /**
     * The zone type that belongs to.
     * @tested
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function requestDetail()
    {
        return $this->belongsTo(Request::class, 'request_id', 'id');
    } 
}
