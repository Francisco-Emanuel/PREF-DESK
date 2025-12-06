<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chamado;
use App\Models\AtualizacaoChamado;
use App\Enums\ChamadoStatus;
use Carbon\Carbon;

class CheckSlaBreaches extends Command
{
    protected $signature = 'app:check-sla-breaches';
    protected $description = 'Verifica chamados com SLA estourado e escala a prioridade para Urgente.';

    public function handle()
    {
        $this->info('Verificando chamados com SLA estourado...');

        $breachedChamados = Chamado::whereNotIn('status', [ChamadoStatus::RESOLVIDO, ChamadoStatus::FECHADO])
                                ->whereNotNull('prazo_sla')
                                ->where('prazo_sla', '<', now())
                                ->where('prioridade', '!=', 'Urgente')
                                ->get();

        foreach ($breachedChamados as $chamado) {
            $prazoAntigo = $chamado->prazo_sla;
            $horasAtraso = $prazoAntigo->diffInHours(now());

            $chamado->prioridade = 'Urgente';

            $now = Carbon::now();
            $chamado->data_inicio_sla = $now;
            $chamado->prazo_sla = (clone $now)->addWeekdays(1); 
            $chamado->save();

            $logTexto = "SLA violado! O chamado estava atrasado em {$horasAtraso} horas. ";
            $logTexto .= "A prioridade foi elevada para Urgente e um novo prazo de resolução foi definido.";

            AtualizacaoChamado::create([
                'chamado_id' => $chamado->id,
                'autor_id' => 1, 
                'texto' => $logTexto,
                'is_system_log' => true,
            ]);

            $this->warn("Chamado #{$chamado->id} teve a prioridade elevada para Urgente.");
        }

        $this->info('Verificação concluída.');
    }
}