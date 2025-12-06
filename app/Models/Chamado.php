<?php

namespace App\Models;

use App\Enums\ChamadoStatus;
use App\Enums\PrioridadeSLA;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Chamado extends Model
{
    use HasFactory;

    /**
     * O nome da tabela. Laravel provavelmente adivinharia 'chamados' corretamente,
     * mas é uma boa prática especificar.
     */
    protected $table = 'chamados';

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'status' => ChamadoStatus::class,
        'prioridade' => PrioridadeSLA::class,
        'data_resolucao' => 'datetime',
        'data_fechamento' => 'datetime',
        'prazo_sla' => 'datetime',
        'data_inicio_sla' => 'datetime',
    ];

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'titulo',
        'descricao_inicial',
        'problema_id',
        'local',
        'status',
        'prioridade',
        'prazo_sla',
        'data_inicio_sla',
        'solucao_final',
        'data_resolucao',
        'data_fechamento',
        'avaliacao',
        'solicitante_id',
        'tecnico_id',
        'categoria_id',
        'departamento_id',
        'assinatura_tecnico_path',       
        'assinatura_solicitante_path',
    ];

    /**
     * Escopo para filtrar e ordenar a lista principal de chamados.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFiltroPrincipal(Builder $query): Builder
    {
        $user = Auth::user();

        $query->where('status', '!=', ChamadoStatus::FECHADO);

        $query->when(! $user->hasRole('Admin'), function (Builder $subQuery) use ($user) {
            $subQuery->where(function (Builder $groupQuery) use ($user) {
                $groupQuery->where('status', '!=', ChamadoStatus::RESOLVIDO)
                    ->orWhere('solicitante_id', '=', $user->id);
            });
        });

        return $query
            ->orderByRaw("
                CASE
                    WHEN status = 'Aberto' THEN 1
                    WHEN status = 'Em Andamento' THEN 2
                    WHEN status = 'Resolvido' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Define o relacionamento: Um Chamado PERTENCE A um Problema.
     */
    public function problema(): BelongsTo
    {
        return $this->belongsTo(Problema::class);
    }

    /**
     * Define o relacionamento: Um Chamado TEM UM solicitante (que é um Usuário).
     */
    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    /**
     * Define o relacionamento: Um Chamado TEM UM técnico responsável (que é um Usuário).
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Define o relacionamento: Um Chamado PERTENCE A uma Categoria.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Define o relacionamento: Um Chamado TEM MUITAS atualizações (o histórico).
     */
    public function atualizacoes(): HasMany
    {
        return $this->hasMany(AtualizacaoChamado::class, 'chamado_id')->latest();
    }

    /**
     * Relacionamento departamento - chamado
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }
}
