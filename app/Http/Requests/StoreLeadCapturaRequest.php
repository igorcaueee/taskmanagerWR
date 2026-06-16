<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Validator;

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
            'mensagem' => ['nullable', 'string', 'max:2000'],
            'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'g-recaptcha-response.required' => 'Por favor, confirme que você não é um robô.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $token = $this->input('g-recaptcha-response');

            if (empty($token)) {
                return;
            }

            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret'),
                'response' => $token,
                'remoteip' => $this->ip(),
            ]);

            if (! $response->successful() || ! $response->json('success')) {
                $validator->errors()->add('g-recaptcha-response', 'Verificação de CAPTCHA falhou. Tente novamente.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => strip_tags((string) $this->nome),
            'empresa' => $this->empresa !== null ? strip_tags((string) $this->empresa) : null,
            'telefone' => $this->telefone !== null ? preg_replace('/[^\d\s\(\)\-\+]/', '', (string) $this->telefone) : null,
            'mensagem' => $this->mensagem !== null ? strip_tags((string) $this->mensagem) : null,
        ]);
    }
}
