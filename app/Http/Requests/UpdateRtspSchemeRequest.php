<?php

namespace App\Http\Requests;

class UpdateRtspSchemeRequest extends StoreRtspSchemeRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:rtsp_schemes,name,'.$this->route('rtsp_scheme')->id],
            'scheme_template' => ['required', 'string'],
        ];
    }
}
