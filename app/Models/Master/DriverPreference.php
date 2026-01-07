<?php

namespace App\Models\Master;

use Storage;
use App\Models\Traits\HasActive;
use App\Models\Traits\HasActiveCompanyKey;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Models\Master\Preference;
use App\Models\Admin\Driver;

class DriverPreference extends Model
{
    use SearchableTrait;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'driver_preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['preference_id','driver_id'];

    protected $appends = ['name'];

    /**
     * The relationships that can be loaded with query string filtering includes.
     *
     * @var array
     */
    public $includes = [
        'preference', 'driverDetail'
    ];

    protected $searchable = [
        'columns' => [
            'preferences.name' => 20,
            'drivers.name' => 20,
        ],
        'joins' => [
            'preferences' => ['driver_preferences.preference_id','preferences.id'],
            'drivers' => ['driver_preferences.driver_id','drivers.id'],
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

    /**
     * The zone type that belongs to.
     * @tested
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function driverDetail()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    } 
}
