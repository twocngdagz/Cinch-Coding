<?php

declare(strict_types=1);

namespace App\Features\Orders\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ValidateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
