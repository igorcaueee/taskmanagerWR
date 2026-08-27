<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadCapturaRequest;
use App\Models\EtapaFunil;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class LeadCapturaController extends Controller
{
    /**
     * Origens de lead permitidas a partir de formulários públicos.
     */
    private const ORIGENS_PERMITIDAS = ['formulario', 'lp-troca-contador'];

    public function showForm(): View
    {
        return view('funil.captura');
    }

    public function landingTrocaContador(): View
    {
        return view('funil.lp-troca-contador');
    }

    public function store(StoreLeadCapturaRequest $request): RedirectResponse
    {
        $primeiraEtapa = EtapaFunil::orderBy('ordem')->first();

        if (is_null($primeiraEtapa)) {
            return Redirect::back()->with('error', 'O funil ainda não está configurado. Por favor, entre em contato diretamente.');
        }

        $origem = $request->input('origem', 'formulario');

        if (! in_array($origem, self::ORIGENS_PERMITIDAS, true)) {
            $origem = 'formulario';
        }

        Lead::create([
            'nome' => $request->input('nome'),
            'email' => $request->input('email'),
            'telefone' => $request->input('telefone'),
            'empresa' => $request->input('empresa'),
            'mensagem' => $request->input('mensagem'),
            'etapa_funil_id' => $primeiraEtapa->id,
            'origem' => $origem,
        ]);

        return Redirect::back()->with('success', 'Obrigado! Sua mensagem foi recebida. Em breve entraremos em contato.');
    }
}
