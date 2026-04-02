<?php

namespace App\DTOs;

use App\Http\Requests\HotelSearchRequest;

readonly class SearchParamsDTO
{
    public function __construct(
        public string  $location,
        public string  $checkIn,
        public string  $checkOut,
        public ?int    $guests,
        public ?float  $minPrice,
        public ?float  $maxPrice,
        public ?string $sortBy,
    ) {}

    public function cacheKey(): string
    {
        return 'hotel_search:' . md5(serialize([
            $this->location,
            $this->checkIn,
            $this->checkOut,
            $this->guests,
            $this->minPrice,
            $this->maxPrice,
            $this->sortBy,
        ]));
    }

    public static function fromRequest(HotelSearchRequest $request): self
    {
        return new self(
            location: $request->validated('location'),
            checkIn: $request->validated('check_in'),
            checkOut: $request->validated('check_out'),
            guests: $request->validated('guests'),
            minPrice: $request->validated('min_price'),
            maxPrice: $request->validated('max_price'),
            sortBy: $request->validated('sort_by'),
        );
    }
}
