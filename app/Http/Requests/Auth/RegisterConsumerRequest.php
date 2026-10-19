<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use App\Services\CatalogContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterConsumerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = app(CatalogContext::class)->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => [
                'bail',
                'required',
                'string',
                'regex:/^(?:\d{11}|[A-Z0-9]{12}\d{2})$/',
                new Cpf,
                new Cnpj,
                Rule::unique(User::class, 'document')->where('business_id', $businessId),
            ],
            'phone' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->where('business_id', $businessId),
            ],
            'photo' => [
                'bail',
                'nullable',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'image',
                'max:5120',
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $document = preg_replace(
            '/[^A-Z0-9]/',
            '',
            Str::upper((string) $this->input('document')),
        ) ?? '';
        $phone = preg_replace('/\D/', '', (string) $this->input('phone')) ?? '';

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'document' => $document,
            'phone' => $phone,
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome completo.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'document.required' => 'Informe seu CPF ou CNPJ.',
            'document.regex' => 'Informe um CPF com 11 dígitos ou CNPJ com 14 caracteres.',
            'document.unique' => 'Este CPF/CNPJ já está cadastrado neste catálogo.',
            'phone.required' => 'Informe seu telefone ou WhatsApp.',
            'phone.regex' => 'Informe um telefone com 10 ou 11 dígitos.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado neste catálogo.',
            'photo.mimes' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.extensions' => 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.',
            'photo.image' => 'O arquivo enviado deve ser uma imagem válida.',
            'photo.max' => 'A foto deve ter no máximo 5 MB.',
            'password.required' => 'Informe uma senha.',
            'password.confirmed' => 'A confirmação da senha não corresponde.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
        ];
    }
}
