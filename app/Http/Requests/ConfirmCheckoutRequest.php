<?php

namespace App\Http\Requests;

use App\Services\CatalogContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = app(CatalogContext::class)->id();

        return [
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('persons', 'id')
                    ->where('business_id', $businessId)
                    ->where('type', 'customer')
                    ->where('active', true),
            ],
            'seller_id' => [
                'nullable',
                'integer',
                Rule::exists('persons', 'id')
                    ->where('business_id', $businessId)
                    ->where('type', 'seller')
                    ->where('active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.integer' => 'O cliente selecionado é inválido.',
            'customer_id.exists' => 'O cliente selecionado é inválido.',
            'seller_id.integer' => 'O vendedor selecionado é inválido.',
            'seller_id.exists' => 'O vendedor selecionado é inválido.',
        ];
    }
}
