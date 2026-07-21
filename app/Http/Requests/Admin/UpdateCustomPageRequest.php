<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customPage = $this->route('customPage');
        return $this->user()->can('update', $customPage);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'seo_image' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
