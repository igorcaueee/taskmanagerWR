<?php

namespace App\Http\Controllers;

use App\Models\StRegraCest;
use App\Models\StRegraCestHistorico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Consulta e edição controlada das regras de ICMS-ST cadastradas
 * (st_regras_cest) — Fase 2 do módulo. Só visualizar/editar; criar uma
 * regra nova ou uma UF nova exige revisão completa da tabela oficial fora
 * da tela (decisão do prompt original), então não há "criar"/"apagar" aqui.
 */
class IcmsStRegraController extends Controller
{
    public function index(Request $request)
    {
        $uf = $request->string('uf')->toString() ?: null;

        $regras = StRegraCest::query()
            ->when($uf, fn ($q) => $q->where('uf', $uf))
            ->orderBy('uf')
            ->orderBy('segmento')
            ->orderBy('cest')
            ->get()
            ->groupBy('segmento');

        return view('icms-st.regras-index', compact('regras', 'uf'));
    }

    public function editar(StRegraCest $regra)
    {
        $historico = $regra->historico()->orderByDesc('editado_em')->limit(100)->get();

        return view('icms-st.regras-editar', compact('regra', 'historico'));
    }

    /** Campos que entram no log de histórico quando alterados (exclui os próprios campos de auditoria). */
    private const CAMPOS_AUDITADOS = [
        'segmento', 'descricao', 'mva_12_pct', 'mva_4_pct', 'mva_pct',
        'aliquota_interna_pct', 'adicional_tipo', 'adicional_pct',
        'adicional_confirmado', 'aliquota_confirmada', 'fonte_legal',
    ];

    public function atualizar(Request $request, StRegraCest $regra): RedirectResponse
    {
        $validated = $request->validate([
            'segmento' => 'required|string|max:255',
            'descricao' => 'required|string',
            'mva_12_pct' => 'nullable|numeric|min:0',
            'mva_4_pct' => 'nullable|numeric|min:0',
            'mva_pct' => 'nullable|numeric|min:0',
            // Nullable: uma regra "revogada" (ex.: Autopeças no RS) não tem alíquota
            // aplicável, porque não há mais ST -- forçar um valor aqui seria inventar.
            'aliquota_interna_pct' => 'nullable|numeric|min:0|max:100',
            'adicional_tipo' => 'required|in:nenhum,AMPARA_RS,FEM_MG',
            'adicional_pct' => 'nullable|numeric|min:0|max:100',
            'adicional_confirmado' => 'nullable|boolean',
            'aliquota_confirmada' => 'nullable|boolean',
            'fonte_legal' => 'nullable|string',
        ]);

        $validated['adicional_confirmado'] = $request->boolean('adicional_confirmado');
        $validated['aliquota_confirmada'] = $request->boolean('aliquota_confirmada');

        $editadoPor = auth()->user()?->name;
        $editadoEm = now();

        // Snapshot ANTES de aplicar as mudanças, pra registrar valor_anterior -> valor_novo
        // campo a campo (só os que realmente mudaram) -- nunca um dump silencioso.
        $valoresAntigos = $regra->only(self::CAMPOS_AUDITADOS);

        $validated['editado_por'] = $editadoPor;
        $validated['editado_em'] = $editadoEm;

        $regra->update($validated);

        foreach (self::CAMPOS_AUDITADOS as $campo) {
            $antigo = $valoresAntigos[$campo];
            $novo = $validated[$campo];

            // Booleans e decimais chegam como tipos diferentes do que saem do banco
            // (string "1"/"0" vs bool, "18.00" vs 18) -- compara como string pra não
            // logar "mudança" onde só o tipo PHP mudou.
            if ((string) $antigo === (string) $novo) {
                continue;
            }

            StRegraCestHistorico::create([
                'st_regra_cest_id' => $regra->id,
                'campo' => $campo,
                'valor_anterior' => $antigo === null ? null : (string) $antigo,
                'valor_novo' => $novo === null ? null : (string) $novo,
                'editado_por' => $editadoPor,
                'editado_em' => $editadoEm,
            ]);
        }

        return redirect()->route('icms-st.regras.index', ['uf' => $regra->uf])
            ->with('status', "Regra {$regra->uf}/{$regra->cest} atualizada. Vale a partir do próximo recálculo.");
    }
}
