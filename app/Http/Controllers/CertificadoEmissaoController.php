<?php

namespace App\Http\Controllers;

use App\Models\CertificadoEmissao;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificadoEmissaoController extends Controller
{
    private const MODELOS = ['ECNPJ' => 'e-CNPJ', 'ECPF' => 'e-CPF'];

    private const FORMAS = ['PRESENCIAL' => 'Presencial', 'VIDEO' => 'Vídeo'];

    private const SITUACOES = ['OK', 'PENDENTE', 'BONIFICADO', 'CANCELADO'];

    public function index(Request $request): View
    {
        $aba = $request->input('aba') === 'vencimentos' ? 'vencimentos' : 'emissoes';

        // ─── Aba Emissões ────────────────────────────────────────────────────
        $query = CertificadoEmissao::with('cliente')->orderByDesc('data_emissao')->orderByDesc('id');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('cliente_nome', 'like', $busca)
                    ->orWhere('numero_pedido', 'like', $busca)
                    ->orWhere('cliente_documento', 'like', $busca);
            });
        }
        if ($request->filled('modelo')) {
            $query->where('modelo', $request->input('modelo'));
        }
        if ($request->filled('situacao')) {
            $query->where('situacao', $request->input('situacao'));
        }
        if ($request->input('vencimento_status') === 'vencido') {
            $query->whereNotNull('vencimento')->where('vencimento', '<', now()->toDateString());
        } elseif ($request->input('vencimento_status') === 'vence30') {
            $query->whereNotNull('vencimento')
                ->whereBetween('vencimento', [now()->toDateString(), now()->addDays(30)->toDateString()]);
        }

        $emissoes = $query->paginate(30)->withQueryString();

        // ─── Aba Vencimentos por cliente ─────────────────────────────────────
        $clientesQuery = Cliente::query()->orderBy('nome');
        if ($request->filled('busca_cliente')) {
            $clientesQuery->where('nome', 'like', '%'.$request->string('busca_cliente').'%');
        }
        $filtroVenc = $request->input('filtro_vencimento');
        if ($filtroVenc === 'vencido') {
            $clientesQuery->whereNotNull('vencimento_certificado')
                ->where('vencimento_certificado', '<', now()->toDateString());
        } elseif ($filtroVenc === 'vence30') {
            $clientesQuery->whereNotNull('vencimento_certificado')
                ->whereBetween('vencimento_certificado', [now()->toDateString(), now()->addDays(30)->toDateString()]);
        } elseif ($filtroVenc === 'sem') {
            $clientesQuery->whereNull('vencimento_certificado');
        }
        $clientes = $clientesQuery->paginate(25, ['id', 'nome', 'cpfcnpj', 'tipo', 'status', 'vencimento_certificado'], 'pagina_clientes')
            ->withQueryString();

        $totVencidos = Cliente::whereNotNull('vencimento_certificado')
            ->where('vencimento_certificado', '<', now()->toDateString())->count();
        $totVence30 = Cliente::whereNotNull('vencimento_certificado')
            ->whereBetween('vencimento_certificado', [now()->toDateString(), now()->addDays(30)->toDateString()])->count();

        return view('certificados.index', [
            'aba'         => $aba,
            'emissoes'    => $emissoes,
            'clientes'    => $clientes,
            'modelos'     => self::MODELOS,
            'formas'      => self::FORMAS,
            'situacoes'   => self::SITUACOES,
            'totVencidos' => $totVencidos,
            'totVence30'  => $totVence30,
        ]);
    }

    public function form(): View
    {
        return view('certificados.partials.form', [
            'emissao'   => null,
            'modelos'   => self::MODELOS,
            'formas'    => self::FORMAS,
            'situacoes' => self::SITUACOES,
        ]);
    }

    public function formEdit(CertificadoEmissao $emissao): View
    {
        $emissao->load('cliente');

        return view('certificados.partials.form', [
            'emissao'   => $emissao,
            'modelos'   => self::MODELOS,
            'formas'    => self::FORMAS,
            'situacoes' => self::SITUACOES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        CertificadoEmissao::create($data);

        return redirect()->route('certificados.index')->with('success', 'Emissão registrada com sucesso!');
    }

    public function update(Request $request, CertificadoEmissao $emissao): RedirectResponse
    {
        $data = $this->validated($request);

        $emissao->update($data);

        return redirect()->route('certificados.index')->with('success', 'Emissão atualizada com sucesso!');
    }

    public function destroy(CertificadoEmissao $emissao): RedirectResponse
    {
        $emissao->delete();

        return redirect()->route('certificados.index')->with('success', 'Emissão excluída com sucesso!');
    }

    public function updateVencimentoCliente(Request $request, Cliente $cliente): RedirectResponse
    {
        $validated = $request->validate([
            'vencimento_certificado' => ['nullable', 'date'],
        ]);

        $cliente->update(['vencimento_certificado' => $validated['vencimento_certificado'] ?? null]);

        return redirect()->route('certificados.index', ['aba' => 'vencimentos'])
            ->with('success', 'Vencimento do certificado atualizado!');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'data_emissao'      => ['required', 'date'],
            'cliente_id'        => ['nullable', 'exists:clientes,id'],
            'cliente_nome'      => ['required', 'string', 'max:255'],
            'cliente_documento' => ['nullable', 'string', 'max:30'],
            'modelo'            => ['required', 'in:'.implode(',', array_keys(self::MODELOS))],
            'numero_pedido'     => ['nullable', 'string', 'max:60'],
            'forma_emissao'     => ['required', 'in:'.implode(',', array_keys(self::FORMAS))],
            'valor'             => ['nullable', 'numeric', 'min:0'],
            'pagamento'         => ['nullable', 'string', 'max:40'],
            'situacao'          => ['required', 'string', 'max:40'],
            'certificadora'     => ['required', 'string', 'max:60'],
            'vencimento'        => ['nullable', 'date'],
            'observacao'        => ['nullable', 'string', 'max:1000'],
        ]);

        $data['cliente_id'] = $data['cliente_id'] ?? null;

        return $data;
    }
}
