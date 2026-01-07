<?php

namespace App\Models\Master;

use Storage;
use App\Models\Traits\HasActive;
use App\Models\Traits\HasActiveCompanyKey;
use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;

class Preference extends Model
{
    use HasActive,HasActiveCompanyKey,SearchableTrait;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'preferences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name','icon','active'];

    /**
     * The relationships that can be loaded with query string filtering includes.
     *
     * @var array
     */
    public $includes = [
        'prices',
    ];

    /**
     * The attributes that have files that should be auto deleted on updating or deleting.
     *
     * @var array
     */
    public $deletableFiles = [
        'icon'
    ];

    protected $searchable = [
        'columns' => [
            'preferences.name' => 20,
        ],
    ];

    public function prices()
    {
        return $this->hasMany(PreferencePrice::class, 'preference_id', 'id');
    }

    public function drivers()
    {
        return $this->hasMany(DriverPreference::class, 'preference_id', 'id');
    }

    /**
     * Get the Profile image full file path.
     *
     * @param string $value
     * @return string
     */
    public function getIconAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        // return asset(file_path('storage/'.$this->uploadPath(), $value));

        return Storage::disk(env('FILESYSTEM_DRIVER'))->url(file_path($this->uploadPath(), $value));
    }

    /**
     * The default file upload path.
     *
     * @return string|null
     */
    public function uploadPath()
    {
        // if (!$this->serviceLocation()->exists()) {
        //     return null;
        // }

        // return folder_merge(config('base.types.upload.images.path'), $this->service_location_id);
        return config('base.preference.upload.images.path');
    }

}
