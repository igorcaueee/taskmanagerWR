<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadCapturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'empresa' => ['nullable', 'string', 'max:255'],
            'possibilidade' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => strip_tags((string) $this->nome),
            'empresa' => $this->empresa !== null ? strip_tags((string) $this->empresa) : null,
            'telefone' => $this->telefone !== null ? preg_replace('/[^\d\s\(\)\-\+]/', '', (string) $this->telefone) : null,
            'possibilidade' => $this->possibilidade !== null ? strip_tags((string) $this->possibilidade) : null,
        ]);
    }
}
