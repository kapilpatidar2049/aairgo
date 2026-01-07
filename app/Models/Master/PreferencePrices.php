<?php

namespace App\Models\Master;

use Storage;
use App\Models\Traits\HasActive;
use App\Models\Traits\HasActiveCompanyKey;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Models\Master\Preference;
use App\Models\Admin\ZoneType;

class PreferencePrices extends Model
{
    use SearchableTrait;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'preference_prices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['preference_id','zone_type_id','price'];

    protected $appends = ['name','icon'];

    /**
     * The relationships that can be loaded with query string filtering includes.
     *
     * @var array
     */
    public $includes = [
    ];

    protected $searchable = [
        'columns' => [
            'preferences.name' => 20,
        ],
        'joins' => [
            'preferences' => ['preference_prices.preference_id','preferences.id'],
        ],
    ];

    public function preference()
    {
        return $this->belongsTo(Preference::class, 'preference_id', 'id');
    }


    public function getNameAttribute()
    {
        return $this->preference()->exists() ? $this->preference->name : null;
    }

    public function getIconAttribute()
    {
        return $this->preference()->exists() ? $this->preference->icon : null;
    }

    /**
     * The zone type that belongs to.
     * @tested
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function setprice()
    {
        return $this->belongsTo(ZoneType::class, 'zone_type_id', 'id');
    } 
}
