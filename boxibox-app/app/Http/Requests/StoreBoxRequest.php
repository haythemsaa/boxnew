<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_boxes');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:50', 'unique:boxes,number'],
            'description' => ['nullable', 'string', 'max:1000'],
            'length' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'width' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'height' => ['required', 'numeric', 'min:0.1', 'max:100'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,occupied,maintenance,reserved'],
            'climate_controlled' => ['nullable', 'boolean'],
            'has_electricity' => ['nullable', 'boolean'],
            'has_alarm' => ['nullable', 'boolean'],
            'has_24_7_access' => ['nullable', 'boolean'],
            'has_wifi' => ['nullable', 'boolean'],
            'has_shelving' => ['nullable', 'boolean'],
            'is_ground_floor' => ['nullable', 'boolean'],
            'position' => ['nullable', 'array'],
            'access_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site_id.required' => 'Please select a site for this box.',
            'site_id.exists' => 'The selected site does not exist.',
            'code.unique' => 'This box code is already in use.',
            'length.required' => 'Please enter the box length.',
            'width.required' => 'Please enter the box width.',
            'height.required' => 'Please enter the box height.',
            'base_price.required' => 'Please enter the base price.',
            'base_price.min' => 'The base price must be at least 0.',
        ];
    }
}
