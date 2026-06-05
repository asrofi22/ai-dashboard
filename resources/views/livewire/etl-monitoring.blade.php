<div class="space-y-6" x-data="{ showNotification: false, notificationMessage: '' }" x-on:pipeline-created.window="notificationMessage = $event.detail.message; showNotification = true; setTimeout(() => showNotification = false, 4000)" x-on:pipeline-deleted.window="notificationMessage = $event.detail.message; showNotification = true; setTimeout(() => showNotification = false, 4000)" x-on:job-fixed.window="notificationMessage = $event.detail.message; showNotification = true; setTimeout(() => showNotification = false, 4000)">
    
    <!-- AI Notification Toast -->
    <div x-show="showNotification" x-cloak x-transition class="fixed top-6 right-6 z-50 bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 border border-slate-700 dark:border-slate-200 text-xs font-semibold">
        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span x-text="notificationMessage"></span>
    </div>

    <!-- AI Pipeline Creator Action Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Perancang Pipeline ETL AI</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Gunakan bahasa natural untuk merancang schema dan menulis skrip pemrosesan data otomatis menggunakan Gemini.</p>
        </div>
        <button 
            wire:click="toggleCreateModal"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 shrink-0"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Pipeline dengan AI
        </button>
    </div>

    <!-- Top Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">ETL Success Rate</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $successRate }}%</span>
                <span class="text-xs font-semibold text-emerald-500">Target: >95%</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Failed Jobs</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold {{ $failedCount > 0 ? 'text-red-500' : 'text-slate-900 dark:text-white' }}">{{ $failedCount }}</span>
                <span class="text-xs text-slate-400">Memerlukan atensi AI</span>
            </div>
        </div>

        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Average Duration</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $avgDuration }}s</span>
                <span class="text-xs text-slate-400">Per eksekusi</span>
            </div>
        </div>
    </div>

    <!-- Main Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Pipelines List -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Pipeline Terdaftar
                </h3>
                <div class="space-y-3">
                    @foreach($pipelines as $pipe)
                        <div 
                            wire:click="selectPipeline({{ $pipe->id }})"
                            class="p-3 border rounded-xl space-y-3 cursor-pointer transition-all hover:border-indigo-500/50 {{ $selectedPipelineId === $pipe->id ? 'bg-indigo-50/40 border-indigo-500/60 dark:bg-indigo-950/10 dark:border-indigo-500/40 shadow-sm' : 'bg-slate-50 dark:bg-[#161A25]/50 border-slate-100 dark:border-slate-850' }}"
                        >
                            <div class="flex justify-between items-start">
                                <div class="truncate">
                                    <h4 class="font-bold text-xs text-slate-800 dark:text-slate-200 font-mono truncate flex items-center gap-1.5">
                                        @if($pipe->generated_script)
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500" title="Dirancang oleh AI"></span>
                                        @endif
                                        {{ $pipe->name }}
                                    </h4>
                                    <div class="flex items-center gap-2 mt-1 text-[10px] text-slate-500">
                                        <span class="truncate max-w-[90px]" title="{{ $pipe->source_layer }}">{{ $pipe->source_layer }}</span>
                                        <span>→</span>
                                        <span class="truncate max-w-[90px]" title="{{ $pipe->target_layer }}">{{ $pipe->target_layer }}</span>
                                    </div>
                                </div>
                                @if($pipe->generated_script)
                                    <button 
                                        onclick="event.stopPropagation()"
                                        wire:click="deletePipeline({{ $pipe->id }})"
                                        wire:confirm="Apakah Anda yakin ingin menghapus pipeline AI ini?"
                                        class="p-1 text-slate-400 hover:text-red-500 dark:hover:text-red-400 rounded transition-colors"
                                        title="Hapus Pipeline"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                @endif
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 px-2 py-0.5 rounded-full font-bold">
                                    {{ $pipe->frequency }}
                                </span>
                                <button 
                                    onclick="event.stopPropagation()"
                                    wire:click="triggerMockJob({{ $pipe->id }})"
                                    class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-bold transition-colors"
                                    {{ $isRunningJob ? 'disabled' : '' }}
                                >
                                    Jalankan Job
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Job Runs Table and AI Diagnostics Drawer -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Pipeline Details Panel -->
            @if($selectedPipeline)
                <div class="bg-white dark:bg-[#12151E] border border-indigo-100 dark:border-indigo-500/20 rounded-xl p-5 shadow-sm space-y-4 animate-in fade-in slide-in-from-top-4 duration-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 uppercase tracking-wider font-bold">
                                Detail Pipeline & Skrip ETL
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-base mt-2 font-mono">
                                {{ $selectedPipeline->name }}
                            </h3>
                        </div>
                        <button 
                            wire:click="selectPipeline(null)"
                            class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            &times; Tutup Detail
                        </button>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50/50 dark:bg-[#161A25]/50 p-3 rounded-lg border border-slate-100 dark:border-slate-800 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider mb-0.5">Source Layer</span>
                            <strong class="text-slate-700 dark:text-slate-300">{{ $selectedPipeline->source_layer }}</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider mb-0.5">Target Layer</span>
                            <strong class="text-slate-700 dark:text-slate-300">{{ $selectedPipeline->target_layer }}</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider mb-0.5">Schedule</span>
                            <strong class="text-slate-700 dark:text-slate-300">{{ $selectedPipeline->frequency }}</strong>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider mb-0.5">Status</span>
                            <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 font-bold text-[10px]">
                                {{ strtoupper($selectedPipeline->is_active) }}
                            </span>
                        </div>
                    </div>

                    @if($selectedPipeline->definition_prompt)
                        <div class="text-xs text-slate-600 dark:text-slate-400 p-3 bg-slate-50 dark:bg-[#161A25]/30 rounded-lg border border-slate-100 dark:border-slate-850">
                            <strong class="text-slate-700 dark:text-slate-300 block mb-0.5">Instruksi Rencana AI:</strong> 
                            <span class="italic font-medium">"{{ $selectedPipeline->definition_prompt }}"</span>
                        </div>
                    @endif

                    <!-- ETL Code Window -->
                    @if($selectedPipeline->generated_script)
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Skrip ETL Hasil Generasi AI:</span>
                            <div x-data="{ copied: false }" class="relative rounded-xl overflow-hidden border border-slate-850 dark:border-slate-900 bg-slate-950 shadow-lg">
                                <div class="flex justify-between items-center px-4 py-2 bg-slate-900 border-b border-slate-800 text-[10px] font-mono text-slate-500">
                                    <span>{{ $selectedPipeline->name }}.py</span>
                                    <button 
                                        type="button"
                                        @click="navigator.clipboard.writeText($refs.codeText.innerText); copied = true; setTimeout(() => copied = false, 2000)" 
                                        class="text-indigo-400 hover:text-indigo-300 transition-colors font-sans flex items-center gap-1 font-bold"
                                    >
                                        <span x-text="copied ? 'Tersalin!' : 'Salin Skrip'"></span>
                                    </button>
                                </div>
                                <pre class="p-4 font-mono text-xs overflow-x-auto text-indigo-200 max-h-72 select-all whitespace-pre"><code x-ref="codeText">{{ $selectedPipeline->generated_script }}</code></pre>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- AI Failure Analysis Drawer -->
            @if($selectedRun && $selectedRun->status === 'Failed')
                <div class="bg-red-50/30 dark:bg-red-500/5 border border-red-200 dark:border-red-500/20 rounded-xl p-5 shadow-sm space-y-4 animate-in fade-in slide-in-from-top-4 duration-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                AI Failure Analysis
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-base mt-2 font-mono">
                                {{ $selectedRun->pipeline->name }} (Run #{{ $selectedRun->id }})
                            </h3>
                        </div>
                        <button 
                            wire:click="$set('selectedRunId', null)"
                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm"
                        >
                            &times; Tutup
                        </button>
                    </div>

                    <!-- Error Log -->
                    <div class="p-3 bg-slate-950 text-red-400 font-mono text-xs rounded-lg border border-slate-900 overflow-x-auto select-all">
                        {{ $selectedRun->error_message }}
                    </div>

                    @if($selectedRun->ai_failure_analysis)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs mt-3">
                            <div class="space-y-3">
                                <div>
                                    <strong class="text-slate-800 dark:text-slate-300 block mb-0.5">Analisis Penyebab Utama (Root Cause):</strong>
                                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                                        {{ $selectedRun->ai_failure_analysis['root_cause'] }}
                                    </p>
                                </div>
                                <div>
                                    <strong class="text-slate-800 dark:text-slate-300 block mb-0.5">Dampak Bisnis:</strong>
                                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                                        {{ $selectedRun->ai_failure_analysis['impact'] }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <div>
                                    <strong class="text-slate-800 dark:text-slate-300 block mb-0.5">Kemungkinan Penyebab:</strong>
                                    <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-400">
                                        @foreach($selectedRun->ai_failure_analysis['possibilities'] as $pos)
                                            <li>{{ $pos }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div>
                                    <strong class="text-indigo-600 dark:text-indigo-400 block mb-0.5 font-bold">Rekomendasi Tindakan:</strong>
                                    <ul class="list-disc list-inside space-y-1 text-indigo-700 dark:text-indigo-300 font-medium">
                                        @foreach($selectedRun->ai_failure_analysis['recommendations'] as $rec)
                                            <li>{{ $rec }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-red-200/30 dark:border-red-500/20 flex justify-end">
                            <button 
                                wire:click="fixJob({{ $selectedRun->id }})"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                Terapkan Perbaikan AI (UPSERT)
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-3">
                            <button 
                                wire:click="analyzeFailure({{ $selectedRun->id }})"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors"
                                {{ $isAnalyzing ? 'disabled' : '' }}
                            >
                                <svg class="w-4 h-4 {{ $isAnalyzing ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                Jalankan Analisis Kegagalan AI
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Job Runs History List -->
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">
                        @if($selectedPipelineId)
                            Riwayat Eksekusi Job: {{ $selectedPipeline->name }}
                        @else
                            Riwayat Eksekusi Semua Job
                        @endif
                    </h3>
                    @if($selectedPipelineId)
                        <button 
                            wire:click="selectPipeline(null)"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        >
                            Tampilkan Semua
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735] text-xs">
                        <thead class="bg-slate-50 dark:bg-[#161A25]">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Job Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Start</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Duration</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Processed</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-[#222735] font-medium text-slate-700 dark:text-slate-300">
                            @php
                                $filteredRuns = $selectedPipelineId 
                                    ? $jobRuns->where('pipeline_id', $selectedPipelineId)
                                    : $jobRuns;
                            @endphp

                            @forelse($filteredRuns as $run)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold">{{ $run->pipeline->name }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $run->status === 'Success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400' }}">
                                            {{ $run->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 font-mono">{{ $run->start_time->format('H:i:s d-M') }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ $run->duration_seconds }}s</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($run->rows_processed) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($run->status === 'Failed')
                                            <button 
                                                wire:click="selectRun({{ $run->id }})"
                                                class="text-indigo-600 dark:text-indigo-400 hover:underline text-[10px] font-bold"
                                            >
                                                Analisis AI
                                            </button>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                                        Belum ada riwayat eksekusi untuk pipeline ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Pipeline Creation Modal overlay dialog -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-150 dark:border-[#222735] flex justify-between items-center">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        Rancang Pipeline ETL dengan AI
                    </h3>
                    <button wire:click="toggleCreateModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="generatePipelineWithAi" class="p-6 space-y-4">
                    @if (session()->has('generation_error'))
                        <div class="p-3 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-lg text-xs font-semibold">
                            {{ session('generation_error') }}
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Instruksi Desain Pipeline</label>
                        <textarea 
                            wire:model="newPipelinePrompt"
                            rows="4" 
                            class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-xl p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                            placeholder="Contoh: Ambil data dari Postgres ERP, filter data yang aktif, bersihkan email kosong, kemudian kirim ke Clickhouse Data Warehouse harian..."
                        ></textarea>
                        @error('newPipelinePrompt') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-[11px] text-slate-400 dark:text-slate-500 leading-relaxed bg-slate-50 dark:bg-[#161A25]/30 p-2.5 rounded-lg border border-slate-100 dark:border-slate-850">
                        💡 <strong>Tips Prompt:</strong> Beritahu AI asal data (source), tujuan data (target), pembersihan yang dilakukan, serta seberapa sering pipeline dijalankan (Hourly/Daily/Weekly).
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-150 dark:border-[#222735]">
                        <button 
                            type="button" 
                            wire:click="toggleCreateModal"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-[#1C212E] rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
                            {{ $isGeneratingPipeline ? 'disabled' : '' }}
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors disabled:opacity-50"
                            {{ $isGeneratingPipeline ? 'disabled' : '' }}
                        >
                            @if($isGeneratingPipeline)
                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                Sedang Merancang...
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Rancang Pipeline
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
