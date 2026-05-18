<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToModel, WithHeadingRow
{
    /**
     * Memetakan setiap baris data dari Excel menjadi Model User.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Hindari entri jika kolom email kosong
        if (empty($row['email'])) {
            return null;
        }

        // Hindari duplikasi berdasarkan email
        $existingUser = User::where('email', $row['email'])->first();
        if ($existingUser) {
            return null;
        }

        return new User([
            'name'     => $row['name'] ?? $row['nama'] ?? 'User Baru',
            'email'    => $row['email'],
            'phone'    => $row['phone'] ?? $row['no_hp'] ?? null,
            'role'     => $row['role'] ?? 'user',
            'password' => isset($row['password']) ? Hash::make($row['password']) : Hash::make('password123'),
        ]);
    }
}
