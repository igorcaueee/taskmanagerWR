@extends('layouts.internal')

@section('title', 'Painel — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Colaboradores</h1>
                <p class="text-gray-700">Aqui você pode visualizar e gerenciar seus colaboradores.</p>
            </div>
            <div>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" data-bs-toggle="modal" data-bs-target="#modalCreateColab">
                    <i class="fa-solid fa-user-plus"></i>
                </button>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cargo</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($colaboradores as $colab)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $colab->nome }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $colab->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $colab->cargo }}</td>
                            <td class="px-6 py-4 text-sm text-right">
                                <!-- placeholder actions -->
                                <a href="#" class="text-blue-600 hover:underline"><i class="fa-solid fa-pencil"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum colaborador encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal (Bootstrap) -->
    <div class="modal fade" id="modalCreateColab" tabindex="-1" aria-labelledby="modalCreateColabLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('colaboradores.save') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCreateColabLabel">Novo Colaborador</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nome</label>
                            <input name="nome" type="text" class="mt-1 block w-full border rounded px-3 py-2" value="{{ old('nome') }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">E-mail</label>
                            <input name="email" type="email" class="mt-1 block w-full border rounded px-3 py-2" value="{{ old('email') }}" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Senha</label>
                            <input name="senha" type="password" class="mt-1 block w-full border rounded px-3 py-2" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cargo</label>
                            <select name="cargo" class="mt-1 block w-full border rounded px-3 py-2" required>
                                <option value="diretor">Diretor</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="analista">Analista</option>
                                <option value="assistente">Assistente</option>
                                <option value="auxiliar">Auxiliar</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection