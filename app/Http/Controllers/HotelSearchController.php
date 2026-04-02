<?php

namespace App\Http\Controllers;

use App\Actions\CacheHotelSearchAction;
use App\DTOs\SearchParamsDTO;
use App\Http\Requests\HotelSearchRequest;
use Illuminate\Http\JsonResponse;

class HotelSearchController
{
    public function __construct(
        private readonly CacheHotelSearchAction $searchAction,
    ) {}

    public function __invoke(HotelSearchRequest $request): JsonResponse
    {
        $params = SearchParamsDTO::fromRequest($request);

        $hotels = ($this->searchAction)($params);

        return response()->json(['data' => $hotels->values()]);
    }
}
