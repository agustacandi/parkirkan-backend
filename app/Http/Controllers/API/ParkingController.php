<?php

namespace App\Http\Controllers\API;

use App\Models\Parking;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\CheckOutAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ParkingController extends BaseController
{
    // Ini adalah fungsi baru untuk verifikasi plat dengan fuzzy matching
    private function findSimilarLicensePlate($ocrText, $threshold = 0.7)
    {
        // Bersihkan input
        $ocrText = strtoupper(preg_replace('/\s+/', '', $ocrText));

        // Ambil semua kendaraan dari database
        $vehicles = Vehicle::all();
        $bestMatch = null;
        $bestScore = 0;

        foreach ($vehicles as $vehicle) {
            // Bersihkan plat nomor dari database
            $dbPlate = strtoupper(preg_replace('/\s+/', '', $vehicle->license_plate));

            // Hitung Levenshtein distance
            $levDistance = levenshtein($ocrText, $dbPlate);

            // Hitung similarity score (0-1)
            $maxLen = max(strlen($ocrText), strlen($dbPlate));
            $score = 1.0 - ($levDistance / $maxLen);

            // Jika score lebih baik dari sebelumnya dan di atas threshold
            if ($score > $bestScore && $score >= $threshold) {
                $bestScore = $score;
                $bestMatch = $vehicle;
            }
        }

        return [
            'match' => $bestMatch,
            'score' => $bestScore
        ];
    }

    public function checkIn(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
                'check_in_image' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            ]);

            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();

            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }

            $image = $request->file('check_in_image');
            $image->storeAs('checkin', $image->hashName());

            // create check in record
            $checkIn = Parking::create([
                'user_id' => Auth::id(),
                'vehicle_id' => $vehicle->id,
                'check_in_time' => now(),
                'check_in_image' => $image->hashName(),
            ]);

            // return response
            return $this->sendResponse($checkIn->toArray(), 'Vehicle checked in successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Modifikasi method checkOut untuk mendukung fuzzy matching
    public function checkOut2(Request $request)
    {
        try {
            // Validasi request
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
                'check_out_image' => 'required|image|mimes:jpeg,png,jpg|max:1024',
                'verification_mode' => 'sometimes|string|in:exact,fuzzy',
                'ocr_confidence' => 'sometimes|numeric|min:0|max:1'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $vehicle = null;
            $verificationMode = $request->input('verification_mode', 'exact');
            $confidenceThreshold = $request->input('ocr_confidence', 0.7);
            $licensePlate = $request->license_plate;

            // Pencarian kendaraan berdasarkan verification mode
            if ($verificationMode === 'exact') {
                // Pencarian eksak seperti sebelumnya
                $vehicle = Vehicle::where('license_plate', $licensePlate)->first();
            } else {
                // Pencarian fuzzy
                $result = $this->findSimilarLicensePlate($licensePlate, $confidenceThreshold);
                $vehicle = $result['match'];

                // Log untuk debugging
                Log::info('OCR fuzzy match', [
                    'input' => $licensePlate,
                    'matched_with' => $vehicle ? $vehicle->license_plate : 'none',
                    'score' => $result['score']
                ]);
            }

            if (!$vehicle) {
                return $this->sendError('Vehicle not found. No similar plates matched.', [], JsonResponse::HTTP_NOT_FOUND);
            }

            // Kode selanjutnya sama seperti method asli...
            $isCheckOutConfirmed = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->where("is_check_out_confirmed", true)
                ->exists();

            if (!$isCheckOutConfirmed) {
                $user = Auth::user();
                $user->notify(new CheckOutAlert());
                return $this->sendError('Check-out not confirmed', ['matched_plate' => $vehicle->license_plate], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $image = $request->file('check_out_image');
            $image->storeAs('checkout', $image->hashName());

            // Proses check out
            $checkOut = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->first();

            if (!$checkOut) {
                return $this->sendError('No check-in record found for this vehicle', [], JsonResponse::HTTP_NOT_FOUND);
            }

            $checkOut->update([
                'check_out_time' => now(),
                'check_out_image' => $image->hashName(),
                'status' => 'done',
            ]);

            // Tambahkan informasi tentang plat yang dicocokkan jika menggunakan fuzzy matching
            $responseData = $checkOut->toArray();
            if ($verificationMode === 'fuzzy') {
                $responseData['matched_license_plate'] = $vehicle->license_plate;
                $responseData['original_input'] = $licensePlate;
                $responseData['match_score'] = $result['score'];
            }

            return $this->sendResponse($responseData, 'Vehicle checked out successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
                'check_out_image' => 'required|image|mimes:jpeg,png,jpg|max:1024',
            ]);

            $levDistance = levenshtein($request->license_plate, $request->check_out_image);

            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();

            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }

            $isCheckOutConfirmed = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->where("is_check_out_confirmed", true)
                ->exists();

            if (!$isCheckOutConfirmed) {
                $user = Auth::user();
                $user->notify(new CheckOutAlert());
                return $this->sendError('Check-out not confirmed', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $image = $request->file('check_out_image');
            $image->storeAs('checkout', $image->hashName());

            // create check out record
            $checkOut = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->first();
            if (!$checkOut) {
                return $this->sendError('No check-in record found for this vehicle', JsonResponse::HTTP_NOT_FOUND);
            }
            $checkOut->update([
                'check_out_time' => now(),
                'check_out_image' => $image->hashName(),
                'status' => 'done',
            ]);

            // return response
            return $this->sendResponse($checkOut->toArray(), 'Vehicle checked out successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function confirmCheckOut(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
            ]);
            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }
            // check if vehicle is checked in
            $isCheckedIn = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->exists();
            if (!$isCheckedIn) {
                return $this->sendError('Vehicle not checked in', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
            // confirm check out
            Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->update(['is_check_out_confirmed' => true]);
            // return response
            return $this->sendResponse([], 'Check-out confirmed successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserParkingRecords(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);

            // get user auth
            $user = Auth::user();

            // get all parking records
            $parkingRecords = Parking::where('user_id', $user->id)->latest()->paginate($perPage);

            // check if parking records exist
            /* if ($parkingRecords->isEmpty()) { */
            /*     return $this->sendError('No parking records found', [], JsonResponse::HTTP_NOT_FOUND); */
            /* } */

            // return response
            return $this->sendResponse($parkingRecords->toArray(), 'Parking records retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getParkingRecords(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);

            // get all parking records
            $parkingRecords = Parking::with(['user', 'vehicle'])->latest()->paginate($perPage);

            // check if parking records exist
            if ($parkingRecords->isEmpty()) {
                return $this->sendError('No parking records found', JsonResponse::HTTP_NOT_FOUND);
            }

            // return response
            return $this->sendResponse($parkingRecords->toArray(), 'Parking records retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDashboard()
    {
        try {
            $totalVehicles = Vehicle::count();
            $totalUsers = User::count();
            // data parkir hari ini
            $totalParkings = Parking::whereDate('check_in_time', now())->count();

            // data untuk chart
            $parkings = Parking::selectRaw('DATE(check_in_time) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get();
            $labels = $parkings->pluck('date')->toArray();
            $data = $parkings->pluck('count')->toArray();
            $chartData = [
                'labels' => $labels,
                'data' => $data,
            ];

            return $this->sendResponse([
                'total_vehicles' => $totalVehicles,
                'total_users' => $totalUsers,
                'total_parkings' => $totalParkings,
                'chart_data' => $chartData,
            ], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function isUserCheckIn(Request $request)
    {
        try {
            // create validation rules
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
            ]);

            // check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // check if vehicle with license plate exists
            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', JsonResponse::HTTP_NOT_FOUND);
            }
            // check if vehicle is checked in
            $isCheckedIn = Parking::where('vehicle_id', $vehicle->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->exists();
            return $this->sendResponse(['is_checked_in' => $isCheckedIn], 'Check-in status retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        } // Tambahkan method ini di ParkingController
    }

    public function verifyLicensePlate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:10',
                'threshold' => 'sometimes|numeric|min:0|max:1'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $licensePlate = $request->license_plate;
            $threshold = $request->input('threshold', 0.7);

            // Cari dengan fuzzy matching
            $result = $this->findSimilarLicensePlate($licensePlate, $threshold);

            if ($result['match']) {
                return $this->sendResponse([
                    'found' => true,
                    'vehicle' => $result['match'],
                    'score' => $result['score'],
                    'input' => $licensePlate
                ], 'Similar license plate found');
            } else {
                return $this->sendResponse([
                    'found' => false,
                    'score' => 0,
                    'input' => $licensePlate
                ], 'No similar license plate found');
            }
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function fuzzyCheckOut(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:20',
                'check_out_image' => 'required|image|mimes:jpeg,png,jpg|max:1024',
                'max_distance' => 'integer|min:0' // Opsional: maksimum jarak Levenshtein
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Ambil plat nomor dari OCR dan max distance (default 2 jika tidak disediakan)
            $ocrLicensePlate = $request->license_plate;
            $maxDistance = $request->max_distance ?? 2;

            // Ambil semua kendaraan untuk fuzzy matching
            $vehicles = Vehicle::select('id', 'license_plate')->get();

            // Variabel untuk menyimpan kendaraan dengan jarak Levenshtein terkecil
            $bestMatch = null;
            $minDistance = PHP_INT_MAX;

            $aaa = DB::table('vehicles')
                ->select([
                    'license_plate',
                ])
                ->get()
                ->map(function ($vehicle) use ($ocrLicensePlate) {
                    $licensePlateUpper = strtoupper($vehicle->license_plate);
                    $licensePlate2Upper = strtoupper($ocrLicensePlate);

                    $levenshtein = levenshtein($licensePlateUpper, $licensePlate2Upper);
                    $maxLength = max(strlen($vehicle->license_plate), strlen($ocrLicensePlate));

                    $vehicle->distance = $levenshtein;
                    $vehicle->ratio = $maxLength > 0 ? $levenshtein / $maxLength : 0;

                    return $vehicle;
                })
                ->filter(function ($vehicle) {
                    return $vehicle->ratio < 0.5;
                });

            dd($aaa);

            // Cari kendaraan dengan plat nomor yang paling mirip
            foreach ($vehicles as $vehicle) {
                $distance = levenshtein(
                    strtoupper($ocrLicensePlate),
                    strtoupper($vehicle->license_plate)
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = $vehicle;
                }
            }

            /* dd($bestMatch, $minDistance); */

            // Jika tidak ada yang cocok dalam threshold
            if ($minDistance > $maxDistance) {
                return $this->sendError('No matching vehicle found within threshold', [
                    'ocr_detected' => $ocrLicensePlate,
                    'closest_match' => $bestMatch ? $bestMatch->license_plate : null,
                    'distance' => $minDistance,
                    'max_distance' => $maxDistance
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            // Log informasi untuk debugging
            \Log::info('Levenshtein matching result', [
                'ocr_input' => $ocrLicensePlate,
                'best_match' => $bestMatch->license_plate,
                'distance' => $minDistance,
                'max_distance' => $maxDistance
            ]);

            // Proses checkout seperti pada fungsi sebelumnya
            $image = $request->file('check_out_image');
            $image->storeAs('checkout', $image->hashName());

            $checkOut = Parking::where('vehicle_id', $bestMatch->id)
                ->where('user_id', Auth::id())
                ->whereNull('check_out_time')
                ->first();

            if (!$checkOut) {
                return $this->sendError('No check-in record found for this vehicle', [], JsonResponse::HTTP_NOT_FOUND);
            }

            if (!$checkOut->is_check_out_confirmed) {
                return $this->sendError('Check-out not confirmed yet', [
                    'original_license_plate' => $bestMatch->license_plate,
                    'ocr_detected' => $ocrLicensePlate,
                    'levenshtein_distance' => $minDistance
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $checkOut->update([
                'check_out_time' => now(),
                'check_out_image' => $image->hashName(),
                'status' => 'done',
            ]);

            return $this->sendResponse([
                'parking' => $checkOut->toArray(),
                'matching_details' => [
                    'ocr_input' => $ocrLicensePlate,
                    'matched_license_plate' => $bestMatch->license_plate,
                    'levenshtein_distance' => $minDistance
                ]
            ], 'Vehicle checked out successfully using fuzzy matching');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
