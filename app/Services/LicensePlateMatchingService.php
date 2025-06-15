<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LicensePlateMatchingService
{
    /**
     * Find vehicle with similar license plate using fuzzy matching
     */
    public function findSimilarLicensePlate(string $ocrText, float $threshold = 0.7): array
    {
        // Clean input
        $ocrText = $this->cleanLicensePlate($ocrText);

        // Get all vehicles from database
        $vehicles = Vehicle::select('id', 'license_plate')->get();
        $bestMatch = null;
        $bestScore = 0;

        foreach ($vehicles as $vehicle) {
            // Clean license plate from database
            $dbPlate = $this->cleanLicensePlate($vehicle->license_plate);

            // Calculate Levenshtein distance
            $levDistance = levenshtein($ocrText, $dbPlate);

            // Calculate similarity score (0-1)
            $maxLen = max(strlen($ocrText), strlen($dbPlate));
            $score = $maxLen > 0 ? 1.0 - ($levDistance / $maxLen) : 0;

            // If score is better than previous and above threshold
            if ($score > $bestScore && $score >= $threshold) {
                $bestScore = $score;
                $bestMatch = $vehicle;
            }
        }

        Log::info('License plate fuzzy matching', [
            'input' => $ocrText,
            'matched_with' => $bestMatch?->license_plate,
            'score' => $bestScore,
            'threshold' => $threshold
        ]);

        return [
            'match' => $bestMatch,
            'score' => $bestScore
        ];
    }

    /**
     * Find vehicles with Levenshtein distance matching
     */
    public function findWithLevenshteinDistance(string $ocrLicensePlate, int $maxDistance = 2): Collection
    {
        $vehicles = Vehicle::select('id', 'license_plate')->get();
        
        return $vehicles->map(function ($vehicle) use ($ocrLicensePlate) {
            $licensePlateUpper = strtoupper($vehicle->license_plate);
            $licensePlate2Upper = strtoupper($ocrLicensePlate);

            $levenshtein = levenshtein($licensePlateUpper, $licensePlate2Upper);
            $maxLength = max(strlen($vehicle->license_plate), strlen($ocrLicensePlate));

            $vehicle->distance = $levenshtein;
            $vehicle->ratio = $maxLength > 0 ? $levenshtein / $maxLength : 0;

            return $vehicle;
        })->filter(function ($vehicle) {
            return $vehicle->ratio < 0.5;
        })->sortBy('distance');
    }

    /**
     * Clean license plate by removing spaces and converting to uppercase
     */
    private function cleanLicensePlate(string $licensePlate): string
    {
        return strtoupper(preg_replace('/\s+/', '', $licensePlate));
    }

    /**
     * Find exact match for license plate
     */
    public function findExactMatch(string $licensePlate): ?Vehicle
    {
        return Vehicle::where('license_plate', $licensePlate)->first();
    }
} 