<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Http\Resources\ParkingResource;
use App\Models\Parking;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\CheckOutAlert;
use App\Services\LicensePlateMatchingService;
use App\Services\ParkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ParkingController extends BaseController
{
    public function __construct(
        private ParkingService $parkingService,
        private LicensePlateMatchingService $matchingService
    ) {}

    /**
     * Handle vehicle check-in
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        try {
            $licensePlate = $request->validated('license_plate');

            // Find vehicle
            $vehicle = Vehicle::where('license_plate', $licensePlate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', [], JsonResponse::HTTP_NOT_FOUND);
            }

            // Check if vehicle is already checked in
            if ($this->parkingService->hasActiveParkingSession($vehicle->id, Auth::id())) {
                return $this->sendError(
                    'Vehicle is already checked in',
                    [],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Store image
            $imagePath = $this->parkingService->storeImage(
                $request->file('check_in_image'),
                'checkin'
            );

            // Create check-in record
            $checkIn = $this->parkingService->createCheckIn($vehicle, $imagePath);

            return $this->sendResponse(
                new ParkingResource($checkIn),
                'Vehicle checked in successfully'
            );
        } catch (\Exception $e) {
            Log::error('Check-in error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle vehicle check-out with optional fuzzy matching
     */
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        try {
            $licensePlate = $request->validated('license_plate');
            $verificationMode = $request->validated('verification_mode', 'exact');
            $confidenceThreshold = $request->validated('ocr_confidence', 0.7);

            // Find vehicle using appropriate matching strategy
            $result = $this->parkingService->findVehicleByLicensePlate(
                $licensePlate,
                $verificationMode,
                $confidenceThreshold
            );

            $vehicle = $result['vehicle'];
            if (!$vehicle) {
                $message = $verificationMode === 'fuzzy'
                    ? 'Vehicle not found. No similar plates matched.'
                    : 'Vehicle not found';
                return $this->sendError($message, [], JsonResponse::HTTP_NOT_FOUND);
            }

            // Check if check-out is confirmed
            if (!$this->parkingService->isCheckOutConfirmed($vehicle->id, Auth::id())) {
                $this->parkingService->sendCheckOutAlert();
                return $this->sendError(
                    'Check-out not confirmed',
                    ['matched_plate' => $vehicle->license_plate],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Get active parking record
            $parking = $this->parkingService->getActiveParkingRecord($vehicle->id, Auth::id());
            if (!$parking) {
                return $this->sendError(
                    'No check-in record found for this vehicle',
                    [],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            // Store check-out image
            $imagePath = $this->parkingService->storeImage(
                $request->file('check_out_image'),
                'checkout'
            );

            // Update parking record
            $parking = $this->parkingService->updateCheckOut($parking, $imagePath);

            // Prepare response data
            $responseData = new ParkingResource($parking);
            if ($verificationMode === 'fuzzy') {
                $responseData = [
                    'parking' => $responseData,
                    'matching_details' => [
                        'matched_license_plate' => $vehicle->license_plate,
                        'original_input' => $licensePlate,
                        'match_score' => $result['score'],
                        'method' => $result['method']
                    ]
                ];
            }

            return $this->sendResponse($responseData, 'Vehicle checked out successfully');
        } catch (\Exception $e) {
            Log::error('Check-out error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Confirm check-out for a vehicle
     */
    public function confirmCheckOut(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', [], JsonResponse::HTTP_NOT_FOUND);
            }

            if (!$this->parkingService->hasActiveParkingSession($vehicle->id, Auth::id())) {
                return $this->sendError(
                    'Vehicle not checked in',
                    [],
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $this->parkingService->confirmCheckOut($vehicle, Auth::id());

            return $this->sendResponse([], 'Check-out confirmed successfully');
        } catch (\Exception $e) {
            Log::error('Confirm check-out error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get user's parking records
     */
    public function getUserParkingRecords(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 5);
            $user = Auth::user();

            $parkingRecords = Parking::where('user_id', $user->id)
                ->with(['vehicle'])
                ->latest()
                ->paginate($perPage);

            // Create pagination response with array-based links for Android compatibility
            $response = [
                'current_page' => $parkingRecords->currentPage(),
                'data' => ParkingResource::collection($parkingRecords->items()),
                'first_page_url' => $parkingRecords->url(1),
                'from' => $parkingRecords->firstItem(),
                'last_page' => $parkingRecords->lastPage(),
                'last_page_url' => $parkingRecords->url($parkingRecords->lastPage()),
                'links' => [
                    ['url' => $parkingRecords->previousPageUrl(), 'label' => 'Previous', 'active' => false],
                    ['url' => null, 'label' => (string)$parkingRecords->currentPage(), 'active' => true],
                    ['url' => $parkingRecords->nextPageUrl(), 'label' => 'Next', 'active' => false],
                ],
                'next_page_url' => $parkingRecords->nextPageUrl(),
                'path' => $parkingRecords->path(),
                'per_page' => $parkingRecords->perPage(),
                'prev_page_url' => $parkingRecords->previousPageUrl(),
                'to' => $parkingRecords->lastItem(),
                'total' => $parkingRecords->total(),
            ];

            return $this->sendResponse(
                $response,
                'Parking records retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Get user parking records error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get all parking records (admin only)
     */
    public function getParkingRecords(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 5);
            $searchName = $request->input('name', '');

            $query = Parking::with(['user', 'vehicle'])->latest();

            // Add search functionality if name parameter is provided
            if (!empty($searchName)) {
                $query->whereHas('user', function ($q) use ($searchName) {
                    $q->where('name', 'LIKE', '%' . $searchName . '%');
                });
            }

            $parkingRecords = $query->paginate($perPage);

            // Always return 200 with proper pagination structure, even if empty
            $response = [
                'current_page' => $parkingRecords->currentPage(),
                'data' => ParkingResource::collection($parkingRecords->items()),
                'first_page_url' => $parkingRecords->url(1),
                'from' => $parkingRecords->firstItem(),
                'last_page' => $parkingRecords->lastPage(),
                'last_page_url' => $parkingRecords->url($parkingRecords->lastPage()),
                'links' => [
                    ['url' => $parkingRecords->previousPageUrl(), 'label' => 'Previous', 'active' => false],
                    ['url' => null, 'label' => (string)$parkingRecords->currentPage(), 'active' => true],
                    ['url' => $parkingRecords->nextPageUrl(), 'label' => 'Next', 'active' => false],
                ],
                'next_page_url' => $parkingRecords->nextPageUrl(),
                'path' => $parkingRecords->path(),
                'per_page' => $parkingRecords->perPage(),
                'prev_page_url' => $parkingRecords->previousPageUrl(),
                'to' => $parkingRecords->lastItem(),
                'total' => $parkingRecords->total(),
            ];

            $message = $parkingRecords->isEmpty()
                ? 'No parking records found'
                : 'Parking records retrieved successfully';

            return $this->sendResponse($response, $message);
        } catch (\Exception $e) {
            Log::error('Get parking records error', ['error' => $e->getMessage()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get detailed parking history with advanced filtering for admin dashboard
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getParkingHistoryDetails(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validator = Validator::make($request->all(), [
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
                'search' => 'string|max:255',
                'status' => 'string|in:checked_in,checked_out,expired',
                'date_from' => 'date_format:Y-m-d',
                'date_to' => 'date_format:Y-m-d|after_or_equal:date_from',
                'user_id' => 'integer|exists:users,id',
                'vehicle_id' => 'integer|exists:vehicles,id',
                'sort_by' => 'string|in:check_in_time,check_out_time,created_at,user_name,vehicle_name',
                'sort_order' => 'string|in:asc,desc',
                'include_stats' => 'boolean',
                'export' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // Extract and set defaults for parameters
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search', '');
            $status = $request->input('status');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $userId = $request->input('user_id');
            $vehicleId = $request->input('vehicle_id');
            $sortBy = $request->input('sort_by', 'check_in_time');
            $sortOrder = $request->input('sort_order', 'desc');
            $includeStats = $request->boolean('include_stats', true);
            $export = $request->boolean('export', false);

            // Build the base query with relationships
            $query = Parking::with(['user', 'vehicle'])->select('parkings.*');

            // Apply search filter (searches in user name, email, and vehicle license plate)
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })->orWhereHas('vehicle', function ($vehicleQuery) use ($search) {
                        $vehicleQuery->where('license_plate', 'LIKE', "%{$search}%")
                            ->orWhere('name', 'LIKE', "%{$search}%");
                    });
                });
            }

            // Apply status filter
            if ($status) {
                switch ($status) {
                    case 'checked_in':
                        $query->whereNull('check_out_time');
                        break;
                    case 'checked_out':
                        $query->whereNotNull('check_out_time');
                        break;
                    case 'expired':
                        // Define "expired" as checked in for more than 24 hours without checkout
                        $query->whereNull('check_out_time')
                            ->where('check_in_time', '<', now()->subHours(24));
                        break;
                }
            }

            // Apply date filters
            if ($dateFrom) {
                $query->whereDate('check_in_time', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('check_in_time', '<=', $dateTo);
            }

            // Apply user filter
            if ($userId) {
                $query->where('user_id', $userId);
            }

            // Apply vehicle filter
            if ($vehicleId) {
                $query->where('vehicle_id', $vehicleId);
            }

            // Apply sorting
            switch ($sortBy) {
                case 'user_name':
                    $query->join('users', 'parkings.user_id', '=', 'users.id')
                        ->orderBy('users.name', $sortOrder);
                    break;
                case 'vehicle_name':
                    $query->join('vehicles', 'parkings.vehicle_id', '=', 'vehicles.id')
                        ->orderBy('vehicles.license_plate', $sortOrder);
                    break;
                default:
                    $query->orderBy($sortBy, $sortOrder);
                    break;
            }

            // Handle export functionality (return early to avoid type issues)
            if ($export) {
                $exportData = $query->get();
                $csvData = $this->generateParkingHistoryCsv($exportData);

                // Create a JSON response for CSV export
                return $this->sendResponse([
                    'export_url' => 'data:text/csv;charset=utf-8,' . urlencode($csvData),
                    'filename' => 'parking_history_' . now()->format('Y-m-d_H-i-s') . '.csv',
                    'total_records' => $exportData->count()
                ], 'Export data generated successfully');
            }

            // Paginate results
            $parkingRecords = $query->paginate($perPage);

            // Prepare response data
            $responseData = [
                'current_page' => $parkingRecords->currentPage(),
                'data' => ParkingResource::collection($parkingRecords->items()),
                'first_page_url' => $parkingRecords->url(1),
                'from' => $parkingRecords->firstItem(),
                'last_page' => $parkingRecords->lastPage(),
                'last_page_url' => $parkingRecords->url($parkingRecords->lastPage()),
                'next_page_url' => $parkingRecords->nextPageUrl(),
                'path' => $parkingRecords->path(),
                'per_page' => $parkingRecords->perPage(),
                'prev_page_url' => $parkingRecords->previousPageUrl(),
                'to' => $parkingRecords->lastItem(),
                'total' => $parkingRecords->total(),
            ];

            // Include statistics if requested
            if ($includeStats) {
                $responseData['statistics'] = $this->getParkingStatistics($dateFrom, $dateTo);
            }

            $message = $parkingRecords->isEmpty()
                ? 'No parking records found matching the criteria'
                : 'Parking history details retrieved successfully';

            return $this->sendResponse($responseData, $message);
        } catch (\Exception $e) {
            Log::error('Get parking history details error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all()
            ]);

            return $this->sendError(
                'Internal Server Error',
                ['error' => 'Failed to retrieve parking history details'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get individual parking record details
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getParkingDetails(int $id): JsonResponse
    {
        try {
            $parking = Parking::with(['user', 'vehicle'])->find($id);

            if (!$parking) {
                return $this->sendError(
                    'Parking record not found',
                    [],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            // Calculate parking duration if checked out
            $duration = null;
            if ($parking->check_out_time && $parking->check_in_time) {
                $checkIn = \Carbon\Carbon::parse($parking->check_in_time);
                $checkOut = \Carbon\Carbon::parse($parking->check_out_time);
                $duration = [
                    'total_minutes' => $checkIn->diffInMinutes($checkOut),
                    'total_hours' => $checkIn->diffInHours($checkOut),
                    'human_readable' => $checkIn->diff($checkOut)->format('%d days, %h hours, %i minutes')
                ];
            }

            $responseData = [
                'parking' => new ParkingResource($parking),
                'duration' => $duration,
                'is_active' => is_null($parking->check_out_time),
                'is_expired' => is_null($parking->check_out_time) && \Carbon\Carbon::parse($parking->check_in_time)->diffInHours(now()) > 24
            ];

            return $this->sendResponse($responseData, 'Parking details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get parking details error', [
                'error' => $e->getMessage(),
                'parking_id' => $id
            ]);

            return $this->sendError(
                'Internal Server Error',
                ['error' => 'Failed to retrieve parking details'],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get parking statistics for dashboard
     * 
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    private function getParkingStatistics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = Parking::query();

        // Apply date filters if provided
        if ($dateFrom) {
            $query->whereDate('check_in_time', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('check_in_time', '<=', $dateTo);
        }

        $totalRecords = $query->count();
        $activeRecords = (clone $query)->whereNull('check_out_time')->count();
        $completedRecords = (clone $query)->whereNotNull('check_out_time')->count();
        $expiredRecords = (clone $query)->whereNull('check_out_time')
            ->where('check_in_time', '<', now()->subHours(24))
            ->count();

        // Get hourly distribution for the last 24 hours
        $hourlyDistribution = Parking::selectRaw('HOUR(check_in_time) as hour, COUNT(*) as count')
            ->whereDate('check_in_time', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->hour => $item->count];
            });

        // Fill missing hours with 0
        $completeHourlyDistribution = [];
        for ($i = 0; $i < 24; $i++) {
            $completeHourlyDistribution[$i] = $hourlyDistribution->get($i, 0);
        }

        // Get daily distribution for the last 7 days
        $dailyDistribution = Parking::selectRaw('DATE(check_in_time) as date, COUNT(*) as count')
            ->whereDate('check_in_time', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            });

        // Get most active users
        $topUsers = Parking::selectRaw('user_id, COUNT(*) as parking_count')
            ->with('user:id,name,email')
            ->groupBy('user_id')
            ->orderByDesc('parking_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->user,
                    'parking_count' => $item->parking_count
                ];
            });

        return [
            'summary' => [
                'total_records' => $totalRecords,
                'active_records' => $activeRecords,
                'completed_records' => $completedRecords,
                'expired_records' => $expiredRecords,
                'completion_rate' => $totalRecords > 0 ? round(($completedRecords / $totalRecords) * 100, 2) : 0
            ],
            'hourly_distribution' => $completeHourlyDistribution,
            'daily_distribution' => $dailyDistribution,
            'top_users' => $topUsers
        ];
    }

    /**
     * Generate CSV export for parking history
     * 
     * @param \Illuminate\Database\Eloquent\Collection $parkingRecords
     * @return string
     */
    private function generateParkingHistoryCsv($parkingRecords): string
    {
        $headers = [
            'ID',
            'User Name',
            'User Email',
            'Vehicle Name',
            'License Plate',
            'Check In Time',
            'Check Out Time',
            'Duration (Hours)',
            'Status',
            'Check Out Confirmed',
            'Created At'
        ];

        $csvData = implode(',', $headers) . "\n";

        foreach ($parkingRecords as $parking) {
            $duration = '';
            if ($parking->check_out_time && $parking->check_in_time) {
                $duration = $parking->check_in_time->diffInHours($parking->check_out_time);
            }

            $status = $parking->check_out_time ? 'Completed' : 'Active';
            if (!$parking->check_out_time && $parking->check_in_time->diffInHours(now()) > 24) {
                $status = 'Expired';
            }

            $row = [
                $parking->id,
                '"' . ($parking->user->name ?? 'N/A') . '"',
                '"' . ($parking->user->email ?? 'N/A') . '"',
                '"' . ($parking->vehicle->name ?? 'N/A') . '"',
                '"' . ($parking->vehicle->license_plate ?? 'N/A') . '"',
                $parking->check_in_time ? $parking->check_in_time->format('Y-m-d H:i:s') : '',
                $parking->check_out_time ? $parking->check_out_time->format('Y-m-d H:i:s') : '',
                $duration,
                $status,
                $parking->is_check_out_confirmed ? 'Yes' : 'No',
                $parking->created_at ? $parking->created_at->format('Y-m-d H:i:s') : ''
            ];

            $csvData .= implode(',', $row) . "\n";
        }

        return $csvData;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboard(): JsonResponse
    {
        try {
            $totalVehicles = Vehicle::count();
            $totalUsers = User::count();
            $totalParkings = Parking::whereDate('check_in_time', now())->count();

            // Chart data for last 7 days
            $parkings = Parking::selectRaw('DATE(check_in_time) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get();

            $chartData = [
                'labels' => $parkings->pluck('date')->toArray(),
                'data' => $parkings->pluck('count')->toArray(),
            ];

            return $this->sendResponse([
                'total_vehicles' => $totalVehicles,
                'total_users' => $totalUsers,
                'total_parkings' => $totalParkings,
                'chart_data' => $chartData,
            ], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Dashboard error', ['error' => $e->getMessage()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if user has active check-in for a vehicle
     */
    public function isUserCheckIn(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $vehicle = Vehicle::where('license_plate', $request->license_plate)->first();
            if (!$vehicle) {
                return $this->sendError('Vehicle not found', [], JsonResponse::HTTP_NOT_FOUND);
            }

            $isCheckedIn = $this->parkingService->hasActiveParkingSession($vehicle->id, Auth::id());

            return $this->sendResponse(
                ['is_checked_in' => $isCheckedIn],
                'Check-in status retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Check user check-in error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Verify license plate using fuzzy matching
     */
    public function verifyLicensePlate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:20',
                'threshold' => 'sometimes|numeric|min:0|max:1'
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $licensePlate = $request->license_plate;
            $threshold = $request->input('threshold', 0.7);

            $result = $this->matchingService->findSimilarLicensePlate($licensePlate, $threshold);

            if ($result['match']) {
                return $this->sendResponse([
                    'found' => true,
                    'vehicle' => $result['match'],
                    'score' => $result['score'],
                    'input' => $licensePlate
                ], 'Similar license plate found');
            }

            return $this->sendResponse([
                'found' => false,
                'score' => 0,
                'input' => $licensePlate
            ], 'No similar license plate found');
        } catch (\Exception $e) {
            Log::error('Verify license plate error', ['error' => $e->getMessage()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Fuzzy check-out using Levenshtein distance
     */
    public function fuzzyCheckOut(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'license_plate' => 'required|string|max:20',
                'check_out_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'max_distance' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->sendError(
                    'Validation Error',
                    $validator->errors(),
                    JsonResponse::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $ocrLicensePlate = $request->license_plate;
            $maxDistance = $request->input('max_distance', 2);

            $vehicles = $this->matchingService->findWithLevenshteinDistance($ocrLicensePlate, $maxDistance);

            if ($vehicles->isEmpty()) {
                return $this->sendError('No matching vehicle found within threshold', [
                    'ocr_detected' => $ocrLicensePlate,
                    'max_distance' => $maxDistance
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $bestMatch = $vehicles->first();

            Log::info('Levenshtein matching result', [
                'ocr_input' => $ocrLicensePlate,
                'best_match' => $bestMatch->license_plate,
                'distance' => $bestMatch->distance,
                'max_distance' => $maxDistance
            ]);

            $checkOut = $this->parkingService->getActiveParkingRecord($bestMatch->id, Auth::id());
            if (!$checkOut) {
                return $this->sendError(
                    'No check-in record found for this vehicle',
                    [],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }

            if (!$checkOut->is_check_out_confirmed) {
                return $this->sendError('Check-out not confirmed yet', [
                    'original_license_plate' => $bestMatch->license_plate,
                    'ocr_detected' => $ocrLicensePlate,
                    'levenshtein_distance' => $bestMatch->distance
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $imagePath = $this->parkingService->storeImage(
                $request->file('check_out_image'),
                'checkout'
            );

            $checkOut = $this->parkingService->updateCheckOut($checkOut, $imagePath);

            return $this->sendResponse([
                'parking' => new ParkingResource($checkOut),
                'matching_details' => [
                    'ocr_input' => $ocrLicensePlate,
                    'matched_license_plate' => $bestMatch->license_plate,
                    'levenshtein_distance' => $bestMatch->distance
                ]
            ], 'Vehicle checked out successfully using fuzzy matching');
        } catch (\Exception $e) {
            Log::error('Fuzzy check-out error', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return $this->sendError(
                'Internal Server Error',
                ['error' => $e->getMessage()],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Helper Method: Finds the best matching vehicle using Levenshtein distance,
     * featuring pre-filtering for performance and ambiguity handling.
     *
     * @param string $ocrText Text from the OCR result.
     * @param int $maxDistance The maximum allowed Levenshtein distance.
     * @return array Returns a structured search result.
     */
    private function findVehicleByLevenshtein(string $ocrText, int $maxDistance = 2): array
    {
        $bestMatches = [];
        $minDistance = PHP_INT_MAX;

        $cleanedOcrText = strtoupper(preg_replace('/[^A-Z0-9]/', '', $ocrText));
        if (empty($cleanedOcrText)) {
            return ['matches' => [], 'distance' => -1, 'is_ambiguous' => false];
        }

        // --- Performance Solution: Pre-filtering at the Database Level ---
        // Only fetch vehicles that start with the same character as the OCR result.
        // This drastically reduces the number of records to process in PHP.
        $firstChar = $cleanedOcrText[0];
        $vehicles = Vehicle::where('license_plate', 'LIKE', $firstChar . '%')->get();

        foreach ($vehicles as $vehicle) {
            $dbPlate = strtoupper(preg_replace('/[^A-Z0-9]/', '', $vehicle->license_plate));
            $distance = levenshtein($cleanedOcrText, $dbPlate);

            // --- Ambiguity Solution ---
            if ($distance < $minDistance) {
                // A better match is found, reset the array and store the new one.
                $minDistance = $distance;
                $bestMatches = [$vehicle];
            } elseif ($distance === $minDistance) {
                // Another match with the same distance is found, add it to the array.
                $bestMatches[] = $vehicle;
            }
        }

        // Check if the final result is valid (within the threshold).
        if ($minDistance > $maxDistance) {
            return ['matches' => [], 'distance' => $minDistance, 'is_ambiguous' => false];
        }

        return [
            'matches' => $bestMatches,
            'distance' => $minDistance,
            'is_ambiguous' => count($bestMatches) > 1,
        ];
    }

    /**
     * MAIN ENDPOINT: Records a parking event (check-in or check-out) from a single camera.
     * This endpoint serves as the "brain" of your automated parking system.
     */
    public function recordEvent(Request $request)
    {
        // 1. Validate input from FastAPI
        $validator = Validator::make($request->all(), [
            'license_plate' => 'required|string|max:20',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'confidence' => 'required|numeric',
            'max_distance' => 'integer|min:0' // Optional parameter from FastAPI
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ocrText = $request->input('license_plate');
        $maxDistance = $request->input('max_distance', 2); // Default distance of 2 if not provided

        // 2. Use Levenshtein to find the most suitable vehicle
        $matchResult = $this->findVehicleByLevenshtein($ocrText, $maxDistance);

        // --- Enhanced Search Result Handling ---

        // Case 1: No match found at all
        if (empty($matchResult['matches'])) {
            return $this->sendError('Vehicle not found.', [
                'ocr_text' => $ocrText,
                'message' => 'No suitable vehicle found within the threshold.',
                'closest_distance' => $matchResult['distance']
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Case 2: More than one match found (Ambiguity)
        if ($matchResult['is_ambiguous']) {
            return $this->sendError('Ambiguous match found.', [
                'ocr_text' => $ocrText,
                'message' => 'Multiple matching license plates were found. Manual verification is required.',
                'levenshtein_distance' => $matchResult['distance'],
                'potential_matches' => $matchResult['matches']
            ], JsonResponse::HTTP_CONFLICT); // HTTP 409 Conflict is suitable for this case
        }

        // --- Proceed if there is only one valid match ---
        $vehicle = $matchResult['matches'][0];
        $levenshteinDistance = $matchResult['distance'];

        // 3. Determine if this is a Check-in or Check-out
        $activeParking = Parking::where('vehicle_id', $vehicle->id)
            ->whereNull('check_out_time')
            ->first();

        $image = $request->file('image');
        $imageName = $image->hashName();

        if ($activeParking) {
            // ==================================
            // --- CHECK-OUT LOGIC is executed ---
            // ==================================
            $image->storeAs('checkout', $imageName, 'public');

            // Check if the owner has confirmed
            if (!$activeParking->is_check_out_confirmed) {
                $vehicleOwner = $activeParking->user;
                if ($vehicleOwner) { // Make sure the user exists before sending a notification
                    $vehicleOwner->notify(new CheckOutAlert());
                }

                return $this->sendError('Check-out not confirmed by owner', [
                    'message' => 'Check-out process detected, but owner confirmation is pending. An alert notification has been sent.',
                    'license_plate' => $vehicle->license_plate,
                    'ocr_text' => $ocrText,
                    'levenshtein_distance' => $levenshteinDistance,
                ], JsonResponse::HTTP_FORBIDDEN);
            }

            // Proceed with checkout if it's already confirmed
            $activeParking->update([
                'check_out_time' => now(),
                'check_out_image' => $imageName,
                'status' => 'done',
            ]);

            return $this->sendResponse([
                'action' => 'checkout',
                'parking_data' => $activeParking,
                'matching_details' => [
                    'ocr_input' => $ocrText,
                    'matched_license_plate' => $vehicle->license_plate,
                    'levenshtein_distance' => $levenshteinDistance,
                ]
            ], 'Vehicle checked out successfully.');
        } else {
            // =================================
            // --- CHECK-IN LOGIC is executed ---
            // =================================
            $image->storeAs('checkin', $imageName, 'public');

            $newParking = Parking::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $vehicle->user_id, // Get user_id from the vehicle relationship
                'check_in_time' => now(),
                'check_in_image' => $imageName,
                'status' => 'parked',
                'is_check_out_confirmed' => false, // Set default
            ]);

            return $this->sendResponse([
                'action' => 'checkin',
                'parking_data' => $newParking,
                'matching_details' => [
                    'ocr_input' => $ocrText,
                    'matched_license_plate' => $vehicle->license_plate,
                    'levenshtein_distance' => $levenshteinDistance,
                ]
            ], 'Vehicle checked in successfully.');
        }
    }
}
