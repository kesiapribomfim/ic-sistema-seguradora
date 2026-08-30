<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
        <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white mb-4">
            Filtros do Relatório
        </h2>

        {{ $this->form }}
    </div>

    <div class="mt-8 space-y-8">

        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-clock class="w-6 h-6 text-warning-500" />
                    Apólices a Vencer (Próximos 30 dias)
                </h3>
                <span class="px-3 py-1 bg-warning-500/10 text-warning-600 rounded-full text-sm font-medium">
                    {{ $apolicesAVencer->count() }} contratos em risco
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nº Apólice</th>
                            <th scope="col" class="px-4 py-3">Segurado</th>
                            <th scope="col" class="px-4 py-3">Corretor</th>
                            <th scope="col" class="px-4 py-3">Vencimento</th>
                            <th scope="col" class="px-4 py-3">Prêmio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($apolicesAVencer as $apolice)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $apolice->numero_apolice }}</td>
                            <td class="px-4 py-3">
                                {{ $apolice->segurado?->tipo === 'PF' ? $apolice->segurado->seguradoPf?->nome : $apolice->segurado->seguradoPj?->razao_social }}
                            </td>
                            <td class="px-4 py-3">{{ $apolice->user?->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-warning-600 font-bold">
                                {{ $apolice->data_fim ? $apolice->data_fim->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3">R$ {{ number_format($apolice->valor_total, 2, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                Nenhuma apólice vencendo neste período com os filtros selecionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>