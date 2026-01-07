<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class MobileAppCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {  
        return [
            'name' => 'required|max:20',
            'mobile_menu_icon' => 'required',
            'service_type' => 'required',
            

        ];
    }
}
