<?php

namespace App\Actions;

use App\DTOs\SearchParamsDTO;
use App\Suppliers\Contracts\HotelFetchingStrategy;
use App\Suppliers\SupplierAStrategy;
use App\Suppliers\SupplierBStrategy;
use App\Suppliers\SupplierCStrategy;
use App\Suppliers\SupplierDStrategy;
use Illuminate\Support\Collection;
use Laravel\Octane\Facades\Octane;

class SearchHotelsAction
{
    public function __construct(
        private readonly ProcessHotelResultsAction $processAction,
    ) {}

    public function __invoke(SearchParamsDTO $params): Collection
    {
        return $this->fetchFromSuppliers($params);
    }

    private function fetchFromSuppliers(SearchParamsDTO $params): Collection
    {
        $strategies = collect([
            new SupplierAStrategy(),
            new SupplierBStrategy(),
            new SupplierCStrategy(),
            new SupplierDStrategy(),
        ]);

        $results = Octane::concurrently(
            $strategies->map(
                fn (HotelFetchingStrategy $strategy) => fn () => $strategy->fetch($params)
            )->toArray(),
            3000,
        );

        $hotels = collect($results)->flatten(1);

        return ($this->processAction)($hotels, $params);
    }
}
