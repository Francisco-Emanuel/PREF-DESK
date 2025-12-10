<?php

namespace App\Console\Commands;

use App\Enums\PrioridadeSLA;
use Illuminate\Console\Command;
use App\Models\Chamado;
use App\Models\AtualizacaoChamado;
use App\Enums\ChamadoStatus;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckSlaBreaches extends Command
{
    protected $signature = 'app:check-sla-breaches';
    protected $description = 'Verifica chamados com SLA estourado e escala a prioridade para Urgente.';

    public function handle()
    {
        Log::info('CheckSlaBreaches: Iniciando verificação de SLAs...');
        $this->info('Verificando chamados com SLA estourado...');
        try {
            $this->info('Verificando chamados com SLA estourado...');

            $breachedChamados = Chamado::whereNotIn('status', [ChamadoStatus::RESOLVIDO, ChamadoStatus::FECHADO])
                ->whereNotNull('prazo_sla')
                ->where('prazo_sla', '<', now())
                ->where('prioridade', '!=', 'Urgente')
                ->get();

            if ($breachedChamados->isEmpty()) {
                Log::info('CheckSlaBreaches: Nenhum chamado violado encontrado.');
                return;
            }

            foreach ($breachedChamados as $chamado) {
                Log::warning('CheckSlaBreaches: SLA Violado detectado', [
                    'chamado_id' => $chamado->id,
                    'prazo_original' => $chamado->prazo_sla,
                    'atraso_em_horas' => $chamado->prazo_sla->diffInHours(now())
                ]);

                $prazoAntigo = $chamado->prazo_sla;
                $horasAtraso = $prazoAntigo->diffInHours(now());

                $chamado->prioridade = PrioridadeSLA::URGENTE;

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
            Log::info("CheckSlaBreaches: Finalizado com sucesso. " . count($breachedChamados) . " chamados processados.");
            $this->info('Verificação concluída.');
        } catch (\Exception $e) {
            Log::error('CheckSlaBreaches: Falha crítica na execução', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Ocorreu um erro ao verificar SLAs. Verifique os logs.');
        }
    }
}