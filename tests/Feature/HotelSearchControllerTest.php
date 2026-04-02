<?php

use App\Actions\CacheHotelSearchAction;
use App\Actions\ProcessHotelResultsAction;
use App\Actions\SearchHotelsAction;
use App\DTOs\HotelDTO;
use App\DTOs\SearchParamsDTO;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->validParams = [
        'location'  => 'Cairo',
        'check_in'  => now()->addDays(1)->format('Y-m-d'),
        'check_out' => now()->addDays(3)->format('Y-m-d'),
    ];
});

describe('Validation', function () {

    it('requires location, check_in, and check_out', function () {
        $this->getJson('/api/hotels/search')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['location', 'check_in', 'check_out']);
    });

    it('rejects location shorter than 2 characters', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'location' => 'X',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['location']);
    });

    it('rejects check_in in the past', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'check_in'  => '2020-01-01',
            'check_out' => '2020-01-05',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in']);
    });

    it('rejects check_out before check_in', function () {
        $checkIn = now()->addDays(5)->format('Y-m-d');
        $checkOut = now()->addDays(3)->format('Y-m-d');

        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_out']);
    });

    it('rejects invalid date format for check_in', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'check_in' => '01-09-2026',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_in']);
    });

    it('rejects guests below 1', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'guests' => 0,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guests']);
    });

    it('rejects guests above 30', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'guests' => 31,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['guests']);
    });

    it('rejects negative min_price', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'min_price' => -10,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['min_price']);
    });

    it('rejects max_price not greater than min_price', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'min_price' => 200,
            'max_price' => 100,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);
    });

    it('rejects invalid sort_by value', function () {
        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'sort_by' => 'name',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort_by']);
    });

    it('accepts valid optional parameters', function () {
        $mock = Mockery::mock(CacheHotelSearchAction::class);
        $mock->shouldReceive('__invoke')->once()->andReturn(collect());
        $this->app->instance(CacheHotelSearchAction::class, $mock);

        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'guests'    => 2,
            'min_price' => 50,
            'max_price' => 300,
            'sort_by'   => 'price',
        ]))
            ->assertOk();
    });
});

describe('Successful response', function () {

    it('returns a 200 with data array', function () {
        $mock = Mockery::mock(CacheHotelSearchAction::class);
        $mock->shouldReceive('__invoke')->once()->andReturn(collect([
            new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 120.50, 8, 4.7, 'supplier_a'),
        ]));
        $this->app->instance(CacheHotelSearchAction::class, $mock);

        $this->getJson('/api/hotels/search?' . http_build_query($this->validParams))
            ->assertOk()
            ->assertJsonStructure(['data' => [['name', 'location', 'pricePerNight', 'availableRooms', 'rating', 'source']]]);
    });

    it('returns correct hotel fields in the response', function () {
        $mock = Mockery::mock(CacheHotelSearchAction::class);
        $mock->shouldReceive('__invoke')->once()->andReturn(collect([
            new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 120.50, 8, 4.7, 'supplier_a'),
        ]));
        $this->app->instance(CacheHotelSearchAction::class, $mock);

        $this->getJson('/api/hotels/search?' . http_build_query($this->validParams))
            ->assertOk()
            ->assertJsonFragment([
                'name'           => 'Grand Nile Tower',
                'location'       => 'Cairo, Egypt',
                'pricePerNight'  => 120.50,
                'availableRooms' => 8,
                'rating'         => 4.7,
                'source'         => 'supplier_a',
            ]);
    });

    it('returns an empty data array when no hotels match', function () {
        $mock = Mockery::mock(CacheHotelSearchAction::class);
        $mock->shouldReceive('__invoke')->once()->andReturn(collect());
        $this->app->instance(CacheHotelSearchAction::class, $mock);

        $this->getJson('/api/hotels/search?' . http_build_query($this->validParams))
            ->assertOk()
            ->assertExactJson(['data' => []]);
    });

    it('returns multiple hotels in the data array', function () {
        $mock = Mockery::mock(CacheHotelSearchAction::class);
        $mock->shouldReceive('__invoke')->once()->andReturn(collect([
            new HotelDTO('Grand Nile Tower', 'Cairo, Egypt', 120.50, 8, 4.7, 'supplier_a'),
            new HotelDTO('Cairo Citadel Suites', 'Cairo, Egypt', 88.00, 18, 3.6, 'supplier_b'),
            new HotelDTO('Pyramids View Hotel', 'Cairo, Egypt', 95.00, 15, 3.5, 'supplier_a'),
        ]));
        $this->app->instance(CacheHotelSearchAction::class, $mock);

        $this->getJson('/api/hotels/search?' . http_build_query($this->validParams))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('passes search params to the action correctly', function () {
        $mock = Mockery::mock(CacheHotelSearchAction::class);
        $mock->shouldReceive('__invoke')
            ->once()
            ->withArgs(function (SearchParamsDTO $params) {
                return $params->location === 'Cairo'
                    && $params->guests === 2
                    && $params->minPrice === 80.0
                    && $params->maxPrice === 300.0
                    && $params->sortBy === 'rating';
            })
            ->andReturn(collect());
        $this->app->instance(CacheHotelSearchAction::class, $mock);

        $this->getJson('/api/hotels/search?' . http_build_query([
            ...$this->validParams,
            'guests'    => 2,
            'min_price' => 80,
            'max_price' => 300,
            'sort_by'   => 'rating',
        ]))
            ->assertOk();
    });
});
