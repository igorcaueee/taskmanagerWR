@extends('layouts.internal')

@section('title', 'Agenda — WR Assessoria')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-calendar-days"></i> Agenda</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Visualize tarefas e compromissos do mês.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Google Calendar integration --}}
            @if (auth()->user()->isGoogleConnected())
                <form method="POST" action="{{ route('google.calendar.sync') }}">
                    @csrf
                    <button type="submit"
                            title="Sincronizar com Google Calendar"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none">
                        <img src="https://www.gstatic.com/images/branding/product/1x/calendar_2020q4_16dp.png"
                             alt="Google Calendar" class="w-4 h-4">
                        Sincronizar
                    </button>
                </form>
                <form method="POST" action="{{ route('google.calendar.disconnect') }}">
                    @csrf
                    <button type="submit"
                            title="Desconectar Google Calendar"
                            onclick="return confirm('Desconectar o Google Calendar?')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-700 border border-red-200 dark:border-red-700 text-red-600 dark:text-red-400 rounded text-sm hover:bg-red-50 dark:hover:bg-red-900/30 focus:outline-none">
                        <i class="fa-brands fa-google text-xs"></i> Desconectar
                    </button>
                </form>
            @else
                <a href="{{ route('google.calendar.redirect') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600 no-underline focus:outline-none">
                    <img src="https://www.gstatic.com/images/branding/product/1x/calendar_2020q4_16dp.png"
                         alt="Google Calendar" class="w-4 h-4">
                    Conectar Google Calendar
                </a>
            @endif

            <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm"
                    data-modal-url="{{ route('agenda.compromisso.form') }}">
                <i class="fa-solid fa-plus"></i> Novo Compromisso
            </button>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-green-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Month navigation --}}
    <div class="flex items-center justify-between mb-4 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl px-4 py-3 shadow-sm">
        <a href="{{ route('agenda', ['mes' => $mesAnterior->month, 'ano' => $mesAnterior->year]) }}"
           class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-brand no-underline group">
            <i class="fa-solid fa-chevron-left group-hover:-translate-x-0.5 transition-transform"></i>
            <span class="hidden sm:inline text-xs">{{ ucfirst($mesAnterior->translatedFormat('F Y')) }}</span>
        </a>

        <span class="text-base font-bold text-gray-900 dark:text-slate-100 capitalize">
            {{ ucfirst($primeiroDia->translatedFormat('F Y')) }}
        </span>

        <a href="{{ route('agenda', ['mes' => $proximoMes->month, 'ano' => $proximoMes->year]) }}"
           class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-brand no-underline group">
            <span class="hidden sm:inline text-xs">{{ ucfirst($proximoMes->translatedFormat('F Y')) }}</span>
            <i class="fa-solid fa-chevron-right group-hover:translate-x-0.5 transition-transform"></i>
        </a>
    </div>

    {{-- Legend --}}
    <div class="flex items-center gap-4 mb-4 text-xs text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-full bg-gray-400 inline-block"></span> Compromisso
        </span>
        <span class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm bg-gray-400 inline-block"></span> Tarefa
        </span>
    </div>

    {{-- Calendar grid --}}
    <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
        {{-- Day headers --}}
        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-slate-700">
            @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $cabecalho)
                <div class="py-2 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    {{ $cabecalho }}
                </div>
            @endforeach
        </div>

        {{-- Weeks --}}
        @foreach ($semanas as $semana)
            <div class="grid grid-cols-7 border-b border-gray-100 dark:border-slate-700 last:border-b-0">
                @foreach ($semana as $dia)
                    @php
                        $chave = $dia->format('Y-m-d');
                        $ehMesAtual = $dia->month === $primeiroDia->month;
                        $ehHoje = $dia->isToday();
                        $tarefasDoDia = $tarefasPorDia[$chave] ?? collect();
                        $compromissosDoDia = $compromissosPorDia[$chave] ?? collect();
                        $totalEventos = $tarefasDoDia->count() + $compromissosDoDia->count();
                    @endphp
                    <div class="min-h-[90px] p-1.5 border-r border-gray-100 dark:border-slate-700 last:border-r-0
                                {{ $ehMesAtual ? 'bg-white dark:bg-slate-800' : 'bg-gray-50 dark:bg-slate-900' }}">

                        {{-- Day number --}}
                        <div class="flex justify-end mb-1">
                            <span class="w-6 h-6 flex items-center justify-center text-xs font-medium rounded-full
                                         {{ $ehHoje ? 'bg-brand text-white' : ($ehMesAtual ? 'text-gray-700' : 'text-gray-300') }}">
                                {{ $dia->day }}
                            </span>
                        </div>

                        {{-- Events --}}
                        <div class="flex flex-wrap gap-1">
                            {{-- Compromissos: círculo preenchido --}}
                            @foreach ($compromissosDoDia->take(4) as $compromisso)
                                <button type="button"
                                        title="Compromisso: {{ $compromisso->titulo }}"
                                        class="w-4 h-4 rounded-full flex-shrink-0 hover:scale-125 transition-transform focus:outline-none ring-1 ring-white/60"
                                        style="background-color: {{ $compromisso->cor }};"
                                        data-modal-url="{{ route('agenda.compromisso.detalhe', $compromisso->id) }}">
                                </button>
                            @endforeach

                            {{-- Tarefas: quadrado arredondado --}}
                            @foreach ($tarefasDoDia->take(4) as $tarefa)
                                <button type="button"
                                        title="Tarefa: {{ $tarefa->titulo }}"
                                        class="w-4 h-4 rounded-b-none flex-shrink-0 hover:scale-125 transition-transform focus:outline-none ring-1 ring-white/60"
                                        style="background-color: {{ $tarefa->etapa->cor ?? '#9ca3af' }};"
                                        data-modal-url="{{ route('agenda.tarefa.detalhe', $tarefa->id) }}">
                                </button>
                            @endforeach

                            {{-- Overflow indicator --}}
                            @if ($totalEventos > 4)
                                <span class="text-[10px] text-gray-400 leading-none self-end">
                                    +{{ $totalEventos - 4 }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endsection
