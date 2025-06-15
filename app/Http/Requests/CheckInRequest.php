<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'license_plate' => 'required|string|max:20',
            'check_in_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_plate.required' => 'License plate is required.',
            'license_plate.max' => 'License plate cannot exceed 20 characters.',
            'check_in_image.required' => 'Check-in image is required.',
            'check_in_image.image' => 'File must be an image.',
            'check_in_image.mimes' => 'Image must be in jpeg, png, jpg, or webp format.',
            'check_in_image.max' => 'Image size cannot exceed 2MB.',
        ];
    }
} 