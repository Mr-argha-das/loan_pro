<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generic master-data validation: masters share a small common shape and may
 * carry extra scalar columns declared in the configuration array.
 */
class StoreMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('masters.manage');
    }

    public function rules(): array
    {
        $extra = config('loanpro.masters.'.($this->route('master') ?? $this->masterKey()).'.rules', []);

        return array_merge([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:60'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], $extra);
    }

    protected function masterKey(): string
    {
        return (string) $this->route('masterKey');
    }
}
