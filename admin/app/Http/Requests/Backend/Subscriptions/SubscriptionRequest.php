<?php

namespace App\Http\Requests\Backend\Subscriptions;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

/**
 * Class VideoRequest.
 */
class SubscriptionRequest extends Request {
/**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'SKU' => 'required|max:20',
            'tutorials' => 'required|numeric',
            'tournaments' => 'required|numeric',
            'battles' => 'numeric',
            'price' => 'required|numeric',
        ];
    }
    
    /**
     * Get the validation rules for subscription add case in android application case.
     *
     * @return array
     */
    public function subscriptionRegisterValidator() {
        
    }
}
