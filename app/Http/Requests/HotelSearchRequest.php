<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location'  => ['required', 'string', 'min:2'],
            'check_in'  => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'date_format:Y-m-d', 'after:check_in'],
            'guests'    => ['sometimes', 'integer', 'min:1', 'max:30'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'gt:min_price'],
            'sort_by'   => ['sometimes', 'string', 'in:price,rating'],
        ];
    }
}
