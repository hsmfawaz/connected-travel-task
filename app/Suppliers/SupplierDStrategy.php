<?php

namespace App\Suppliers;

use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;
use App\Suppliers\Contracts\HotelFetchingStrategy;
use Illuminate\Support\Facades\Log;

class SupplierDStrategy implements HotelFetchingStrategy
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
            ['title' => 'The Shard Hotel', 'area' => 'London, UK', 'cost_per_night' => 315.00, 'vacancies' => 5, 'review_score' => 4.5],
            ['title' => 'Soho Grand', 'area' => 'New York, USA', 'cost_per_night' => 285.00, 'vacancies' => 4, 'review_score' => 4.3],
            ['title' => 'Giza Plateau Lodge', 'area' => 'Cairo, Egypt', 'cost_per_night' => 65.00, 'vacancies' => 14, 'review_score' => 3.0],
            ['title' => 'La Défense Tower', 'area' => 'Paris, France', 'cost_per_night' => 178.00, 'vacancies' => 6, 'review_score' => 3.8],
            ['title' => 'Creek Side Hotel', 'area' => 'Dubai, UAE', 'cost_per_night' => 155.00, 'vacancies' => 9, 'review_score' => 4.0],
            ['title' => 'Mayfair Luxury Rooms', 'area' => 'London, UK', 'cost_per_night' => 440.00, 'vacancies' => 1, 'review_score' => 4.8],
            ['title' => 'Nile Felucca Inn', 'area' => 'Cairo, Egypt', 'cost_per_night' => 78.00, 'vacancies' => 16, 'review_score' => 3.4],
            ['title' => 'JBR Beach Resort', 'area' => 'Dubai, UAE', 'cost_per_night' => 330.00, 'vacancies' => 7, 'review_score' => 4.6],
        ];

        return array_map(
            fn (array $hotel) => new HotelDTO(
                name: $hotel['title'],
                location: $hotel['area'],
                pricePerNight: $hotel['cost_per_night'],
                availableRooms: $hotel['vacancies'],
                rating: $hotel['review_score'],
                source: 'supplier_d',
            ),
            $rawHotels,
        );
    }
}
