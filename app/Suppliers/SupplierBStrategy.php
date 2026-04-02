<?php

namespace App\Suppliers;

use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;
use App\Suppliers\Contracts\HotelFetchingStrategy;
use Illuminate\Support\Facades\Log;

class SupplierBStrategy implements HotelFetchingStrategy
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
            ['name' => 'The Shard Hotel', 'location' => 'London, UK', 'price_per_night' => 340.00, 'availability' => 3, 'rating' => 4.6],
            ['name' => 'Montmartre Residence', 'location' => 'Paris, France', 'price_per_night' => 165.00, 'availability' => 7, 'rating' => 4.0],
            ['name' => 'Burj Vista Inn', 'location' => 'Dubai, UAE', 'price_per_night' => 290.00, 'availability' => 6, 'rating' => 4.4],
            ['name' => 'Brooklyn Heights Hotel', 'location' => 'New York, USA', 'price_per_night' => 245.00, 'availability' => 9, 'rating' => 3.8],
            ['name' => 'Cairo Citadel Suites', 'location' => 'Cairo, Egypt', 'price_per_night' => 88.00, 'availability' => 18, 'rating' => 3.6],
            ['name' => 'Kensington Royal', 'location' => 'London, UK', 'price_per_night' => 410.00, 'availability' => 2, 'rating' => 4.9],
            ['name' => 'Palm Jumeirah Palace', 'location' => 'Dubai, UAE', 'price_per_night' => 450.00, 'availability' => 4, 'rating' => 5.0],
        ];

        return array_map(
            fn (array $hotel) => new HotelDTO(
                name: $hotel['name'],
                location: $hotel['location'],
                pricePerNight: $hotel['price_per_night'],
                availableRooms: $hotel['availability'],
                rating: $hotel['rating'],
                source: 'supplier_b',
            ),
            $rawHotels,
        );
    }
}
