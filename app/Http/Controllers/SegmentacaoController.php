<?php

namespace App\Http\Controllers;

use App\Models\Segmentacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SegmentacaoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $validator = Validator::make($request->only('nome'), [
            'nome' => ['required', 'string', 'max:255', 'unique:segmentacoes,nome'],
        ], [
            'nome.required' => 'O nome da segmentação é obrigatório.',
            'nome.unique' => 'Já existe uma segmentação com este nome.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $segmentacao = Segmentacao::create(['nome' => $request->string('nome')]);

        return response()->json(['id' => $segmentacao->id, 'nome' => $segmentacao->nome]);
    }
}
