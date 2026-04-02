<?php

namespace App\Suppliers;

use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;
use App\Suppliers\Contracts\HotelFetchingStrategy;
use Illuminate\Support\Facades\Log;

class SupplierCStrategy implements HotelFetchingStrategy
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
            ['property_title' => 'Grand Nile Tower', 'destination' => 'Cairo, Egypt', 'rate' => 135.00, 'units_free' => 5, 'guest_score' => 4.6],
            ['property_title' => 'Eiffel Panorama', 'destination' => 'Paris, France', 'rate' => 250.00, 'units_free' => 2, 'guest_score' => 4.7],
            ['property_title' => 'Downtown Dubai Loft', 'destination' => 'Dubai, UAE', 'rate' => 199.00, 'units_free' => 10, 'guest_score' => 4.2],
            ['property_title' => 'Times Square Central', 'destination' => 'New York, USA', 'rate' => 375.00, 'units_free' => 3, 'guest_score' => 4.5],
            ['property_title' => 'Thames Riverside', 'destination' => 'London, UK', 'rate' => 210.00, 'units_free' => 8, 'guest_score' => 4.1],
            ['property_title' => 'Sphinx Garden Resort', 'destination' => 'Cairo, Egypt', 'rate' => 72.00, 'units_free' => 20, 'guest_score' => 3.2],
            ['property_title' => 'Champs-Élysées Palace', 'destination' => 'Paris, France', 'rate' => 390.00, 'units_free' => 1, 'guest_score' => 4.9],
            ['property_title' => 'Harlem Jazz Hotel', 'destination' => 'New York, USA', 'rate' => 160.00, 'units_free' => 11, 'guest_score' => 3.7],
        ];

        return array_map(
            fn (array $hotel) => new HotelDTO(
                name: $hotel['property_title'],
                location: $hotel['destination'],
                pricePerNight: $hotel['rate'],
                availableRooms: $hotel['units_free'],
                rating: $hotel['guest_score'],
                source: 'supplier_c',
            ),
            $rawHotels,
        );
    }
}
