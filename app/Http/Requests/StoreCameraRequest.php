<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCameraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'external_ip' => ['nullable', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'forwarded_port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'rtsp_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
