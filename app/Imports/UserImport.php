<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithValidation;

class UserImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip if user with this email already exists
        if (User::where('email', $row['email'])->exists()) {
            return null;
        }

        // Convert phone to string if it's a number
        $phone = isset($row['phone']) ? (string) $row['phone'] : null;

        // Clean up phone number (remove any non-digit characters except +)
        if ($phone) {
            $phone = preg_replace('/[^\d+]/', '', $phone);
        }

        return new User([
            'name' => trim($row['name']),
            'email' => trim(strtolower($row['email'])),
            'password' => bcrypt('password'), // Default password
            'phone' => $phone,
            'role' => isset($row['role']) ? trim(strtolower($row['role'])) : 'user',
        ]);
    }

    /**
     * Validation rules for each row
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|max:20', // Allow both string and numeric phone numbers
            'role' => 'nullable|in:user,admin',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Name is required for row :row',
            'email.required' => 'Email is required for row :row',
            'email.email' => 'Email format is invalid for row :row',
            'email.unique' => 'Email already exists for row :row',
            'phone.max' => 'Phone number is too long for row :row',
            'role.in' => 'Role must be either "user" or "admin" for row :row',
        ];
    }
}
