<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomPageLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customPage = $this->route('customPage');
        return $this->user()->can('update', $customPage);
    }

    public function rules(): array
    {
        return [
            'layout' => ['required', 'array'],
            'lock_version' => ['required', 'integer'],
        ];
    }
}
