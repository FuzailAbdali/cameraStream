<?php

namespace App\Http\Requests;

class UpdateCameraRequest extends StoreCameraRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['password'][0] = 'sometimes';

        return $rules;
    }
}
