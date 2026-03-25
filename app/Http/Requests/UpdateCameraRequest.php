<?php

namespace App\Http\Requests;

class UpdateCameraRequest extends StoreCameraRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['password'] = ['nullable', 'string', 'max:255'];

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->password === null || $this->password === '') {
            $this->request->remove('password');
        }
    }
}
