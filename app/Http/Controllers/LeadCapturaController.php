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
    public function showForm(): View
    {
        return view('funil.captura');
    }

    public function store(StoreLeadCapturaRequest $request): RedirectResponse
    {
        $primeiraEtapa = EtapaFunil::orderBy('ordem')->first();

        if (is_null($primeiraEtapa)) {
            return Redirect::back()->with('error', 'O funil ainda não está configurado. Por favor, entre em contato diretamente.');
        }

        Lead::create([
            'nome' => $request->input('nome'),
            'email' => $request->input('email'),
            'telefone' => $request->input('telefone'),
            'empresa' => $request->input('empresa'),
            'possibilidade' => $request->input('possibilidade'),
            'etapa_funil_id' => $primeiraEtapa->id,
            'origem' => 'formulario',
        ]);

        return Redirect::route('funil.captura')->with('success', 'Obrigado! Sua mensagem foi recebida. Em breve entraremos em contato.');
    }
}
