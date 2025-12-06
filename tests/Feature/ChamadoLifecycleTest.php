<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Categoria; // <--- Importante: Importar o Model
use App\Models\Chamado;

class ChamadoLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ticket_can_be_created_assigned_and_closed(): void
    {
<<<<<<< HEAD
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
=======
        // --- 1. PREPARAÇÃO ---
        // Cria permissões
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        // Cria dados essenciais
>>>>>>> 9727f02baddf885204460d76892af0226823ae0a
        $departamento = Departamento::create(['nome' => 'Financeiro', 'local' => 'Prédio Principal']);
        
        // CORREÇÃO: Cria a Categoria dinamicamente para o teste
        $categoria = Categoria::create(['nome_amigavel' => 'Hardware', 'tipo_interno' => 'hardware']);

<<<<<<< HEAD
=======
        // Cria usuários
>>>>>>> 9727f02baddf885204460d76892af0226823ae0a
        $solicitante = User::factory()->create(['departamento_id' => $departamento->id]);
        $solicitante->assignRole('Usuário Comum');

        $tecnico = User::factory()->create(['departamento_id' => $departamento->id]);
        $tecnico->assignRole('Técnico de TI');

<<<<<<< HEAD
=======
        // --- 2. AÇÃO & VERIFICAÇÃO ---

        // Cenário 1: Solicitante cria um chamado
>>>>>>> 9727f02baddf885204460d76892af0226823ae0a
        $response = $this->actingAs($solicitante)->post(route('chamados.store'), [
            'titulo' => 'Impressora não funciona',
            'descricao_problema' => 'A impressora do nosso departamento não está a imprimir nada.',
            'local' => 'Sala 101',
            'prioridade' => 'Média',
            'departamento_id' => $departamento->id, // <--- Usa o ID real criado
            'categoria_id' => $categoria->id,       // <--- Usa o ID real criado
        ]);
        
        $response->assertRedirect(route('chamados.index'));
        $this->assertDatabaseHas('chamados', ['titulo' => 'Impressora não funciona']);
<<<<<<< HEAD
        $chamado = Chamado::first(); 

=======
        
        $chamado = Chamado::first();

        // Cenário 2: Técnico atribui o chamado a si mesmo
>>>>>>> 9727f02baddf885204460d76892af0226823ae0a
        $response = $this->actingAs($tecnico)->patch(route('chamados.assign', $chamado));
        
        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'tecnico_id' => $tecnico->id,
        ]);

<<<<<<< HEAD
=======
        // Cenário 3: Técnico atende e resolve
>>>>>>> 9727f02baddf885204460d76892af0226823ae0a
        $this->actingAs($tecnico)->patch(route('chamados.attend', $chamado));
        $response = $this->actingAs($tecnico)->patch(route('chamados.resolve', $chamado), [
            'solucao_final' => 'Cabo reconectado.',
            'servico_executado' => 'on',
        ]);

        $response->assertRedirect(route('chamados.show', $chamado));
        
<<<<<<< HEAD
=======
        // Cenário 4: Fechamento
>>>>>>> 9727f02baddf885204460d76892af0226823ae0a
        $response = $this->actingAs($solicitante)->patch(route('chamados.close', $chamado));
        
        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => \App\Enums\ChamadoStatus::FECHADO,
        ]);
    }
}