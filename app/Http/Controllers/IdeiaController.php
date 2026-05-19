<?php

namespace App\Http\Controllers;

use App\Models\Ideia;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IdeiaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ideia::with('colaborador')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('colaborador_id')) {
            $query->where('colaborador_id', $request->integer('colaborador_id'));
        }

        $ideias = $query->paginate(20)->withQueryString();
        $colaboradores = Usuario::orderBy('nome')->get();

        return view('ideias.home', compact('ideias', 'colaboradores'));
    }

    public function form(): View
    {
        return view('ideias.partials.formIdeia');
    }

    public function formEdit(int $id): View
    {
        $ideia = Ideia::findOrFail($id);

        return view('ideias.partials.editStatus', compact('ideia'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'descricao' => ['required', 'string', 'max:2000'],
            'status' => ['required', 'in:pendente,em_analise,aprovada,concluida,descartada'],
        ]);

        $validated['colaborador_id'] = Auth::id();

        Ideia::create($validated);

        return redirect()->route('ideias.index')->with('success', 'Ideia cadastrada com sucesso!');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $ideia = Ideia::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:pendente,em_analise,aprovada,concluida,descartada'],
            'data_conclusao' => ['nullable', 'date'],
        ]);

        $ideia->update($validated);

        return redirect()->route('ideias.index')->with('success', 'Status atualizado com sucesso!');
    }

    public function destroy(int $id): RedirectResponse
    {
        Ideia::findOrFail($id)->delete();

        return redirect()->route('ideias.index')->with('success', 'Ideia excluída com sucesso!');
    }
}
