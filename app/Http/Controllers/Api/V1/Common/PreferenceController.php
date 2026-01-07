<?php

namespace App\Http\Controllers\Api\V1\Common;

use Illuminate\Http\Request;
use App\Models\Master\Preference;
use App\Http\Controllers\Api\V1\BaseController;
use App\Transformers\Common\PreferencesTransformer;

/**
 * @group Preference
 *
 */
class PreferenceController extends BaseController
{
    /**
     *
     * @var \App\Models\Admin\Preference
     */
    protected $preference;

    /**
     * CancellationReasonsController constructor.
     *
     * @param \App\Models\Admin\Preference $preference
     */
    public function __construct(Preference $preference)
    {
        $this->preference = $preference;
    }

    /**
     * Get All Preferences
     * 
     * @response
     * {
     * 
     *      "success": true,
     *      "message": "preferences_listed",
     *      "data": [
     *          {
     *              "id": 4,
     *              "name": "Pet",
     *              "icon": "https://restart.ondemandappz.com/storage/uploads/preference/images/9mme6ouhQmZjpWrRKLvn3RX2MUFrxryagl7Misyy.png",
     *              "created_at": "2025-08-21T14:04:46.000000Z",
     *              "updated_at": "2025-08-21T14:04:46.000000Z",
     *              "driver_selected": false
     *          },
     *      ]
     * }
    */
    public function index(Request $request)
    {
        $includes = [];

        $query = $this->preference->whereActive(true)->get();

        $results = fractal($query, new PreferencesTransformer);

        return $this->respondSuccess($results, 'preferences_listed');
    }
    

    /**
     * Store Preferences
     * 
     * @response
     * {
     * 
     *      "success": true,
     *      "message": "preferences_updated",
     *      "data": [2,4]
     * }
    */
    public function update(Request $request) {

        if(access()->hasRole('driver')){

            $validated = $request->validate(['preferences'=>'required']);

            $driver = auth()->user()->driver;

            $driver->preference()->delete();

            foreach (json_decode($validated['preferences']) as $key => $selected_preference) {
                $driver->preference()->create([ 'preference_id' => $selected_preference,]);
            }

            $results = $driver->preference()->pluck('preference_id');
        }else{
            $this->throwAuthorizationException();
        }

        return $this->respondSuccess($results, 'preferences_updated');

    }

}

