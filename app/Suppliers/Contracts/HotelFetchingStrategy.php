<?php

namespace App\Suppliers\Contracts;

use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;

interface HotelFetchingStrategy
{
    /**
     * Fetch hotels from this supplier for the given search params.
     * Returns a flat array of normalized HotelDTO objects.
     *
     * @return HotelDTO[]
     */
    public function fetch(SearchParamsDTO $params): array;
}
