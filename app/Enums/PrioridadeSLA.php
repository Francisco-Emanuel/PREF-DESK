<?php

namespace App\Enums;

use Carbon\Carbon;

enum PrioridadeSLA: string
{
    case BAIXA = 'Baixa';
    case MEDIA = 'Média';
    case ALTA = 'Alta';
    case URGENTE = 'Urgente';

    /**
     * Retorna a data limite baseada na prioridade (excluindo fins de semana).
     */
    public function calcularPrazo(): Carbon
    {
        $diasUteis = match($this) {
            self::URGENTE => 1,
            self::ALTA => 3,
            self::MEDIA => 5,
            self::BAIXA => 10,
        };

        return now()->addWeekdays($diasUteis);
    }
    
}