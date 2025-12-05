<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Chamado;
use App\Models\Problema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ChamadoLifecycleTest extends TestCase
{
    // A trait RefreshDatabase limpa a base de dados antes de cada teste, garantindo um ambiente limpo.
    use RefreshDatabase;

    /**
     * Testa o ciclo de vida completo de um chamado.
     */
    public function test_a_ticket_can_be_created_assigned_and_closed(): void
    {
        // --- 1. PREPARAÇÃO (Arrange) ---

        // Cria os papéis e permissões necessários para o teste
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        // Cria um departamento
        $departamento = Departamento::create(['nome' => 'Financeiro', 'local' => 'Prédio Principal']);

        $categoria = Categoria::create(['nome_amigavel' => 'Hardware', 'tipo_interno' => 'hardware']);
        
        // Cria um utilizador comum (solicitante)
        $solicitante = User::factory()->create(['departamento_id' => $departamento->id]);
        $solicitante->assignRole('Usuário Comum');

        // Cria um técnico
        $tecnico = User::factory()->create(['departamento_id' => $departamento->id]);
        $tecnico->assignRole('Técnico de TI');

        // --- 2. AÇÃO (Act) & VERIFICAÇÃO (Assert) ---

        // **Cenário 1: Solicitante cria um chamado**
        $response = $this->actingAs($solicitante)->post(route('chamados.store'), [
            'titulo' => 'Impressora não funciona',
            'descricao_problema' => 'A impressora do nosso departamento não está a imprimir nada.',
            'local' => 'Sala 101',
            'prioridade' => 'Média',
    'categoria_id' => 1, // Se for obrigatório
    'departamento_id' => 1,
        ]);
        
        $response->assertRedirect(route('chamados.index'));
        $this->assertDatabaseHas('chamados', ['titulo' => 'Impressora não funciona']);
        $chamado = Chamado::first(); // Pega o chamado que acabámos de criar

        // **Cenário 2: Técnico atribui o chamado a si mesmo**
        $response = $this->actingAs($tecnico)->patch(route('chamados.assign', $chamado));
        
        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'tecnico_id' => $tecnico->id,
        ]);

        // **Cenário 3: Técnico atende e resolve o chamado**
        $this->actingAs($tecnico)->patch(route('chamados.attend', $chamado));
        $response = $this->actingAs($tecnico)->patch(route('chamados.resolve', $chamado), [
            'solucao_final' => 'O cabo de rede da impressora estava desligado. Reconectado e testado.',
            'servico_executado' => 'on',
        ]);

        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => \App\Enums\ChamadoStatus::RESOLVIDO,
        ]);
        
        // **Cenário 4: Solicitante fecha o chamado**
        $response = $this->actingAs($solicitante)->patch(route('chamados.close', $chamado));
        
        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => \App\Enums\ChamadoStatus::FECHADO,
        ]);
    }
}