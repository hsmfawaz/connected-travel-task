<?php

namespace App\DTOs;

readonly class HotelDTO
{
    public function __construct(
        public string $name,
        public string $location,
        public float  $pricePerNight,
        public int    $availableRooms,
        public float  $rating,
        public string $source,
    ) {}
}
