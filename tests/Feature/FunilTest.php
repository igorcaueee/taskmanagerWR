<?php

namespace Tests\Feature;

use App\Models\EtapaFunil;
use App\Models\Lead;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunilTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): Usuario
    {
        return Usuario::factory()->create([
            'cargo' => 'diretor',
        ]);
    }

    private function etapa(int $ordem = 1, string $nome = 'Prospecção/Lead'): EtapaFunil
    {
        return EtapaFunil::create([
            'nome' => $nome,
            'ordem' => $ordem,
            'cor' => '#3B82F6',
        ]);
    }

    public function test_kanban_requires_authentication(): void
    {
        $response = $this->get(route('funil'));

        $response->assertRedirect();
    }

    public function test_kanban_loads_for_authenticated_user(): void
    {
        $usuario = $this->usuario();
        $etapa = $this->etapa();
        Lead::factory()->create(['etapa_funil_id' => $etapa->id]);

        $response = $this->actingAs($usuario, 'web')->get(route('funil'));

        $response->assertStatus(200);
        $response->assertViewHas('etapas');
        $response->assertViewHas('leads');
    }

    public function test_create_lead_authenticated(): void
    {
        $usuario = $this->usuario();
        $etapa = $this->etapa();

        $response = $this->actingAs($usuario, 'web')->post(route('leads.save'), [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
            'empresa' => 'Empresa ABC',
            'etapa_funil_id' => $etapa->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'origem' => 'manual',
        ]);
    }

    public function test_update_etapa_creates_historico_funil(): void
    {
        $usuario = $this->usuario();
        $etapa1 = $this->etapa(1, 'Prospecção/Lead');
        $etapa2 = $this->etapa(2, 'Qualificação');
        $lead = Lead::factory()->create(['etapa_funil_id' => $etapa1->id]);

        $response = $this->actingAs($usuario, 'web')->patchJson(route('leads.update.etapa', $lead->id), [
            'etapa_funil_id' => $etapa2->id,
            'descricao' => 'Ligação realizada com sucesso.',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('historico_funil', [
            'lead_id' => $lead->id,
            'etapa_anterior_id' => $etapa1->id,
            'etapa_nova_id' => $etapa2->id,
            'descricao' => 'Ligação realizada com sucesso.',
        ]);
        $this->assertEquals($etapa2->id, $lead->fresh()->etapa_funil_id);
    }

    public function test_public_capture_form_is_accessible(): void
    {
        $response = $this->get(route('funil.captura'));

        $response->assertStatus(200);
    }

    public function test_public_capture_store_creates_lead_with_origem_formulario(): void
    {
        $this->etapa();

        $response = $this->post(route('funil.captura.store'), [
            'nome' => 'Maria Souza',
            'email' => 'maria@example.com',
            'telefone' => '11988888888',
            'empresa' => 'Empresa XYZ',
            'possibilidade' => 'Interesse em contratação',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'nome' => 'Maria Souza',
            'origem' => 'formulario',
        ]);
    }

    public function test_kanban_forbidden_for_non_diretor(): void
    {
        $usuario = Usuario::factory()->create(['cargo' => 'supervisor']);

        $response = $this->actingAs($usuario, 'web')->get(route('funil'));

        $response->assertForbidden();
    }

    public function test_create_lead_forbidden_for_non_diretor(): void
    {
        $usuario = Usuario::factory()->create(['cargo' => 'analista']);
        $etapa = $this->etapa();

        $response = $this->actingAs($usuario, 'web')->post(route('leads.save'), [
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
            'empresa' => 'Empresa ABC',
            'etapa_funil_id' => $etapa->id,
        ]);

        $response->assertForbidden();
    }

    public function test_delete_lead_forbidden_for_non_diretor(): void
    {
        $usuario = Usuario::factory()->create(['cargo' => 'assistente']);
        $etapa = $this->etapa();
        $lead = Lead::factory()->create(['etapa_funil_id' => $etapa->id]);

        $response = $this->actingAs($usuario, 'web')->delete(route('leads.delete', $lead->id));

        $response->assertForbidden();
    }

    public function test_delete_lead(): void
    {
        $usuario = $this->usuario();
        $etapa = $this->etapa();
        $lead = Lead::factory()->create(['etapa_funil_id' => $etapa->id]);

        $response = $this->actingAs($usuario, 'web')->delete(route('leads.delete', $lead->id));

        $response->assertRedirect(route('funil'));
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_converter_lead_para_cliente(): void
    {
        $usuario = $this->usuario();
        $etapa = $this->etapa();
        $lead = Lead::factory()->create([
            'etapa_funil_id' => $etapa->id,
            'empresa' => 'Empresa Teste',
        ]);

        $response = $this->actingAs($usuario, 'web')->postJson(route('leads.converter', $lead->id), [
            'nome' => 'Empresa Teste',
            'cpfcnpj' => '12.345.678/0001-99',
            'tipo' => '1',
            'regime_tributario' => 'Simples Nacional',
            'faturamento' => 50000,
            'honorario' => 1500,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotNull($lead->fresh()->convertido_cliente_id);
    }
}
