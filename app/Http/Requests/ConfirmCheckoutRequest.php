<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'seller_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.integer' => 'O cliente selecionado é inválido.',
            'customer_id.min' => 'O cliente selecionado é inválido.',
            'seller_id.integer' => 'O vendedor selecionado é inválido.',
            'seller_id.min' => 'O vendedor selecionado é inválido.',
        ];
    }
}
