<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
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
            'check_out_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'verification_mode' => 'sometimes|string|in:exact,fuzzy',
            'ocr_confidence' => 'sometimes|numeric|min:0|max:1',
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
            'check_out_image.required' => 'Check-out image is required.',
            'check_out_image.image' => 'File must be an image.',
            'check_out_image.mimes' => 'Image must be in jpeg, png, jpg, or webp format.',
            'check_out_image.max' => 'Image size cannot exceed 2MB.',
            'verification_mode.in' => 'Verification mode must be either exact or fuzzy.',
            'ocr_confidence.numeric' => 'OCR confidence must be a number.',
            'ocr_confidence.min' => 'OCR confidence must be at least 0.',
            'ocr_confidence.max' => 'OCR confidence cannot exceed 1.',
        ];
    }
} 