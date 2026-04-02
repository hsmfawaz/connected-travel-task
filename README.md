# Multi-Supplier Hotel Search API

A Laravel API that aggregates hotel listings from four external suppliers concurrently, deduplicates results, and returns filtered, sorted data through a single endpoint.

## Setup Instructions

**Prerequisites:** PHP 8.3+, Composer, Swoole extension.

```bash
# Install the Swoole extension
pecl install swoole

# Clone and install dependencies
composer install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Start the Octane server with Swoole
php artisan octane:start --server=swoole --port=8000
```

## API Endpoint

### `GET /api/hotels/search`

| Parameter   | Required | Type    | Description                              |
|-------------|----------|---------|------------------------------------------|
| `location`  | Yes      | string  | Destination to search (min 2 chars)      |
| `check_in`  | Yes      | date    | Check-in date (`Y-m-d`, today or later)  |
| `check_out` | Yes      | date    | Check-out date (`Y-m-d`, after check_in) |
| `guests`    | No       | integer | Minimum available rooms (1–30)           |
| `min_price` | No       | numeric | Minimum price per night                  |
| `max_price` | No       | numeric | Maximum price per night (must be > min)  |
| `sort_by`   | No       | string  | `price` (ascending) or `rating` (desc)   |

### Example Request

```
GET /api/hotels/search?location=Cairo&check_in=2025-09-01&check_out=2025-09-05&guests=2&min_price=80&max_price=300&sort_by=price
```

### Example Response

```json
{
  "data": [
    {
      "name": "Pyramids View Hotel",
      "location": "Cairo, Egypt",
      "pricePerNight": 95.0,
      "availableRooms": 15,
      "rating": 3.5,
      "source": "supplier_a"
    },
    {
      "name": "Grand Nile Tower",
      "location": "Cairo, Egypt",
      "pricePerNight": 120.5,
      "availableRooms": 8,
      "rating": 4.7,
      "source": "supplier_a"
    },
    {
      "name": "Cairo Citadel Suites",
      "location": "Cairo, Egypt",
      "pricePerNight": 88.0,
      "availableRooms": 18,
      "rating": 3.6,
      "source": "supplier_b"
    }
  ]
}
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run unit tests only (merge, filter, deduplication)
php artisan test --filter=ProcessHotelResultsActionTest

# Run feature tests only (controller validation and response)
php artisan test --filter=HotelSearchControllerTest
```

### Test Coverage

| Suite | File | Tests |
|---|---|---|
| Unit | `ProcessHotelResultsActionTest` | 17 tests — merge/dedup logic, filtering, sorting |
| Feature | `HotelSearchControllerTest` | 16 tests — request validation, response structure, param passing |

## Design Decisions

### Merging & Deduplication

When the same hotel appears across multiple suppliers (identified by normalising the hotel name and location to lowercase and combining them into a composite key), the system groups all occurrences and retains only the listing with the lowest price per night. This ensures the consumer always sees the best available deal for a given property without encountering confusing duplicate entries. The normalisation step (lowercase + trim) guards against trivial formatting differences between suppliers breaking the dedup logic.

### Parallelism

The API uses `Octane::concurrently()` backed by Swoole coroutines to dispatch all four supplier fetches simultaneously. Unlike Guzzle's async pool (which multiplexes on a single event loop) or queued jobs (which add latency through serialisation and worker polling), Swoole coroutines provide true non-blocking I/O where each fetch runs in its own lightweight coroutine within the same process. This means total response time equals the slowest healthy supplier rather than the sum of all four. The 3-second timeout on the concurrent call acts as a circuit breaker, preventing a single slow or unresponsive supplier from degrading the entire response.

### Strategy Pattern

Each supplier is encapsulated as a concrete strategy implementing `HotelFetchingStrategy`. This design was chosen over an adapter or monolithic service because each supplier's integration involves a complete algorithm — calling a unique API, mapping proprietary field names, and normalising into DTOs. Each strategy owns its own error handling: failures are logged to a dedicated `suppliers` channel and gracefully return an empty result set, so one broken supplier never takes down the search. The strategy pattern makes adding a fifth supplier a zero-impact change to existing code: create a new strategy class, add it to the strategies array in `SearchHotelsAction`, and the rest of the pipeline (processing, merging, filtering) requires no modification.

### Caching Layer

To improve performance and prevent rate-limiting from external suppliers, the API implements a custom caching layer encapsulated in the `CacheHotelSearchAction`. This acts as a decorator around the primary search pipeline. Cache keys are deterministically generated via MD5 hashing of serialized search parameters. The caching strategy strictly enforces a memory cap of 10 active cache keys using a FIFO (first-in-first-out) eviction strategy. When a new distinct search is executed that pushes the tracker above this 10-key limit, the oldest tracked key is safely evicted using `Cache::forget()`. All tracked requests employ a 5-minute Time-To-Live (TTL), striking a balance between providing instantaneous response times and preventing stale pricing or availability data from reaching the user.
