<div class="space-y-6" @if($this->hasRunningJobs()) wire:poll.2s="loadData" @else wire:poll.15s="loadData" @endif x-data="{ 
    isRunning: false, 
    progress: 0, 
    logs: '', 
    rowsRead: 0, 
    rowsWritten: 0, 
    rowsRejected: 0,
    showNotification: false,
    notificationMessage: '',
    init() {
        window.addEventListener('start-execution-simulation', e => {
            const data = e.detail[0];
            this.isRunning = true;
            this.progress = 0;
            this.logs = 'INFO - [' + new Date().toLocaleTimeString() + '] Memulai inisialisasi eksekusi ETL...\n';
            this.rowsRead = 0;
            this.rowsWritten = 0;
            this.rowsRejected = 0;
            
            let step = 0;
            const willSucceed = data.willSucceed;
            const steps = [];
            
            // stepMetricsList keeps track of metrics for each step to write to the DB
            let stepMetricsList = [];
            
            steps.push({
                desc: 'INFO - [' + new Date().toLocaleTimeString() + '] Menghubungkan ke data source: ' + data.sourceDriver.toUpperCase() + '...',
                delay: 600,
                action: () => {
                    stepMetricsList.push({
                        step: 'Table Input',
                        read: 0,
                        written: 0,
                        rejected: 0,
                        status: 'Running'
                    });
                }
            });
            
            steps.push({
                desc: 'INFO - [' + new Date().toLocaleTimeString() + '] Koneksi berhasil. Membaca data dari: ' + data.sourceTable + '...',
                delay: 700,
                action: () => {
                    this.rowsRead = Math.floor(Math.random() * 2500) + 500;
                    let idx = stepMetricsList.findIndex(sm => sm.step === 'Table Input');
                    if (idx !== -1) {
                        stepMetricsList[idx].read = this.rowsRead;
                        stepMetricsList[idx].written = this.rowsRead;
                        stepMetricsList[idx].status = 'Success';
                    }
                }
            });

            // Transformations
            if (data.transformations.length > 0) {
                data.transformations.forEach(t => {
                    let desc = 'INFO - [' + new Date().toLocaleTimeString() + '] Menerapkan transformasi: \'' + t + '\'...';
                    
                    if (t === 'Value Mapper') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Value Mapper] Memetakan nilai diskrit kustom (contoh: M/F -> Male/Female)...';
                    } else if (t === 'String Operations') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho String Operations] Membersihkan spasi penutup, substring, dan padding kolom...';
                    } else if (t === 'Mathematical Calculator') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Calculator] Membersihkan data dengan rumus matematika...';
                    } else if (t === 'Sort Rows') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Sort Rows] Mengurutkan data berdasarkan kunci index utama...';
                    } else if (t === 'Group By') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Group By] Melakukan agregasi data kelompok (mengurangi jumlah baris ditulis)...';
                    } else if (t === 'Concat Fields') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Concat Fields] Menggabungkan beberapa field menjadi satu kolom target...';
                    } else if (t === 'Split Fields') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Split Fields] Memecah nilai kolom terdelimitasi menjadi kolom terpisah...';
                    } else if (t === 'Add Constants') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Add Constants] Menyisipkan nilai konstanta statis ke kolom target...';
                    } else if (t === 'Lookup') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Database Lookup] Melakukan pencarian ending_balance periode sebelumnya sebagai beginning_balance...';
                    } else if (t === 'Join') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Merge Join] Menggabungkan data master transaksi dan profil customer...';
                    } else if (t === 'Aggregation') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Group By] Agregasi transaksi pembayaran (sum amount) per customer per bulan...';
                    } else if (t === 'Calculator') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Calculator] Menghitung formula: ending_balance = beginning_balance + payment_amount...';
                    } else if (t === 'Data Validation') {
                        desc = 'INFO - [' + new Date().toLocaleTimeString() + '] [Pentaho Data Validation] Memvalidasi keabsahan data saldo akhir (non-negatif)...';
                    }

                    steps.push({
                        desc: desc,
                        delay: 600,
                        action: () => {
                            let stepRejected = 0;
                            if (t === 'Remove Null') {
                                stepRejected = Math.floor(Math.random() * 10) + 1;
                            }
                            if (t === 'Remove Duplicate') {
                                stepRejected = Math.floor(Math.random() * 8) + 1;
                            }
                            if (t === 'Group By') {
                                stepRejected = Math.floor(this.rowsRead * 0.4);
                            }
                            this.rowsRejected += stepRejected;
                            
                            stepMetricsList.push({
                                step: t,
                                read: this.rowsRead,
                                written: this.rowsRead - this.rowsRejected,
                                rejected: stepRejected,
                                status: 'Success'
                            });
                        }
                    });
                });
            }

            steps.push({
                desc: 'INFO - [' + new Date().toLocaleTimeString() + '] Menghubungkan ke target PostgreSQL Data Warehouse...',
                delay: 650,
                action: () => {
                    stepMetricsList.push({
                        step: 'Table Output',
                        read: this.rowsRead - this.rowsRejected,
                        written: 0,
                        rejected: 0,
                        status: 'Running'
                    });
                }
            });

            if (willSucceed) {
                steps.push({
                    desc: 'INFO - [' + new Date().toLocaleTimeString() + '] Memulai proses bulk loading ke tabel target: ' + data.targetTable + '...',
                    delay: 800,
                    action: () => {
                        this.rowsWritten = this.rowsRead - this.rowsRejected;
                        let idx = stepMetricsList.findIndex(sm => sm.step === 'Table Output');
                        if (idx !== -1) {
                            stepMetricsList[idx].written = this.rowsWritten;
                            stepMetricsList[idx].status = 'Success';
                        }
                    }
                });
                
                steps.push({
                    desc: 'INFO - [' + new Date().toLocaleTimeString() + '] Eksekusi ETL Sukses diselesaikan.',
                    delay: 500,
                    action: () => {
                        this.isRunning = false;
                        $wire.completeRunSuccess(data.runId, this.logs, this.rowsRead, this.rowsWritten, this.rowsRejected);
                    }
                });
            } else {
                // Fail step
                const errors = [
                    'ERROR - Connection Refused: Listener TNS database Oracle di 10.15.2.41:1521 menolak koneksi. ORA-12541 TNS: no listener.',
                    'ERROR - Unique Constraint Violation: Duplicate key value violates unique constraint \'idx_customer_email\' on target table \'dim_customer\'.',
                    'ERROR - Read Timeout: Koneksi ke server SharePoint terputus saat mengunduh data leads_export.csv.',
                    'ERROR - Out of Memory: Driver kehabisan memori RAM saat memproses pembersihan string tabel customers_raw.'
                ];
                const selectedError = errors[Math.floor(Math.random() * errors.length)];
                
                steps.push({
                    desc: 'ERROR - [' + new Date().toLocaleTimeString() + '] Terjadi gangguan eksekusi:\n' + selectedError,
                    delay: 900,
                    action: () => {
                        this.rowsRejected = this.rowsRead;
                        let idx = stepMetricsList.findIndex(sm => sm.step === 'Table Output');
                        if (idx !== -1) {
                            stepMetricsList[idx].status = 'Failed';
                        }
                    }
                });
                
                steps.push({
                    desc: 'ERROR - [' + new Date().toLocaleTimeString() + '] Eksekusi ETL Gagal tertunda.',
                    delay: 400,
                    action: () => {
                        this.isRunning = false;
                        $wire.completeRunFailed(data.runId, this.logs, selectedError);
                    }
                });
            }

            const executeStep = () => {
                if (step < steps.length) {
                    const current = steps[step];
                    setTimeout(() => {
                        current.action();
                        this.logs += current.desc + '\n';
                        this.progress = Math.min(100, Math.floor(((step + 1) / steps.length) * 100));
                        
                        // Update live progress to database
                        $wire.updateRunProgress(data.runId, this.logs, this.rowsRead, this.rowsWritten, this.rowsRejected, stepMetricsList);
                        
                        step++;
                        executeStep();
                    }, current.delay);
                }
            };
            
            executeStep();
        });

        window.addEventListener('execution-completed', e => {
            this.notificationMessage = e.detail.message;
            this.showNotification = true;
            setTimeout(() => this.showNotification = false, 4000);
        });
    }
}" class="space-y-6">

    <!-- Notification Toast -->
    <div x-show="showNotification" x-cloak x-transition class="fixed top-6 right-6 z-50 bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 border border-slate-700 dark:border-slate-200 text-xs font-semibold">
        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span x-text="notificationMessage"></span>
    </div>

    <!-- Live Execution Console Drawer overlay when running -->
    <template x-if="isRunning">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-md z-50 flex items-center justify-center p-4">
            <div class="bg-[#0B0F19] border border-slate-800 rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden p-6 space-y-6 animate-in fade-in zoom-in-95 duration-200 text-slate-200">
                <!-- Header -->
                <div class="flex justify-between items-center pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500 animate-ping"></span>
                        <h3 class="font-extrabold text-sm font-mono text-amber-400">ETL RUNNER ENGINE - LIVE CONSOLE</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-500 font-mono" x-text="progress + '%'"></span>
                </div>

                <!-- Stats Counter -->
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-[9px] text-slate-500 font-bold block uppercase tracking-wider mb-1">Baris Dibaca</span>
                        <strong class="text-xl font-bold font-mono text-blue-400" x-text="rowsRead">0</strong>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-[9px] text-slate-500 font-bold block uppercase tracking-wider mb-1">Baris Ditulis</span>
                        <strong class="text-xl font-bold font-mono text-emerald-400" x-text="rowsWritten">0</strong>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-xl border border-slate-800/80">
                        <span class="text-[9px] text-slate-500 font-bold block uppercase tracking-wider mb-1">Baris Ditolak</span>
                        <strong class="text-xl font-bold font-mono text-red-400" x-text="rowsRejected">0</strong>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1">
                    <div class="w-full bg-slate-900 rounded-full h-2 border border-slate-800 overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                    </div>
                </div>

                <!-- Logs Box -->
                <div class="space-y-1">
                    <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Terminal Ingestion Logs:</span>
                    <pre class="bg-black p-4 rounded-xl font-mono text-[10px] text-indigo-300 overflow-y-auto max-h-60 border border-slate-900 text-left select-all whitespace-pre-wrap leading-relaxed shadow-inner" x-text="logs"></pre>
                </div>
            </div>
        </div>
    </template>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Designed Pipelines List (Left Side) -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-900 dark:text-white text-xs uppercase tracking-wider">Pilih Pipeline</h3>
                <div class="space-y-3">
                    @forelse($pipelines as $pipe)
                        <div class="p-3.5 bg-slate-50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-850 rounded-xl space-y-3">
                            <div>
                                <h4 class="font-bold text-xs text-slate-800 dark:text-slate-200 font-mono">{{ $pipe['name'] }}</h4>
                                <div class="flex items-center gap-1.5 mt-1 text-[10px] text-slate-500 font-medium">
                                    <span>{{ $pipe['source_table'] }}</span>
                                    <span>→</span>
                                    <span>{{ $pipe['target_table'] }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-slate-100 dark:border-slate-800">
                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 font-bold">
                                    {{ count($pipe['transformations'] ?? []) }} Transforms
                                </span>
                                <button 
                                    wire:click="startRun({{ $pipe['id'] }})"
                                    class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-bold transition-all shadow-sm flex items-center gap-1"
                                    {{ $runningPipelineId ? 'disabled' : '' }}
                                >
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                    Run Pipeline
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-xs text-slate-400 py-6">
                            Belum ada pipeline aktif. Rancang di sub-menu Pipelines.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Failure AI Diagnostics Panel & History Runs Table (Right Side) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- AI Failure Analysis insights drawer (consistent with modern SaaS styling) -->
            @php
                $selectedRun = $selectedRunId ? collect($runs)->firstWhere('id', $selectedRunId) : null;
            @endphp            @if($selectedRun)
                @php
                    $status = $selectedRun['status'];
                    $panelBg = match($status) {
                        'Success' => 'bg-emerald-50/20 dark:bg-emerald-500/5 border-emerald-200 dark:border-emerald-500/20',
                        'Running' => 'bg-amber-50/20 dark:bg-amber-500/5 border-amber-200 dark:border-amber-500/20',
                        default => 'bg-red-50/20 dark:bg-red-500/5 border-red-200 dark:border-red-500/20'
                    };
                    $badgeBg = match($status) {
                        'Success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                        'Running' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 animate-pulse',
                        default => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                    };
                @endphp
                
                <div class="{{ $panelBg }} border rounded-xl p-5 shadow-sm space-y-4 animate-in fade-in slide-in-from-top-4 duration-200 text-xs">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $badgeBg }} uppercase tracking-wider font-mono">
                                @if($status === 'Failed')
                                    AI FAILURE ANALYSIS - INSIGHT PANEL
                                @elseif($status === 'Running')
                                    ETL PIPELINE RUNNING - PROGRESS PANEL
                                @else
                                    ETL PIPELINE SUCCESS - LOGS PANEL
                                @endif
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-base mt-2 font-mono flex items-center gap-2">
                                {{ $selectedRun['pipeline']['name'] }} (Run #{{ $selectedRun['id'] }})
                                @if($status === 'Running')
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                                @endif
                            </h3>
                            <p class="text-[10px] text-slate-500 mt-1">
                                Waktu Mulai: <span class="font-mono">{{ date('Y-m-d H:i:s', strtotime($selectedRun['start_time'])) }}</span> 
                                @if($selectedRun['end_time'])
                                    | Selesai: <span class="font-mono">{{ date('H:i:s', strtotime($selectedRun['end_time'])) }}</span> 
                                @endif
                                | Durasi: <span class="font-mono">{{ $selectedRun['duration_seconds'] ?? 0 }}s</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($status === 'Running')
                                <button 
                                    wire:click="forceStopRun({{ $selectedRun['id'] }})"
                                    class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-[10px] font-bold transition-all shadow-sm"
                                >
                                    Stop Pipeline
                                </button>
                            @endif
                            <button 
                                wire:click="selectRun(null)"
                                class="text-slate-400 hover:text-slate-650 text-xs font-bold"
                            >
                                &times; Tutup
                            </button>
                        </div>
                    </div>

                    <!-- Row Stats Overview -->
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-white dark:bg-[#161A25] p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                            <span class="text-[9px] text-slate-500 font-bold block uppercase tracking-wider mb-0.5">Read</span>
                            <strong class="text-sm font-bold font-mono text-blue-500">{{ number_format($selectedRun['rows_read']) }}</strong>
                        </div>
                        <div class="bg-white dark:bg-[#161A25] p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                            <span class="text-[9px] text-slate-500 font-bold block uppercase tracking-wider mb-0.5">Written</span>
                            <strong class="text-sm font-bold font-mono text-emerald-500">{{ number_format($selectedRun['rows_written']) }}</strong>
                        </div>
                        <div class="bg-white dark:bg-[#161A25] p-2.5 rounded-lg border border-slate-200 dark:border-slate-800">
                            <span class="text-[9px] text-slate-500 font-bold block uppercase tracking-wider mb-0.5">Rejected</span>
                            <strong class="text-sm font-bold font-mono text-red-500">{{ number_format($selectedRun['rows_rejected']) }}</strong>
                        </div>
                    </div>

                    @if($status === 'Failed')
                        <!-- Error Log Snippet -->
                        <div class="p-3 bg-slate-950 text-red-400 font-mono text-[10px] rounded-lg border border-slate-900 overflow-x-auto select-all leading-relaxed">
                            {{ $selectedRun['error_log'] ?? 'Unknown connection termination.' }}
                        </div>

                        @if(!empty($selectedRun['ai_failure_analysis']))
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 pt-3 border-t border-slate-100 dark:border-slate-850">
                                <div class="space-y-3">
                                    <div>
                                        <strong class="text-slate-800 dark:text-slate-300 block mb-0.5 uppercase text-[9px] tracking-wider font-bold">Analisis Penyebab (Root Cause):</strong>
                                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                                            {{ $selectedRun['ai_failure_analysis']['root_cause'] }}
                                        </p>
                                    </div>
                                    <div>
                                        <strong class="text-slate-800 dark:text-slate-300 block mb-0.5 uppercase text-[9px] tracking-wider font-bold">Dampak Bisnis:</strong>
                                        <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                                            {{ $selectedRun['ai_failure_analysis']['impact'] }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="space-y-3">
                                    <div>
                                        <strong class="text-slate-800 dark:text-slate-300 block mb-0.5 uppercase text-[9px] tracking-wider font-bold">Kemungkinan Penyebab:</strong>
                                        <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-400">
                                            @foreach($selectedRun['ai_failure_analysis']['possibilities'] as $pos)
                                                <li>{{ $pos }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div>
                                        <strong class="text-indigo-600 dark:text-indigo-400 block mb-0.5 uppercase text-[9px] tracking-wider font-bold">Rekomendasi Tindakan:</strong>
                                        <ul class="list-disc list-inside space-y-1 text-indigo-700 dark:text-indigo-300 font-medium">
                                            @foreach($selectedRun['ai_failure_analysis']['recommendations'] as $rec)
                                                <li>{{ $rec }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                                <button 
                                    wire:click="autoFixRun({{ $selectedRun['id'] }})"
                                    class="px-4 py-2 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-750 hover:to-indigo-750 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm hover:shadow-md disabled:opacity-50"
                                    {{ $isFixing ? 'disabled' : '' }}
                                >
                                    @if($isFixing)
                                        <svg class="w-4 h-4 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                        Menerapkan Perbaikan...
                                    @else
                                        <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                                        Auto-Fix dengan AI
                                    @endif
                                </button>
                            </div>
                        @else
                            <!-- Loading spinner for diagnostics generation -->
                            <div class="flex items-center gap-3">
                                <button 
                                    wire:click="analyzeFailure({{ $selectedRun['id'] }})"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors disabled:opacity-50"
                                    {{ $isAnalyzing ? 'disabled' : '' }}
                                >
                                    @if($isAnalyzing)
                                        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                        Menganalisis...
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                        Minta Analisis AI
                                    @endif
                                </button>

                                <button 
                                    wire:click="autoFixRun({{ $selectedRun['id'] }})"
                                    class="px-4 py-2 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-750 hover:to-indigo-750 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm hover:shadow-md disabled:opacity-50"
                                    {{ $isFixing ? 'disabled' : '' }}
                                >
                                    @if($isFixing)
                                        <svg class="w-4 h-4 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                        Menerapkan Perbaikan...
                                    @else
                                        <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                                        Auto-Fix dengan AI
                                    @endif
                                </button>
                            </div>
                        @endif
                    @endif

                    <!-- Step Metrics Table (if available) -->
                    @if(!empty($selectedRun['step_metrics']))
                        <div class="space-y-1.5 pt-3 border-t border-slate-100 dark:border-slate-850">
                            <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">Step Metrics Progress:</span>
                            <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-[10px]">
                                    <thead class="bg-slate-50 dark:bg-[#161A25]">
                                        <tr>
                                            <th class="px-3 py-1.5 text-left font-bold text-slate-500">Step Name</th>
                                            <th class="px-3 py-1.5 text-right font-bold text-slate-500">Read</th>
                                            <th class="px-3 py-1.5 text-right font-bold text-slate-500">Written</th>
                                            <th class="px-3 py-1.5 text-right font-bold text-slate-500">Rejected</th>
                                            <th class="px-3 py-1.5 text-center font-bold text-slate-500">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-150 dark:divide-slate-800 font-mono text-[9px] text-slate-600 dark:text-slate-350 bg-white dark:bg-[#12151E]">
                                        @foreach($selectedRun['step_metrics'] as $key => $sm)
                                            @php
                                                $stepName = $sm['step'] ?? $sm['label'] ?? $sm['name'] ?? (is_string($key) ? $key : 'Unknown Step');
                                                $read = $sm['read'] ?? $sm['input'] ?? 0;
                                                $written = $sm['written'] ?? $sm['output'] ?? 0;
                                                $rejected = $sm['rejected'] ?? 0;
                                                $status = $sm['status'] ?? 'Success';
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-1.5 font-bold">{{ $stepName }}</td>
                                                <td class="px-3 py-1.5 text-right">{{ number_format($read) }}</td>
                                                <td class="px-3 py-1.5 text-right">{{ number_format($written) }}</td>
                                                <td class="px-3 py-1.5 text-right text-red-500">{{ number_format($rejected) }}</td>
                                                <td class="px-3 py-1.5 text-center">
                                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold {{ $status === 'Success' ? 'bg-emerald-500/10 text-emerald-400' : ($status === 'Running' ? 'bg-amber-500/10 text-amber-400 animate-pulse' : 'bg-red-500/10 text-red-400') }}">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Log Output pre -->
                    @if(!empty($selectedRun['execution_logs']))
                        <div class="space-y-1.5 pt-3 border-t border-slate-100 dark:border-slate-850">
                            <span class="text-[9px] text-slate-500 font-bold uppercase tracking-wider block">Terminal Ingestion Logs:</span>
                            <pre class="bg-black p-3 rounded-lg font-mono text-[10px] text-indigo-300 overflow-y-auto max-h-48 border border-slate-900 text-left select-all whitespace-pre-wrap leading-relaxed shadow-inner">{{ $selectedRun['execution_logs'] }}</pre>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Runs History list table -->
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Riwayat Eksekusi Pipeline</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735] text-xs">
                        <thead class="bg-slate-50 dark:bg-[#161A25]">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pipeline Name</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Waktu Mulai</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Duration</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Read/Written</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-[#222735] font-medium text-slate-700 dark:text-slate-300">
                            @forelse($runs as $run)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold">{{ $run['pipeline']['name'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $run['status'] === 'Success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400' : ($run['status'] === 'Running' ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400 animate-pulse' : 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400') }}">
                                            {{ $run['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 font-mono">{{ date('H:i:s d-M', strtotime($run['start_time'])) }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ $run['duration_seconds'] }}s</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($run['rows_read']) }} / {{ number_format($run['rows_written']) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                wire:click="selectRun({{ $run['id'] }})"
                                                class="text-indigo-500 hover:text-indigo-400 dark:text-indigo-400 dark:hover:text-indigo-300 hover:underline text-[10px] font-bold whitespace-nowrap"
                                            >
                                                @if($run['status'] === 'Failed')
                                                    Analisis AI
                                                @else
                                                    Lihat Detail
                                                @endif
                                            </button>
                                            @if($run['status'] === 'Running')
                                                <span class="text-slate-300 dark:text-slate-700">|</span>
                                                <button 
                                                    wire:click="forceStopRun({{ $run['id'] }})"
                                                    class="text-amber-500 hover:text-amber-400 hover:underline text-[10px] font-bold whitespace-nowrap"
                                                >
                                                    Hentikan
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                                        Belum ada riwayat eksekusi pipeline yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
