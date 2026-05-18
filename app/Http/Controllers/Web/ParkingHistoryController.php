<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Parking;
use Illuminate\Http\Request;

class ParkingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Parking::with(['user', 'vehicle']);

        // Filter berdasarkan nama user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $parkings = $query->orderBy('check_in_time', 'desc')->paginate(10)->withQueryString();

        return view('admin.parking-history.index', compact('parkings'));
    }

    public function show(Parking $parking)
    {
        // Memuat relasi agar dapat ditampilkan pada halaman detail
        $parking->load(['user', 'vehicle']);

        return view('admin.parking-history.show', compact('parking'));
    }
}
