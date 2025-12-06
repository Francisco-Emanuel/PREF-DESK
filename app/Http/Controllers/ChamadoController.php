<?php

namespace App\Http\Controllers;

use App\Enums\ChamadoStatus;
use App\Http\Requests\Chamado\AddUpdateRequest;
use App\Http\Requests\Chamado\AtribuirChamadoRequest;
use App\Http\Requests\Chamado\ReopenChamadoRequest;
use App\Http\Requests\Chamado\ResolveChamadoRequest;
use App\Http\Requests\Chamado\StoreChamadoRequest;
use App\Http\Requests\Chamado\UpdateStatusRequest;
use App\Models\Categoria;
use App\Models\Chamado;
use App\Models\Departamento;
use App\Models\User;
use App\Services\ChamadoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChamadoController extends Controller
{
    public function __construct(protected ChamadoService $chamadoService)
    {
    }

    public function index()
    {
        $this->authorize('view-chamados');
        $chamados = Chamado::with(['solicitante'])->filtroPrincipal()->paginate(15);
        $tecnicosDisponiveis = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Técnico de TI', 'Supervisor', 'Admin', 'Estagiário']))->orderBy('name')->get();
        return view('chamados.index', compact('chamados', 'tecnicosDisponiveis'));
    }

    public function create()
    {
        $this->authorize('create-chamados');
        $categorias = Categoria::orderBy('nome_amigavel')->get();
        $departamentos = Departamento::orderBy('nome')->get();
        $solicitantes = User::orderBy('name')->get();
        return view('chamados.create', compact( 'categorias', 'solicitantes', 'departamentos'));
    }

    public function show(Chamado $chamado)
    {
        $this->authorize('view-chamados');
        $chamado->load([ 'solicitante', 'tecnico', 'categoria', 'atualizacoes.autor']);
        $historyLogs = $chamado->atualizacoes()->where('is_system_log', true)->get();
        $tecnicosDisponiveis = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Técnico de TI', 'Supervisor', 'Admin', 'Estagiário']))->orderBy('name')->get();
        return view('chamados.show', compact('chamado', 'historyLogs', 'tecnicosDisponiveis'));
    }

    public function store(StoreChamadoRequest $request)
    {
        $this->chamadoService->criarNovoChamado($request->validated());
        return redirect()->route('chamados.index')->with('success', 'Chamado aberto com sucesso!');
    }

    public function addUpdate(AddUpdateRequest $request, Chamado $chamado)
    {
        $this->chamadoService->criarLog($chamado, Auth::user(), 'OBS: ' . $request->validated()['texto'], false); 
        return redirect()->route('chamados.show', $chamado)->with('success', 'Sua atualização foi adicionada!');
    }

    public function updateStatus(UpdateStatusRequest $request, Chamado $chamado)
    {
        $novoStatus = ChamadoStatus::from($request->validated()['status']);
        $this->chamadoService->atualizarStatus($chamado, $novoStatus, Auth::user());
        return redirect()->route('chamados.show', $chamado)->with('success', 'Status do chamado atualizado!');
    }

    public function assignToSelf(Chamado $chamado)
    {
        $this->authorize('edit-chamados');
        if ($chamado->tecnico_id) {
            return back()->with('error', 'Este chamado já foi atribuído.');
        }
        $this->chamadoService->atribuirParaSi($chamado, Auth::user());
        return redirect()->route('chamados.show', $chamado)->with('success', 'Chamado atribuído a você!');
    }

    public function atribuir(AtribuirChamadoRequest $request, Chamado $chamado)
    {
        $novoTecnico = User::find($request->validated()['new_tecnico_id']);
        $this->chamadoService->atribuirOuEscalar($chamado, $novoTecnico, Auth::user());
        return redirect()->route('chamados.index')->with('success', "Chamado atribuído a {$novoTecnico->name}!");
    }

    public function attend(Chamado $chamado)
    {
        $this->authorize('edit-chamados'); 
        if ($chamado->tecnico_id !== Auth::id()) {
            return back()->with('error', 'Você não é o técnico responsável por este chamado.');
        }
        if ($chamado->status !== ChamadoStatus::ABERTO && $chamado->status !== ChamadoStatus::EM_ANDAMENTO) {
            return back()->with('error', 'Este chamado não pode ser atendido no status atual.');
        }
        $this->chamadoService->atenderChamado($chamado, Auth::user());
        return redirect()->route('chamados.show', $chamado)->with('success', 'Chamado em atendimento!');
    }

    public function resolve(ResolveChamadoRequest $request, Chamado $chamado)
    {
        $this->chamadoService->resolverChamado($chamado, $request->validated(), Auth::user());
        return redirect()->route('chamados.show', $chamado)->with('success', 'Chamado resolvido com sucesso!');
    }

    public function close(Chamado $chamado)
    {
        $this->authorize('close-chamados'); 
        $this->chamadoService->fecharChamado($chamado, Auth::user());
        return redirect()->route('chamados.show', $chamado)->with('success', 'Chamado fechado com sucesso!');
    }

    public function reopen(ReopenChamadoRequest $request, Chamado $chamado)
    {
        $this->chamadoService->reabrirChamado($chamado, $request->validated()['motivo_reabertura'], Auth::user());
        return redirect()->route('chamados.show', $chamado)->with('success', 'Chamado reaberto!');
    }

    public function myChamados()
    {
        $this->authorize('view-chamados');
        $meusChamados = Chamado::with([ 'solicitante'])
            ->where('tecnico_id', Auth::id())
            ->filtroPrincipal()
            ->paginate(15);
        return view('chamados.my-chamados', ['chamados' => $meusChamados]);
    }

    public function closedIndex()
    {
        $this->authorize('view-chamados');
        $chamadosFechados = Chamado::with([ 'solicitante', 'tecnico'])
            ->where('status', ChamadoStatus::FECHADO)
            ->latest('data_fechamento')
            ->paginate(15);
        return view('chamados.closed', ['chamados' => $chamadosFechados]);
    }

    public function generateReport(Request $request, Chamado $chamado)
    {
        $this->authorize('view-chamados');
        $chamado->load(['problema.ativo', 'solicitante', 'tecnico', 'categoria', 'atualizacoes.autor', 'departamento']);
        $historyLogs = $chamado->atualizacoes()->where('is_system_log', true)->get();
        $comHistorico = $request->query('historico', '1') === '1';

        $pdf = Pdf::loadView('chamados.report', compact('chamado', 'historyLogs', 'comHistorico'));
        return $pdf->download("Ordem-Servico-{$chamado->id}.pdf");
    }

    public function getUserDetails(User $user)
    {
        $this->authorize('create-chamados');
        $user->load('departamento');
        return response()->json([
            'departamento_nome' => $user->departamento->nome ?? 'Não definido',
            'departamento_local' => $user->departamento->local ?? '',
        ]);
    }
}