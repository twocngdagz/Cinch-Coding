<?php

declare(strict_types=1);

namespace App\Features\Cart\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddCartItemRequest extends FormRequest
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
            'variant_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
