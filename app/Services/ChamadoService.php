<?php

namespace App\Services;

use App\Enums\ChamadoStatus;
use App\Models\AtualizacaoChamado;
use App\Models\Chamado;
use App\Models\Problema;
use App\Models\User;
use App\Notifications\ChamadoAtribuidoNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class ChamadoService
{
    /**
     * Cria um novo chamado e o problema associado.
     */
    public function criarNovoChamado(array $validatedData): Chamado
    {
        $user = Auth::user();

        $problema = Problema::create([
            'descricao' => $validatedData['descricao_problema'],
            'autor_id' => $user->id,
        ]);

        $chamado = Chamado::create([
            'titulo' => $validatedData['titulo'],
            'descricao_inicial' => $validatedData['descricao_problema'],
            'problema_id' => $problema->id,
            'local' => $validatedData['local'],
            'departamento_id' => $validatedData['departamento_id'],
            'solicitante_id' => $user->hasAnyRole(['Admin', 'Supervisor', 'Técnico de TI']) ? $validatedData['solicitante_id'] : $user->id,
            'status' => ChamadoStatus::ABERTO,
            'prioridade' => $validatedData['prioridade'],
            'categoria_id' => $validatedData['categoria_id'] ?? null,
        ]);

        $this->criarLog($chamado, $user, 'Chamado aberto.');

        return $chamado;
    }

    /**
     * Atribui um chamado a um técnico ou escala para outro.
     */
    public function atribuirOuEscalar(Chamado $chamado, User $novoTecnico, User $autor): void
    {
        $logTexto = $chamado->tecnico_id
            ? "Chamado escalado de {$chamado->tecnico->name} para {$novoTecnico->name} por {$autor->name}."
            : "Chamado atribuído a {$novoTecnico->name} por {$autor->name}.";

        $chamado->tecnico_id = $novoTecnico->id;
        $chamado->save();

        try {
            $novoTecnico->notify(new ChamadoAtribuidoNotification($chamado, $autor));
        } catch (\Exception $e) {
            Log::error('ChamadoService: Falha ao enviar notificação de atribuição', [
                'chamado_id' => $chamado->id,
                'tecnico_id' => $novoTecnico->id,
                'erro' => $e->getMessage()
            ]);
        }

        $this->criarLog($chamado, $autor, $logTexto);
    }

    /**
     * Atribui o chamado ao próprio usuário logado.
     */
    public function atribuirParaSi(Chamado $chamado, User $tecnico): void
    {
        $chamado->tecnico_id = $tecnico->id;
        $chamado->save();

        $tecnico->notify(new ChamadoAtribuidoNotification($chamado, $tecnico));

        $this->criarLog($chamado, $tecnico, "Chamado atribuído a {$tecnico->name}.");
    }

    /**
     * Inicia o atendimento de um chamado.
     */
    public function atenderChamado(Chamado $chamado, User $tecnico): void
    {
        $chamado->status = ChamadoStatus::EM_ANDAMENTO;
        $this->startOrResetSla($chamado);
        $this->criarLog($chamado, $tecnico, "Chamado em atendimento por {$tecnico->name}. O SLA foi iniciado.");
    }

    /**
     * Atualiza o status de um chamado.
     */
    public function atualizarStatus(Chamado $chamado, ChamadoStatus $novoStatus, User $autor): void
    {
        $statusAntigo = $chamado->status->value;
        $chamado->status = $novoStatus;

        if ($novoStatus === ChamadoStatus::RESOLVIDO && is_null($chamado->data_resolucao)) {
            $chamado->data_resolucao = now();
        }
        if ($novoStatus === ChamadoStatus::FECHADO) {
            $chamado->data_fechamento = now();
            if (is_null($chamado->data_resolucao)) {
                $chamado->data_resolucao = now();
            }
        }
        $chamado->save();

        $this->criarLog($chamado, $autor, "Status do chamado alterado de '{$statusAntigo}' para '{$novoStatus->value}'.");
    }

    /**
     * Marca um chamado como Resolvido, coletando a solução e as assinaturas.
     */
    public function resolverChamado(Chamado $chamado, array $validatedData, User $autor): void
    {
        if (!$chamado->tecnico_id && $autor->hasRole('Admin')) {
            $chamado->tecnico_id = $autor->id;
            $this->criarLog($chamado, $autor, "Chamado automaticamente atribuído a {$autor->name} (Admin) ao ser resolvido.");
        }

        if (!empty($validatedData['assinatura_tecnico'])) {
            $chamado->assinatura_tecnico_path = $this->saveSignature($validatedData['assinatura_tecnico'], 'tecnico', $chamado->id);
        }

        if (!empty($validatedData['assinatura_solicitante'])) {
            $chamado->assinatura_solicitante_path = $this->saveSignature($validatedData['assinatura_solicitante'], 'solicitante', $chamado->id);
        }

        $chamado->solucao_final = $validatedData['solucao_final'];
        $chamado->status = ChamadoStatus::RESOLVIDO;
        $chamado->data_resolucao = now();
        $chamado->save();

        $this->criarLog($chamado, $autor, "Chamado marcado como Resolvido por {$autor->name}.");
    }

    /**
     * Marca um chamado como Fechado.
     */
    public function fecharChamado(Chamado $chamado, User $autor): void
    {
        $chamado->status = ChamadoStatus::FECHADO;
        $chamado->data_fechamento = now();
        $chamado->save();
        $this->criarLog($chamado, $autor, 'Chamado fechado pelo solicitante.');
    }

    /**
     * Reabre um chamado que já foi resolvido.
     */
    public function reabrirChamado(Chamado $chamado, string $motivo, User $autor): void
    {
        $chamado->status = ChamadoStatus::ABERTO;
        $chamado->tecnico_id = null;
        $chamado->solucao_final = null;
        $chamado->data_resolucao = null;
        $chamado->data_fechamento = null;
        $chamado->assinatura_tecnico_path = null;
        $chamado->assinatura_solicitante_path = null;
        $chamado->save();

        $this->criarLog($chamado, $autor, "Chamado reaberto. Motivo: " . $motivo);
    }

    /**
     * Cria um registro de log no histórico do chamado.
     */
    public function criarLog(Chamado $chamado, User $autor, string $texto, bool $isSystemLog = true): void
    {
        AtualizacaoChamado::create([
            'chamado_id' => $chamado->id,
            'autor_id' => $autor->id,
            'texto' => $texto,
            'is_system_log' => $isSystemLog,
        ]);
    }

    /**
     * Inicia ou reseta o SLA para um chamado.
     */
    private function startOrResetSla(Chamado $chamado): void
    {
        $now = Carbon::now();
        $chamado->data_inicio_sla = now();
        $chamado->prazo_sla = $chamado->prioridade->calcularPrazo();
        $chamado->save();
    }

    /**
     * Salva a imagem da assinatura em base64 como um arquivo.
     */
    private function saveSignature(string $base64Data, string $type, int $chamadoId): string
    {
        $image_parts = explode(";base64,", $base64Data);
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = "assinatura_{$type}_{$chamadoId}_" . uniqid() . '.png';
        $path = 'assinaturas/' . $fileName;
        Storage::disk('local')->put($path, $image_base64);
        return $path;
    }
}