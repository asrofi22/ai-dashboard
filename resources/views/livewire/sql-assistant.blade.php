<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" 
     x-data="{
         chartInstance: null,
         initChart(type, dataStr) {
             if (this.chartInstance) {
                 this.chartInstance.destroy();
             }
             const canvas = document.getElementById('sqlAssistantChart');
             if (!canvas) return;
             
             const ctx = canvas.getContext('2d');
             const dataPoints = JSON.parse(dataStr);
             const labels = dataPoints.map(d => d.label);
             const values = dataPoints.map(d => d.value);

             this.chartInstance = new Chart(ctx, {
                 type: type === 'none' ? 'bar' : type,
                 data: {
                     labels: labels,
                     datasets: [{
                         label: 'Hasil Analitik Kueri',
                         data: values,
                         backgroundColor: 'rgba(99, 102, 241, 0.4)',
                         borderColor: 'rgba(99, 102, 241, 1)',
                         borderWidth: 2,
                         borderRadius: 6,
                         tension: 0.3
                     }]
                 },
                 options: {
                     responsive: true,
                     maintainAspectRatio: false,
                     plugins: {
                         legend: {
                             labels: { color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#334155' }
                         }
                     },
                     scales: {
                         y: {
                             beginAtZero: true,
                             grid: { color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                             ticks: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' }
                         },
                         x: {
                             grid: { display: false },
                             ticks: { color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' }
                         }
                     }
                 }
             });
         }
     }"
     @render-sql-chart.window="initChart($event.detail.type, $event.detail.data)"
>
    <!-- Left Chat Panel -->
    <div class="lg:col-span-1 bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl shadow-sm flex flex-col h-[calc(100vh-12rem)]">
        <div class="p-4 border-b border-slate-200 dark:border-[#222735] bg-slate-50/50 dark:bg-[#161A25]/50">
            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                AI SQL Assistant Chat
            </h3>
        </div>
        
        <!-- Chat History -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
            @foreach($history as $msg)
                <div class="flex gap-3 {{ $msg['role'] === 'user' ? 'justify-end' : '' }}">
                    @if($msg['role'] !== 'user')
                        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white shrink-0 text-xs font-bold shadow-sm">AI</div>
                    @endif
                    <div class="max-w-[85%] rounded-xl p-3 text-sm {{ $msg['role'] === 'user' ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-[#161A25] text-slate-700 dark:text-slate-300' }}">
                        @if($msg['role'] === 'user')
                            <p class="leading-relaxed">{{ $msg['content'] }}</p>
                        @else
                            <!-- Simple Markdown parser for SQL and Bold -->
                            <div class="space-y-2 leading-relaxed">
                                {!! nl2br(preg_replace(
                                    '/```sql(.*?)```/s', 
                                    '<pre class="bg-slate-950 text-emerald-400 p-3 rounded-lg overflow-x-auto text-xs font-mono my-2 select-all">$1</pre>', 
                                    e($msg['content'])
                                )) !!}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Chat Input -->
        <form wire:submit.prevent="submitChat" class="p-3 border-t border-slate-200 dark:border-[#222735] flex gap-2">
            <input 
                type="text" 
                wire:model="query" 
                placeholder="Ketik pertanyaan data..." 
                class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-800 rounded-lg text-sm bg-transparent text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold flex items-center justify-center shrink-0">
                Kirim
            </button>
        </form>
    </div>

    <!-- Right Workspace Panel -->
    <div class="lg:col-span-2 space-y-6">
        <!-- SQL Editor and Controls -->
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm space-y-4">
            <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                Generated SQL Editor
            </h3>
            
            <textarea 
                wire:model.defer="generatedSql" 
                rows="6"
                placeholder="Ketik atau edit query SQL di sini..."
                class="w-full p-4 bg-slate-950 text-emerald-400 font-mono text-xs rounded-xl border border-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 select-all"
            ></textarea>

            <div class="flex flex-wrap gap-2">
                <button 
                    wire:click="executeSql"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Execute SQL
                </button>
                <button 
                    wire:click="explainSql"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Explain Query
                </button>
                @if(!empty($queryResult['rows']))
                    <button 
                        wire:click="exportCsv"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors ml-auto"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export CSV
                    </button>
                @endif
            </div>

            <!-- Error Banner -->
            @if($errorMessage)
                <div class="p-4 bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20 rounded-xl text-red-600 dark:text-red-400 text-xs font-medium leading-relaxed flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>{{ $errorMessage }}</span>
                </div>
            @endif

            <!-- AI Explanation Panel -->
            @if($explanation)
                <div class="p-4 bg-indigo-50/50 border border-indigo-100 dark:bg-indigo-500/5 dark:border-indigo-500/20 rounded-xl text-indigo-700 dark:text-indigo-300 text-xs leading-relaxed flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364.364l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    <div>
                        <strong class="block text-indigo-800 dark:text-indigo-400 mb-0.5">Penjelasan AI:</strong>
                        <span>{{ $explanation }}</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Query Output and Chart -->
        @if(!empty($queryResult['rows']))
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm space-y-6">
                <!-- Tabs to switch result tables or charts -->
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3" x-data="{ outputTab: 'table' }">
                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Output Hasil Kueri</h4>
                    <div class="flex bg-slate-100 dark:bg-slate-800 p-0.5 rounded-lg text-xs font-semibold">
                        <button 
                            @click="outputTab = 'table'; $dispatch('switch-tab', 'table')"
                            class="px-3 py-1.5 rounded-md transition-colors"
                            :class="outputTab === 'table' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        >
                            Tabel
                        </button>
                        @if($chartType !== 'none')
                            <button 
                                @click="outputTab = 'chart'; $dispatch('switch-tab', 'chart')"
                                class="px-3 py-1.5 rounded-md transition-colors"
                                :class="outputTab === 'chart' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            >
                                Grafik
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Outputs Panel Container -->
                <div x-data="{ currentTab: 'table' }" x-on:switch-tab.window="currentTab = $event.detail">
                    <!-- Tab: Table -->
                    <div x-show="currentTab === 'table'" class="overflow-x-auto max-h-96">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735] text-sm">
                            <thead class="bg-slate-50 dark:bg-[#161A25] sticky top-0">
                                <tr>
                                    @foreach($queryResult['headers'] as $header)
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-[#222735] font-mono text-xs">
                                @foreach($queryResult['rows'] as $row)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                        @foreach($queryResult['headers'] as $header)
                                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $row[$header] ?? 'null' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab: Chart -->
                    @if($chartType !== 'none')
                        <div x-show="currentTab === 'chart'" class="h-80 w-full" x-init="$nextTick(() => initChart('{{ $chartType }}', '{{ $chartData }}'))">
                            <canvas id="sqlAssistantChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
