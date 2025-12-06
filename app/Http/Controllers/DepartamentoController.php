<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartamentoController extends Controller
{
    
    /**
     * Exibe a lista de todos os departamentos.
     */
    public function index()
    {
       
        $departamentos = Departamento::withCount(['users'])
                        ->orderBy('nome')
                        ->paginate(10);
                        
        return view('departamentos.index', compact('departamentos'));
    }

    /**
     * Mostra o formulário para criar um novo departamento.
     */
    public function create()
    {
        return view('departamentos.create');
    }

    /**
     * Salva o novo departamento no banco de dados.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nome' => 'required|string|max:100|unique:departamentos,nome',
        ]);

        departamento::create($validatedData);

        return redirect()->route('departamentos.index')->with('success', 'departamento criado com sucesso!');
    }

    /**
     * Mostra o formulário para editar um departamento.
     */
    public function edit(departamento $departamento)
    {
        return view('departamentos.edit', compact('departamento'));
    }

    /**
     * Atualiza o departamento no banco de dados.
     */
    public function update(Request $request, departamento $departamento)
    {
        $validatedData = $request->validate([
            'nome' => ['required', 'string', 'max:100', Rule::unique('departamentos')->ignore($departamento->id)],
        ]);

        $departamento->update($validatedData);

        return redirect()->route('departamentos.index')->with('success', 'departamento atualizado com sucesso!');
    }

    /**
     * Remove um departamento do banco de dados, com trava de segurança.
     */
    public function destroy(departamento $departamento)
    {
        if ($departamento->users()->count() > 0) {
            return redirect()->route('departamentos.index')
                             ->with('error', 'Não é possível excluir este departamento, pois ele está associado a usuários');
        }

        $departamento->delete();

        return redirect()->route('departamentos.index')->with('success', 'departamento excluído com sucesso!');
    }
}