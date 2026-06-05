<div class="space-y-6">
    <!-- Top Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Success Rate</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $successRate }}%</span>
                <span class="text-xs font-semibold text-emerald-500">Target: >95%</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Failed Executions</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold {{ $failedCount > 0 ? 'text-red-500' : 'text-slate-900 dark:text-white' }}">{{ $failedCount }}</span>
                <span class="text-xs text-slate-400">Total kegagalan</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Rows Loaded</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ number_format($totalRowsWritten) }}</span>
                <span class="text-[10px] text-red-500 font-bold">Rejected: {{ number_format($totalRowsRejected) }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Rata-rata Durasi</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $avgDuration }}s</span>
                <span class="text-xs text-slate-400">Per running job</span>
            </div>
        </div>
    </div>

    <!-- Charts and Runs Ingestion list -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Interactive Chart.js Area (Left Side) -->
        <div class="lg:col-span-2 space-y-6">
            <div 
                x-data="{
                    labels: {{ $labels }},
                    read: {{ $rowsRead }},
                    written: {{ $rowsWritten }},
                    rejected: {{ $rowsRejected }},
                    chart: null,
                    init() {
                        setTimeout(() => {
                            const ctx = document.getElementById('studioIngestionChart').getContext('2d');
                            this.chart = new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: this.labels,
                                    datasets: [
                                        {
                                            label: 'Rows Read',
                                            data: this.read,
                                            backgroundColor: 'rgba(79, 70, 229, 0.75)',
                                            borderColor: '#4f46e5',
                                            borderWidth: 1
                                        },
                                        {
                                            label: 'Rows Written',
                                            data: this.written,
                                            backgroundColor: 'rgba(16, 185, 129, 0.75)',
                                            borderColor: '#10b981',
                                            borderWidth: 1
                                        },
                                        {
                                            label: 'Rows Rejected',
                                            data: this.rejected,
                                            backgroundColor: 'rgba(239, 68, 68, 0.75)',
                                            borderColor: '#ef4444',
                                            borderWidth: 1
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            labels: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#475569', font: { family: 'Inter', size: 10 } }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            grid: { display: false },
                                            ticks: { color: document.documentElement.classList.contains('dark') ? '#64748b' : '#94a3b8', font: { family: 'Inter', size: 9 } }
                                        },
                                        y: {
                                            grid: { color: document.documentElement.classList.contains('dark') ? '#222735' : '#e2e8f0' },
                                            ticks: { color: document.documentElement.classList.contains('dark') ? '#64748b' : '#94a3b8', font: { family: 'Inter', size: 9 } }
                                        }
                                    }
                                }
                            });
                        }, 400);

                        window.addEventListener('theme-changed', () => {
                            if (this.chart) {
                                const isDark = document.documentElement.classList.contains('dark');
                                this.chart.options.plugins.legend.labels.color = isDark ? '#94a3b8' : '#475569';
                                this.chart.options.scales.x.ticks.color = isDark ? '#64748b' : '#94a3b8';
                                this.chart.options.scales.y.grid.color = isDark ? '#222735' : '#e2e8f0';
                                this.chart.options.scales.y.ticks.color = isDark ? '#64748b' : '#94a3b8';
                                this.chart.update();
                            }
                        });
                    }
                }"
                class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm h-[380px] relative"
            >
                <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-4">Volume Data yang Diproses (10 Eksekusi Terakhir)</h3>
                <div class="h-72 w-full">
                    <canvas id="studioIngestionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Latest Executions & Detail Drawers (Right Side) -->
        <div class="lg:col-span-1 space-y-4">
            @if($selectedRun)
                <!-- Run Detail Panel -->
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4 animate-in fade-in slide-in-from-right-4 duration-200 text-xs">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 uppercase tracking-wider font-mono">
                                Ringkasan Eksekusi Detail
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-sm mt-2 font-mono">
                                {{ $selectedRun->pipeline->name }} (Run #{{ $selectedRun->id }})
                            </h3>
                        </div>
                        <button wire:click="selectRun(null)" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                    </div>

                    <!-- Details grid -->
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 p-3 rounded-lg font-mono text-[10px]">
                        <div><span class="text-slate-400">Status:</span> <span class="font-bold uppercase {{ $selectedRun->status === 'Success' ? 'text-emerald-500' : 'text-red-500' }}">{{ $selectedRun->status }}</span></div>
                        <div><span class="text-slate-400">Durasi:</span> <span class="font-bold text-slate-700 dark:text-slate-300">{{ $selectedRun->duration_seconds }}s</span></div>
                        <div><span class="text-slate-400">Rows Read:</span> <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($selectedRun->rows_read) }}</span></div>
                        <div><span class="text-slate-400">Rows Written:</span> <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($selectedRun->rows_written) }}</span></div>
                    </div>

                    <!-- Logs terminal box -->
                    <div class="space-y-1">
                        <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Terminal Ingestion Logs:</span>
                        <pre class="bg-black p-3 rounded-lg font-mono text-[9px] text-indigo-300 overflow-y-auto max-h-48 border border-slate-900 text-left select-all whitespace-pre-wrap leading-relaxed shadow-inner" style="font-family: Consolas, Monaco, monospace;">{{ $selectedRun->execution_logs }}</pre>
                    </div>

                    @if($selectedRun->status === 'Failed' && $selectedRun->ai_failure_analysis)
                        <!-- failure diagnostics preview -->
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-1">
                            <span class="text-[9px] text-red-500 font-bold uppercase tracking-wider block">Diagnosis Masalah AI:</span>
                            <div class="p-2.5 bg-red-50/30 dark:bg-red-500/5 border border-red-100 dark:border-red-950/20 rounded-lg text-slate-600 dark:text-slate-400 leading-normal text-[10px]">
                                <strong class="text-slate-800 dark:text-slate-300 block mb-0.5">Penyebab:</strong>
                                {{ $selectedRun->ai_failure_analysis['root_cause'] }}
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <!-- Runs List Summary -->
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">Eksekusi Terbaru</h3>
                    <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                        @forelse($runs->take(8) as $run)
                            <div 
                                wire:click="selectRun({{ $run['id'] }})"
                                class="p-2.5 bg-slate-50 dark:bg-[#161A25]/50 hover:bg-indigo-50/20 dark:hover:bg-indigo-950/5 border border-slate-100 dark:border-slate-850 rounded-lg flex justify-between items-center cursor-pointer transition-colors"
                            >
                                <div class="overflow-hidden pr-2">
                                    <strong class="text-[10px] text-slate-800 dark:text-slate-200 font-mono truncate block" title="{{ $run['pipeline']['name'] }}">{{ $run['pipeline']['name'] }}</strong>
                                    <span class="text-[9px] text-slate-400 font-mono block mt-0.5">{{ date('H:i:s d M', strtotime($run['start_time'])) }}</span>
                                </div>
                                <span class="text-[8px] font-bold px-1.5 py-0.5 rounded font-mono uppercase {{ $run['status'] === 'Success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400' }}">
                                    {{ $run['status'] }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-xs text-slate-400 py-6">
                                Belum ada riwayat job.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
