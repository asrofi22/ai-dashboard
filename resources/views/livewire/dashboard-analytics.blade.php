<div class="space-y-6">
    <!-- Header / Filter -->
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
            <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Data Real-time</span>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Total Projects -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Total Proyek</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ number_format($totalProjects) }}</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-emerald-600 dark:text-emerald-400">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <span class="font-medium">+12%</span>
                <span class="ml-2 text-slate-400 dark:text-slate-500 text-xs">dari bulan lalu</span>
            </div>
        </div>

        <!-- Total Candidates -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-amber-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Kandidat Duplikat</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ number_format($totalCandidates) }}</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-slate-500 dark:text-slate-400">
                <span class="font-medium">Membutuhkan peninjauan manual</span>
            </div>
        </div>

        <!-- High Confidence -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-red-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Risiko Tinggi (AI SAMA)</p>
                    <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-500 tracking-tight">{{ number_format($highConfidenceCount) }}</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-600 dark:text-red-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-red-600 dark:text-red-500 font-medium">
                Prioritas Utama Resolusi
            </div>
        </div>

        <!-- Duplicate Percentage -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-5 hover:shadow-md transition-shadow duration-300 relative overflow-hidden group">
            <div class="absolute right-0 top-0 h-full w-1 bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Tingkat Duplikasi</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $duplicatePercentage }}%</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                </div>
            </div>
            <div class="mt-4 w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ min($duplicatePercentage, 100) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Charts and Secondary Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Main Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Distribusi Kemiripan Data</h3>
                <button class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
                </button>
            </div>
            <div class="relative flex-1 min-h-[250px] w-full mt-2">
                <canvas id="similarityChart"></canvas>
            </div>
        </div>

        <!-- AI Insight Summary Widget -->
        <div class="lg:col-span-1 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-xl shadow-sm p-6 text-white flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-10 -top-10 opacity-10">
                <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <h3 class="text-base font-semibold">Insight AI</h3>
                </div>
                
                <p class="text-indigo-100 text-sm leading-relaxed mb-6">
                    Berdasarkan analisis semantik terbaru, sebagian besar duplikasi terjadi akibat perbedaan penulisan singkatan perusahaan (seperti "PT." vs "Perseroan Terbatas") dan format penamaan wilayah.
                </p>

                <div class="space-y-3">
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-medium text-indigo-200">Kualitas Data Master</span>
                            <span class="text-xs font-bold">Baik</span>
                        </div>
                        <div class="w-full bg-indigo-900/50 rounded-full h-1.5">
                            <div class="bg-emerald-400 h-1.5 rounded-full" style="width: 85%"></div>
                        </div>
                    </div>
                    
                    <button class="w-full py-2 bg-white text-indigo-700 rounded-lg text-sm font-semibold hover:bg-indigo-50 transition-colors shadow-sm" @click="document.querySelector('[x-data]').__x.$data.activeTab = 'analytics'">
                        Tanya Asisten AI
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let similarityChartInstance = null;

        function renderSimilarityChart() {
            const canvas = document.getElementById('similarityChart');
            if (!canvas) return;

            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#94a3b8' : '#64748b';
            const gridColor = isDarkMode ? '#1e293b' : '#f1f5f9';
            const ctx = canvas.getContext('2d');

            // Destroy previous chart instance to avoid "Canvas already in use" error
            if (similarityChartInstance) {
                similarityChartInstance.destroy();
                similarityChartInstance = null;
            }

            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, '#6366f1');
            gradient.addColorStop(1, '#818cf8');

            similarityChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! $distributionLabels !!},
                    datasets: [{
                        label: 'Jumlah Kandidat',
                        data: {!! $distributionData !!},
                        backgroundColor: gradient,
                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1e293b' : '#ffffff',
                            titleColor: isDarkMode ? '#f8fafc' : '#0f172a',
                            bodyColor: isDarkMode ? '#cbd5e1' : '#475569',
                            borderColor: isDarkMode ? '#334155' : '#e2e8f0',
                            borderWidth: 1,
                            padding: 12,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor, drawBorder: false },
                            border: { display: false },
                            ticks: { color: textColor, padding: 10, font: { family: 'Inter', size: 12 }, precision: 0 }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            border: { display: false },
                            ticks: { color: textColor, padding: 10, font: { family: 'Inter', size: 12 } }
                        }
                    },
                    interaction: { intersect: false, mode: 'index' },
                }
            });
        }

        // Initial render
        document.addEventListener('livewire:initialized', renderSimilarityChart);

        // Re-render after every Livewire update (catches import-completed refresh)
        document.addEventListener('livewire:update', renderSimilarityChart);

        // Re-render when theme is toggled manually
        window.addEventListener('theme-changed', renderSimilarityChart);
    </script>
</div>
