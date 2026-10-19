<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ConfirmCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();

        $this->merge([
            'customer' => [
                'name' => trim((string) $user?->name),
                'document' => preg_replace('/\D+/', '', (string) $user?->document) ?? '',
                'phone' => preg_replace('/\D+/', '', (string) $user?->phone) ?? '',
                'email' => Str::lower(trim((string) $user?->email)),
            ],
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer' => ['required', 'array:name,document,phone,email'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.document' => ['bail', 'required', 'string', 'regex:/^\d{11}$/', new Cpf],
            'customer.phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'customer.email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array{name: string, document: string, phone: string, email: string}
     */
    public function customer(): array
    {
        $customer = $this->validated('customer');

        return [
            'name' => $customer['name'],
            'document' => $customer['document'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer.name.required' => 'O nome da sua conta é obrigatório para finalizar o pedido.',
            'customer.name.max' => 'O nome da sua conta deve ter no máximo 255 caracteres.',
            'customer.document.required' => 'O CPF da sua conta é obrigatório para finalizar o pedido.',
            'customer.document.regex' => 'O CPF da sua conta deve conter 11 dígitos.',
            'customer.phone.required' => 'O telefone da sua conta é obrigatório para finalizar o pedido.',
            'customer.phone.regex' => 'O telefone da sua conta deve conter 10 ou 11 dígitos.',
            'customer.email.required' => 'O e-mail da sua conta é obrigatório para finalizar o pedido.',
            'customer.email.email' => 'O e-mail da sua conta é inválido.',
        ];
    }
}
