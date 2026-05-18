<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Parking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik utama
        // Total Users: Secara opsional mengecualikan admin agar lebih relevan (asumsi role = 'user' atau role != 'admin')
        $totalUsers = User::where('role', '!=', 'admin')->count();
        $totalVehicles = Vehicle::count();
        $totalParkings = Parking::count();

        // Data Chart 7 Hari Terakhir
        $last7Days = Carbon::today()->subDays(6);

        // Agregasi jumlah parkir per tanggal (hanya check_in_time)
        $parkingTrends = Parking::select(
            DB::raw('DATE(check_in_time) as date'),
            DB::raw('count(*) as total')
        )
            ->where('check_in_time', '>=', $last7Days)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartValues = [];

        // Pastikan array memiliki 7 hari lengkap meski ada hari tanpa data parkir
        for ($i = 0; $i < 7; $i++) {
            $dateString = Carbon::today()->subDays(6 - $i)->format('Y-m-d');
            $chartLabels[] = Carbon::parse($dateString)->format('d M');
            $chartValues[] = $parkingTrends->has($dateString) ? $parkingTrends[$dateString]->total : 0;
        }

        return view('admin.dashboard.index', compact(
            'totalUsers',
            'totalVehicles',
            'totalParkings',
            'chartLabels',
            'chartValues'
        ));
    }
}
