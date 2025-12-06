<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departamento;
use App\Models\Categoria; 
use App\Models\Chamado;

class ChamadoLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ticket_can_be_created_assigned_and_closed(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        
        $departamento = Departamento::create(['nome' => 'Financeiro', 'local' => 'Prédio Principal']);
        
        $categoria = Categoria::create(['nome_amigavel' => 'Hardware', 'tipo_interno' => 'hardware']);

        $solicitante = User::factory()->create(['departamento_id' => $departamento->id]);
        $solicitante->assignRole('Usuário Comum');

        $tecnico = User::factory()->create(['departamento_id' => $departamento->id]);
        $tecnico->assignRole('Técnico de TI');

      
        $response = $this->actingAs($solicitante)->post(route('chamados.store'), [
            'titulo' => 'Impressora não funciona',
            'descricao_problema' => 'A impressora do nosso departamento não está a imprimir nada.',
            'local' => 'Sala 101',
            'prioridade' => 'Média',
            'departamento_id' => $departamento->id, 
            'categoria_id' => $categoria->id,       
        ]);
        
        $response->assertRedirect(route('chamados.index'));
        $this->assertDatabaseHas('chamados', ['titulo' => 'Impressora não funciona']);
        $chamado = Chamado::first(); 

        $response = $this->actingAs($tecnico)->patch(route('chamados.assign', $chamado));
        
        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'tecnico_id' => $tecnico->id,
        ]);

        $this->actingAs($tecnico)->patch(route('chamados.attend', $chamado));
        $response = $this->actingAs($tecnico)->patch(route('chamados.resolve', $chamado), [
            'solucao_final' => 'Cabo reconectado.',
            'servico_executado' => 'on',
        ]);

        $response->assertRedirect(route('chamados.show', $chamado));
        
        $response = $this->actingAs($solicitante)->patch(route('chamados.close', $chamado));
        
        $response->assertRedirect(route('chamados.show', $chamado));
        $this->assertDatabaseHas('chamados', [
            'id' => $chamado->id,
            'status' => \App\Enums\ChamadoStatus::FECHADO,
        ]);
    }
}