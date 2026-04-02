<?php

namespace App\Actions;

use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;
use Illuminate\Support\Collection;

class ProcessHotelResultsAction
{
    /**
     * @param  Collection<int, HotelDTO>  $hotels
     * @return Collection<int, HotelDTO>
     */
    public function __invoke(Collection $hotels, SearchParamsDTO $params): Collection
    {
        return $hotels
            ->groupBy(fn (HotelDTO $hotel) => strtolower(trim($hotel->name)) . '||' . strtolower(trim($hotel->location)))
            ->map(fn (Collection $group) => $group->sortBy('pricePerNight')->first())
            ->values()
            ->when($params->minPrice, fn (Collection $c) => $c->where('pricePerNight', '>=', $params->minPrice))
            ->when($params->maxPrice, fn (Collection $c) => $c->where('pricePerNight', '<=', $params->maxPrice))
            ->when($params->guests, fn (Collection $c) => $c->where('availableRooms', '>=', $params->guests))
            ->when($params->location, fn (Collection $c) => $c->filter(
                fn (HotelDTO $h) => str_contains(strtolower($h->location), strtolower($params->location))
            ))
            ->when($params->sortBy === 'price', fn (Collection $c) => $c->sortBy('pricePerNight'))
            ->when($params->sortBy === 'rating', fn (Collection $c) => $c->sortByDesc('rating'))
            ->values();
    }
}
