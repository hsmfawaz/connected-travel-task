<?php

namespace App\Suppliers;

use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;
use App\Suppliers\Contracts\HotelFetchingStrategy;
use Illuminate\Support\Facades\Log;

class SupplierAStrategy implements HotelFetchingStrategy
{
    public function fetch(SearchParamsDTO $params): array
    {
        try {
            return $this->fetchHotels();
        } catch (\Throwable $e) {
            Log::channel('suppliers')->warning('Supplier fetch failed', [
                'supplier' => static::class,
                'error'    => $e->getMessage(),
            ]);

            return [];
        }
    }

    /** @return HotelDTO[] */
    private function fetchHotels(): array
    {
        $rawHotels = [
            ['hotel_name' => 'Le Marais Boutique', 'city' => 'Paris, France', 'nightly_rate' => 185.00, 'rooms_available' => 4, 'stars' => 4.3],
            ['hotel_name' => 'Grand Nile Tower', 'city' => 'Cairo, Egypt', 'nightly_rate' => 120.50, 'rooms_available' => 8, 'stars' => 4.7],
            ['hotel_name' => 'Manhattan Plaza', 'city' => 'New York, USA', 'nightly_rate' => 310.00, 'rooms_available' => 2, 'stars' => 4.1],
            ['hotel_name' => 'Dubai Marina Resort', 'city' => 'Dubai, UAE', 'nightly_rate' => 275.00, 'rooms_available' => 12, 'stars' => 4.8],
            ['hotel_name' => 'Chelsea Garden Inn', 'city' => 'London, UK', 'nightly_rate' => 198.00, 'rooms_available' => 5, 'stars' => 3.9],
            ['hotel_name' => 'Pyramids View Hotel', 'city' => 'Cairo, Egypt', 'nightly_rate' => 95.00, 'rooms_available' => 15, 'stars' => 3.5],
            ['hotel_name' => 'Seine River Lodge', 'city' => 'Paris, France', 'nightly_rate' => 220.00, 'rooms_available' => 3, 'stars' => 4.5],
            ['hotel_name' => 'Central Park Suites', 'city' => 'New York, USA', 'nightly_rate' => 430.00, 'rooms_available' => 1, 'stars' => 4.9],
        ];

        return array_map(
            fn (array $hotel) => new HotelDTO(
                name: $hotel['hotel_name'],
                location: $hotel['city'],
                pricePerNight: $hotel['nightly_rate'],
                availableRooms: $hotel['rooms_available'],
                rating: $hotel['stars'],
                source: 'supplier_a',
            ),
            $rawHotels,
        );
    }
}
