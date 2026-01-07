<?php

namespace App\Models\Master;

use App\Models\Traits\HasActive;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\DeleteOldFiles;
use Illuminate\Support\Facades\Storage;


class UserImported extends Model {
    
    use HasActive,DeleteOldFiles;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_imported';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
         'valid_file','invalid_file','invalid_file_path','user_id','status'

    ];
}
