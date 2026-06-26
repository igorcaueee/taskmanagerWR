<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ProdutoFinanceiro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private function diretor(): Usuario
    {
        return Usuario::factory()->create(['cargo' => 'diretor']);
    }

    private function assistente(): Usuario
    {
        return Usuario::factory()->create(['cargo' => 'assistente']);
    }

    private function cliente(): Cliente
    {
        return Cliente::factory()->create();
    }

    // ─── Acesso ──────────────────────────────────────────────────────────────

    public function test_assistente_nao_acessa_financeiro(): void
    {
        $this->actingAs($this->assistente())
            ->get(route('financeiro.produtos.index'))
            ->assertForbidden();
    }

    public function test_diretor_acessa_lista(): void
    {
        $this->actingAs($this->diretor())
            ->get(route('financeiro.produtos.index'))
            ->assertOk();
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function test_criar_produto(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($this->diretor())
            ->post(route('financeiro.produtos.store'), [
                'cliente_id'    => $cliente->id,
                'nome'          => 'Produto Teste',
                'codigo'        => 'PT-001',
                'categoria'     => 'Serviços',
                'preco_custo'   => 10.00,
                'preco_venda'   => 25.00,
                'estoque_atual' => 100,
                'ativo'         => 1,
            ])
            ->assertRedirect(route('financeiro.produtos.index'));

        $this->assertDatabaseHas('produtos_financeiros', [
            'cliente_id' => $cliente->id,
            'nome'       => 'Produto Teste',
            'codigo'     => 'PT-001',
        ]);
    }

    public function test_criar_produto_sem_nome_falha(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($this->diretor())
            ->post(route('financeiro.produtos.store'), [
                'cliente_id' => $cliente->id,
                'nome'       => '',
            ])
            ->assertSessionHasErrors('nome');
    }

    public function test_atualizar_produto(): void
    {
        $produto = ProdutoFinanceiro::factory()->create([
            'cliente_id' => $this->cliente()->id,
            'nome'       => 'Original',
        ]);

        $this->actingAs($this->diretor())
            ->put(route('financeiro.produtos.update', $produto), [
                'cliente_id' => $produto->cliente_id,
                'nome'       => 'Atualizado',
                'ativo'      => 1,
            ])
            ->assertRedirect(route('financeiro.produtos.index'));

        $this->assertDatabaseHas('produtos_financeiros', ['id' => $produto->id, 'nome' => 'Atualizado']);
    }

    public function test_excluir_produto(): void
    {
        $produto = ProdutoFinanceiro::factory()->create([
            'cliente_id' => $this->cliente()->id,
        ]);

        $this->actingAs($this->diretor())
            ->delete(route('financeiro.produtos.destroy', $produto))
            ->assertRedirect(route('financeiro.produtos.index'));

        $this->assertDatabaseMissing('produtos_financeiros', ['id' => $produto->id]);
    }
}
