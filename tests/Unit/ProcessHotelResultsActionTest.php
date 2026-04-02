<?php

use App\Actions\ProcessHotelResultsAction;
use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;

function noFilterParams(): SearchParamsDTO
{
    return new SearchParamsDTO(
        location: '',
        checkIn: '2026-09-01',
        checkOut: '2026-09-05',
        guests: null,
        minPrice: null,
        maxPrice: null,
        sortBy: null,
    );
}

describe('Merge', function () {
    beforeEach(function () {
        $this->action = new ProcessHotelResultsAction();
    });

    it('returns an empty collection when given no hotels', function () {
        $result = ($this->action)(collect(), noFilterParams());

        expect($result)->toBeEmpty();
    });

    it('keeps a single hotel unchanged', function () {
        $hotel = new HotelDTO('Hotel A', 'Paris, France', 150.00, 5, 4.2, 'supplier_a');

        $result = ($this->action)(collect([$hotel]), noFilterParams());

        expect($result)->toHaveCount(1)
            ->and($result->first()->name)->toBe('Hotel A')
            ->and($result->first()->pricePerNight)->toBe(150.00);
    });

    it('deduplicates hotels with the same name and location, keeping the lowest price', function () {
        $expensive = new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 135.00, 5, 4.6, 'supplier_c');
        $cheap = new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 120.50, 8, 4.7, 'supplier_a');

        $result = ($this->action)(collect([$expensive, $cheap]), noFilterParams());

        expect($result)->toHaveCount(1)
            ->and($result->first()->pricePerNight)->toBe(120.50);
    });

    it('treats name comparison as case-insensitive', function () {
        $upper = new HotelDTO('THE SHARD HOTEL', 'London, UK', 340.00, 3, 4.6, 'supplier_b');
        $lower = new HotelDTO('the shard hotel', 'London, UK', 315.00, 5, 4.5, 'supplier_d');

        $result = ($this->action)(collect([$upper, $lower]), noFilterParams());

        expect($result)->toHaveCount(1)
            ->and($result->first()->pricePerNight)->toBe(315.00);
    });

    it('keeps hotels with the same name but different locations as separate entries', function () {
        $paris = new HotelDTO('Grand Hotel', 'Paris, France', 200.00, 4, 4.3, 'supplier_a');
        $london = new HotelDTO('Grand Hotel', 'London, UK', 250.00, 3, 4.1, 'supplier_b');

        $result = ($this->action)(collect([$paris, $london]), noFilterParams());

        expect($result)->toHaveCount(2);
    });

    it('preserves the source field of the cheapest hotel', function () {
        $fromA = new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 120.50, 8, 4.7, 'supplier_a');
        $fromC = new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 135.00, 5, 4.6, 'supplier_c');

        $result = ($this->action)(collect([$fromA, $fromC]), noFilterParams());

        expect($result->first()->source)->toBe('supplier_a');
    });

    it('handles hotels from all four suppliers simultaneously', function () {
        $hotels = collect([
            new HotelDTO('Hotel Alpha', 'Paris, France', 180.00, 4, 4.3, 'supplier_a'),
            new HotelDTO('Hotel Beta', 'London, UK', 220.00, 3, 4.1, 'supplier_b'),
            new HotelDTO('Hotel Gamma', 'Dubai, UAE', 300.00, 6, 4.5, 'supplier_c'),
            new HotelDTO('Hotel Delta', 'New York, USA', 250.00, 2, 4.0, 'supplier_d'),
            new HotelDTO('Hotel Alpha', 'Paris, France', 170.00, 5, 4.2, 'supplier_c'),
        ]);

        $result = ($this->action)($hotels, noFilterParams());

        expect($result)->toHaveCount(4)
            ->and($result->firstWhere('name', 'Hotel Alpha')->pricePerNight)->toBe(170.00)
            ->and($result->firstWhere('name', 'Hotel Alpha')->source)->toBe('supplier_c');
    });
});

describe('Filter', function () {
    beforeEach(function () {
        $this->action = new ProcessHotelResultsAction();
        $this->hotels = collect([
            new HotelDTO('Le Marais Boutique', 'Paris, France', 185.00, 4, 4.3, 'supplier_a'),
            new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 120.50, 8, 4.7, 'supplier_a'),
            new HotelDTO('The Shard Hotel', 'London, UK', 315.00, 5, 4.5, 'supplier_d'),
            new HotelDTO('Giza Plateau Lodge', 'Cairo, Egypt', 65.00, 14, 3.0, 'supplier_d'),
            new HotelDTO('Central Park Suites', 'New York, USA', 430.00, 1, 4.9, 'supplier_a'),
        ]);
    });

    it('returns all hotels when no filters are applied', function () {
        $result = ($this->action)($this->hotels, noFilterParams());

        expect($result)->toHaveCount(5);
    });

    it('filters hotels below min_price', function () {
        $params = new SearchParamsDTO(
            location: '',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: 150.00,
            maxPrice: null,
            sortBy: null,
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toHaveCount(3)
            ->and($result->pluck('pricePerNight')->min())->toBeGreaterThanOrEqual(150.00);
    });

    it('filters hotels above max_price', function () {
        $params = new SearchParamsDTO(
            location: '',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: null,
            maxPrice: 200.00,
            sortBy: null,
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toHaveCount(3)
            ->and($result->pluck('pricePerNight')->max())->toBeLessThanOrEqual(200.00);
    });

    it('filters by both min and max price', function () {
        $params = new SearchParamsDTO(
            location: '',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: 100.00,
            maxPrice: 320.00,
            sortBy: null,
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toHaveCount(3)
            ->and($result->every(fn (HotelDTO $h) => $h->pricePerNight >= 100.00 && $h->pricePerNight <= 320.00))->toBeTrue();
    });

    it('filters hotels where available_rooms is less than guests', function () {
        $params = new SearchParamsDTO(
            location: '',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: 6,
            minPrice: null,
            maxPrice: null,
            sortBy: null,
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toHaveCount(2)
            ->and($result->every(fn (HotelDTO $h) => $h->availableRooms >= 6))->toBeTrue();
    });

    it('filters by location string (case-insensitive partial match)', function () {
        $params = new SearchParamsDTO(
            location: 'cairo',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: null,
            maxPrice: null,
            sortBy: null,
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toHaveCount(2)
            ->and($result->every(fn (HotelDTO $h) => str_contains(strtolower($h->location), 'cairo')))->toBeTrue();
    });

    it('sorts by price ascending when sort_by is price', function () {
        $params = new SearchParamsDTO(
            location: '',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: null,
            maxPrice: null,
            sortBy: 'price',
        );

        $result = ($this->action)($this->hotels, $params);

        $prices = $result->pluck('pricePerNight')->all();

        expect($prices)->toEqual([65.00, 120.50, 185.00, 315.00, 430.00]);
    });

    it('sorts by rating descending when sort_by is rating', function () {
        $params = new SearchParamsDTO(
            location: '',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: null,
            maxPrice: null,
            sortBy: 'rating',
        );

        $result = ($this->action)($this->hotels, $params);

        $ratings = $result->pluck('rating')->all();

        expect($ratings)->toEqual([4.9, 4.7, 4.5, 4.3, 3.0]);
    });

    it('returns an empty collection when no hotels match the filters', function () {
        $params = new SearchParamsDTO(
            location: 'Tokyo',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: null,
            minPrice: null,
            maxPrice: null,
            sortBy: null,
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toBeEmpty();
    });

    it('applies all filters simultaneously', function () {
        $params = new SearchParamsDTO(
            location: 'cairo',
            checkIn: '2026-09-01',
            checkOut: '2026-09-05',
            guests: 5,
            minPrice: 60.00,
            maxPrice: 130.00,
            sortBy: 'price',
        );

        $result = ($this->action)($this->hotels, $params);

        expect($result)->toHaveCount(2)
            ->and($result->first()->name)->toBe('Giza Plateau Lodge')
            ->and($result->last()->name)->toBe('Grand Nile Tower');
    });
});
