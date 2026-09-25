<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('leads.update');
    }

    public function rules(): array
    {
        return ['otp' => ['required', 'digits:6']];
    }
}
