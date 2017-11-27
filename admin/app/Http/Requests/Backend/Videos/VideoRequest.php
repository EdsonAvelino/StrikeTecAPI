<?php

namespace App\Http\Requests\Backend\Videos;
use App\Http\Requests\Request;

/**
 * Class VideoRequest.
 */
class VideoRequest extends Request {
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
            'video_file' => 'required|mimes:mp4',
            'video_thumbnail'=> 'required|mimes:jpeg,jpg,png,ico',
            'price' => 'numeric',
        ];
    }

}
