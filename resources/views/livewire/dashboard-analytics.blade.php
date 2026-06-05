<div class="space-y-6"
     x-data="{
         dqChartInstance: null,
         etlChartInstance: null,
         initDashboardCharts() {
             // 1. Data Quality Trend Chart
             const dqCanvas = document.getElementById('dqTrendChart');
             if (dqCanvas) {
                 const ctx = dqCanvas.getContext('2d');
                 if (this.dqChartInstance) this.dqChartInstance.destroy();
                 
                 const isDark = document.documentElement.classList.contains('dark');
                 
                 this.dqChartInstance = new Chart(ctx, {
                     type: 'line',
                     data: {
                         labels: {{ $dqTrendLabels }},
                         datasets: [{
                             label: 'Data Quality Score (%)',
                             data: {{ $dqTrendData }},
                             borderColor: '#6366f1',
                             backgroundColor: 'rgba(99, 102, 241, 0.1)',
                             borderWidth: 3,
                             fill: true,
                             tension: 0.3,
                             pointBackgroundColor: '#6366f1'
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: { display: false }
                         },
                         scales: {
                             y: {
                                 min: 80,
                                 max: 100,
                                 grid: { color: isDark ? '#1e293b' : '#f1f5f9' },
                                 ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                             },
                             x: {
                                 grid: { display: false },
                                 ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                             }
                         }
                     }
                 });
             }

             // 2. ETL Success Rate Doughnut
             const etlCanvas = document.getElementById('etlSuccessChart');
             if (etlCanvas) {
                 const ctx = etlCanvas.getContext('2d');
                 if (this.etlChartInstance) this.etlChartInstance.destroy();
                 
                 this.etlChartInstance = new Chart(ctx, {
                     type: 'doughnut',
                     data: {
                         labels: ['Success', 'Failed', 'Warning'],
                         datasets: [{
                             data: {{ $etlSuccessData }},
                             backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                             borderWidth: 0
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         plugins: {
                             legend: {
                                 position: 'bottom',
                                 labels: {
                                     boxWidth: 12,
                                     color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#334155'
                                 }
                             }
                         },
                         cutout: '70%'
                     }
                 });
             }
         }
     }"
     x-init="$nextTick(() => { initDashboardCharts(); });"
     @theme-changed.window="initDashboardCharts()"
     x-on:livewire:update="initDashboardCharts()"
>
    <!-- Cakupan Analisis / Filter -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cakupan Analisis</h3>
            <div class="mt-1.5 relative min-w-[240px]">
                <select wire:model.live="batchId" class="appearance-none w-full bg-white dark:bg-[#1C212E] border border-slate-200 dark:border-[#2A303F] text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 pr-10 outline-none transition-all cursor-pointer shadow-sm">
                    <option value="">Semua Batch Impor</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}">
                            Batch #{{ $batch->id }} ({{ $batch->created_at->format('d M H:i') }}) — {{ $batch->sourceConnection->name }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">AI Data Platform Active Monitoring</span>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Tables -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Tables</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($totalTables) }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-slate-400">
                <span>Data Warehouse Catalog</span>
            </div>
        </div>

        <!-- Total Records -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Records</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($totalRecords) }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-slate-400">
                <span>DWH + Raw Imported rows</span>
            </div>
        </div>

        <!-- Data Quality Score -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Data Quality Score</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $dqScore }}%</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <span class="text-xs font-black">{{ $dqScore }}</span>
                </div>
            </div>
            <div class="mt-4 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $dqScore }}%"></div>
            </div>
        </div>

        <!-- Active / Failed Pipelines -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-red-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">ETL Pipelines</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ $activePipelines }} <span class="text-xs font-medium text-slate-400">Aktif</span>
                        @if($failedPipelines > 0)
                            <span class="text-xs text-red-500 font-bold ml-1">/ {{ $failedPipelines }} Gagal</span>
                        @endif
                    </p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-600 dark:text-red-400">
                    <svg class="w-5 h-5 animate-spin" style="animation-duration: 4s;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-slate-400">
                <span>Monitoring Status Pekerjaan</span>
            </div>
        </div>
    </div>

    <!-- Secondary Indicator row (Missing, Duplicates, AI Insights) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Missing Values</span>
                <span class="block text-sm font-bold text-slate-700 dark:text-slate-300">{{ number_format($missingRecords) }} Records</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Duplicate Records</span>
                <span class="block text-sm font-bold text-slate-700 dark:text-slate-300">{{ number_format($duplicateRecords) }} Pasang</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-4 flex items-center gap-3 font-semibold bg-indigo-50/20 dark:bg-indigo-500/5">
            <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
            </div>
            <div>
                <span class="text-[10px] text-indigo-400 uppercase font-bold tracking-wider">AI Insights Generated</span>
                <span class="block text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($aiInsightsGenerated) }} Insights</span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart: DQ Trend -->
        <div class="lg:col-span-2 bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-6 flex flex-col h-[350px]">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Tren Skor Kualitas Data</h3>
            <div class="relative flex-1 w-full h-full">
                <canvas id="dqTrendChart"></canvas>
            </div>
        </div>

        <!-- Chart: ETL Success Doughnut -->
        <div class="lg:col-span-1 bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-6 flex flex-col h-[350px]">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Rasio Keberhasilan ETL</h3>
            <div class="relative flex-1 w-full h-full flex items-center justify-center">
                <canvas id="etlSuccessChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Issues and Insights Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Data Quality Issues Table -->
        <div class="lg:col-span-2 bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Isu Kualitas Data Tertinggi</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735] text-xs">
                    <thead class="bg-slate-50 dark:bg-[#161A25]">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tabel</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipe Masalah</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Ringkasan</th>
                            <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dampak Skor</th>
                            <th class="px-4 py-3 text-center font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-[#222735] font-medium text-slate-700 dark:text-slate-300">
                        @foreach($topIssues as $issue)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                <td class="px-4 py-3 font-mono font-bold">{{ $issue['table_name'] }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $issue['finding_type'] }}</td>
                                <td class="px-4 py-3 max-w-[200px] truncate" title="{{ $issue['finding_summary'] }}">{{ $issue['finding_summary'] }}</td>
                                <td class="px-4 py-3 text-right text-red-500 font-bold">-{{ $issue['quality_score_impact'] }}%</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $issue['priority_level'] === 'High' ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                        {{ $issue['priority_level'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent AI Insights Feed -->
        <div class="lg:col-span-1 bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-900 dark:text-white text-sm">Feed Analisis AI Terkini</h3>
            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                @foreach($recentInsights as $insight)
                    <div class="p-3 bg-slate-50/50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-xl space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] uppercase tracking-wider font-bold text-indigo-500">{{ $insight['type'] }}</span>
                            <span class="text-[9px] text-slate-400">{{ $insight['time'] }}</span>
                        </div>
                        <h4 class="font-bold text-xs text-slate-800 dark:text-slate-200 leading-snug">{{ $insight['title'] }}</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ $insight['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
