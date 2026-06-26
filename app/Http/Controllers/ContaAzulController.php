<?php

namespace App\Http\Controllers;

use App\Jobs\SincronizarEmpresaContaAzul;
use App\Models\Cliente;
use App\Services\ContaAzulService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContaAzulController extends Controller
{
    public function __construct(private ContaAzulService $service) {}

    public function redirect(Cliente $cliente): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $url = $this->service->getAuthorizationUrl($cliente);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $code      = $request->string('code');
        $clienteId = $request->integer('state');

        if ($code->isEmpty() || ! $clienteId) {
            return redirect()->route('clientes.show', $clienteId ?: 1)
                ->with('error', 'Parâmetros inválidos no callback da Conta Azul.');
        }

        $cliente = Cliente::findOrFail($clienteId);

        try {
            $this->service->handleCallback($code->toString(), $cliente);
        } catch (\Throwable $e) {
            return redirect()->route('clientes.show', $cliente->id)
                ->with('error', 'Falha ao conectar Conta Azul: ' . $e->getMessage());
        }

        return redirect()->route('clientes.show', $cliente->id)
            ->with('success', 'Conta Azul conectada com sucesso!');
    }

    public function desconectar(Cliente $cliente): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $cliente->update([
            'conta_azul_conectada'       => false,
            'conta_azul_access_token'    => null,
            'conta_azul_refresh_token'   => null,
            'conta_azul_token_expira_em' => null,
        ]);

        return redirect()->route('clientes.show', $cliente->id)
            ->with('success', 'Conta Azul desconectada.');
    }

    public function sincronizarAgora(Cliente $cliente): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);
        abort_unless($cliente->conta_azul_conectada, 422, 'Empresa não conectada à Conta Azul.');

        SincronizarEmpresaContaAzul::dispatch($cliente->id);

        return redirect()->route('clientes.show', $cliente->id)
            ->with('success', 'Sincronização iniciada! Os dados serão atualizados em breve.');
    }
}
