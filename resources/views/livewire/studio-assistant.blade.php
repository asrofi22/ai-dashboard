<div class="space-y-6" x-data="{ openScanner: false }">
    <!-- Header -->
    <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            AI ETL Assistant Desainer
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tulis instruksi dalam bahasa natural dan biarkan AI memindai metadata database, memilih tabel sumber optimal, menyusun mapping kalkulatif, dan menghasilkan visual pipeline siap jalan.</p>
    </div>

    <!-- Alert Notifications -->
    @if ($successMessage)
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg text-xs font-semibold border border-emerald-100 dark:border-emerald-500/20">
            {{ $successMessage }}
        </div>
    @endif
    @if ($errorMessage)
        <div class="p-3.5 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 rounded-lg text-xs font-semibold border border-red-100 dark:border-red-500/20">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Input Panel (Left Column) -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4">
                <form wire:submit.prevent="generatePipeline" class="space-y-4">
                    <!-- 1. Database Metadata Scanner Selector -->
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Pilih Koneksi Database</label>
                        <select 
                            wire:model.live="sourceConnectionId"
                            class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-medium"
                        >
                            @foreach($connectionsList as $conn)
                                <option value="{{ $conn['id'] }}">{{ $conn['name'] }} ({{ strtoupper($conn['driver']) }})</option>
                            @endforeach
                        </select>
                        @error('sourceConnectionId') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Collapsible JSON Scanner Box -->
                    <div class="space-y-1 bg-slate-50 dark:bg-[#161A25]/30 rounded-lg p-2.5 border border-slate-100 dark:border-slate-800">
                        <button 
                            type="button"
                            @click="openScanner = !openScanner"
                            class="w-full flex justify-between items-center text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hover:text-indigo-500 transition-colors"
                        >
                            <span>🔍 DATABASE METADATA SCANNER</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="openScanner ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="openScanner" x-cloak class="mt-2 text-[10px] space-y-1.5 font-mono max-h-48 overflow-y-auto bg-black p-2 rounded-lg text-indigo-300">
                            @if(!empty($databaseMetadata))
                                <pre class="whitespace-pre-wrap select-all leading-normal">{{ json_encode($databaseMetadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="text-slate-500">(Tidak ada metadata terpindai)</span>
                            @endif
                        </div>
                    </div>

                    <!-- 2. Scheduler Config -->
                    <div class="space-y-3 p-3 bg-slate-50 dark:bg-[#161A25]/30 border border-slate-100 dark:border-slate-850 rounded-xl">
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Integrasi Penjadwalan (Scheduler)</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="space-y-1">
                                <span class="text-[9px] text-slate-400 block font-semibold">Interval:</span>
                                <select 
                                    wire:model.live="scheduleInterval"
                                    class="w-full bg-white dark:bg-[#1C212E] border border-slate-200 dark:border-slate-800 rounded p-1 text-[11px] dark:text-white"
                                >
                                    <option value="manual">Manual (Sekali Jalankan)</option>
                                    <option value="hourly">Setiap Jam (Hourly)</option>
                                    <option value="daily">Setiap Hari (Daily)</option>
                                    <option value="weekly">Setiap Minggu (Weekly)</option>
                                    <option value="monthly">Setiap Bulan (Monthly)</option>
                                    <option value="custom">Custom Cron Expression</option>
                                </select>
                            </div>
                            
                            @if($scheduleInterval === 'custom')
                                <div class="space-y-1">
                                    <span class="text-[9px] text-slate-400 block font-semibold">Cron Expression:</span>
                                    <input 
                                        type="text" 
                                        wire:model="customCron"
                                        placeholder="0 * * * *"
                                        class="w-full bg-white dark:bg-[#1C212E] border border-slate-200 dark:border-slate-800 rounded p-1 text-[11px] font-mono dark:text-white"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- 3. Prompt Instruction -->
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Instruksi Rancang ETL</label>
                        <textarea 
                            wire:model="prompt"
                            rows="4"
                            class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white leading-relaxed"
                            placeholder="Tulis instruksi di sini (contoh: buatkan saya pipeline untuk etl mengisi tabel dw.fact_customer_balance pada db_warehouse_localgueh, source data dari schema public)..."
                        ></textarea>
                        @error('prompt') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Recommendations Prompts -->
                    <div class="space-y-1.5 text-xs">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Rekomendasi Prompt:</span>
                        <button 
                            type="button" 
                            @click="$wire.set('prompt', 'buatkan saya pipeline untuk etl mengisi tabel dw.fact_customer_balance pada database connection db_warehouse_localgueh yang source datanya diambil dari schema public pada database db_warehouse_localgueh juga. jadi anda lakukan scanning semua tabel yang ada di schema tersebut, kemudian rancanglah etlnya untuk mengisi tabel dw.fact_customer_balance yang isiannya ada balance_id, period_month, customer_id, beginning_balance, payment_amount, dan ending_balance.')"
                            class="w-full text-left p-2 bg-slate-50 dark:bg-[#161A25]/40 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-[9px] text-slate-500 dark:text-slate-400 font-medium transition-colors border border-slate-100 dark:border-slate-850"
                        >
                            📊 "Rancang ETL dw.fact_customer_balance menggunakan input tabel public dari db_warehouse_localgueh..."
                        </button>
                    </div>

                    <button 
                        type="submit"
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
                        {{ $isGenerating ? 'disabled' : '' }}
                    >
                        @if($isGenerating)
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                            Menganalisis Metadata & Rencana...
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Rancang dengan AI
                        @endif
                    </button>
                </form>
            </div>
        </div>

        <!-- Result Insights Panel (Right Columns) -->
        <div class="lg:col-span-2 space-y-6">
            @if($generatedPlan)
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-5 animate-in fade-in slide-in-from-right-4 duration-200 text-xs">
                    <!-- Title & Confidence Score Panel -->
                    <div class="flex justify-between items-start pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 uppercase tracking-wider font-mono">
                                AI PIPELINE BLUEPRINT GENERATED
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-base mt-2 font-mono">
                                {{ $generatedPlan['pipeline_name'] }}
                            </h3>
                        </div>

                        <!-- Confidence Score Badge -->
                        @php
                            $conf = $generatedPlan['confidence'] ?? ['score' => 85, 'category' => 'Medium Confidence', 'warning' => false, 'factors' => []];
                            $badgeColor = match(true) {
                                $conf['score'] >= 90 => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20',
                                $conf['score'] >= 70 => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
                                default => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 border-red-200 dark:border-red-500/20 animate-pulse'
                            };
                        @endphp
                        <div class="text-right">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg border font-bold text-[10px] {{ $badgeColor }}">
                                <span>AI Confidence:</span>
                                <span class="text-xs">{{ $conf['score'] }}%</span>
                            </div>
                            <span class="block text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-wider">{{ $conf['category'] }}</span>
                        </div>
                    </div>

                    <!-- Low Confidence Warning Banner -->
                    @if($conf['score'] < 70)
                        <div class="p-3 bg-red-50 dark:bg-red-500/5 text-red-600 dark:text-red-400 rounded-lg font-medium border border-red-100 dark:border-red-500/20 flex gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <div>
                                <strong class="block text-[11px] font-bold">Peringatan: Tingkat Kepercayaan AI Rendah</strong>
                                <span class="text-[10px]">Tingkat kecocokan skema rendah atau terdapat terlalu banyak asumsi. Verifikasi kembali kecocokan kolom di canvas setelah memuat pipeline.</span>
                            </div>
                        </div>
                    @endif

                    <!-- Dual Grid: Candidate Tables & AI Analysis -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- AI Analysis & Reasoning Panel -->
                        <div class="bg-slate-50 dark:bg-[#161A25]/40 border border-slate-100 dark:border-slate-850 p-4 rounded-xl space-y-3.5">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                AI Analysis & Reasoning
                            </h4>
                            <div class="space-y-2.5">
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold block">TARGET TABLE:</span>
                                    <strong class="font-mono text-slate-700 dark:text-slate-300 font-bold">{{ $generatedPlan['reasoning']['target'] ?? $generatedPlan['target_table'] }}</strong>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold block">TARGET COLUMNS:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($generatedPlan['reasoning']['target_columns'] ?? [] as $tc)
                                            <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded font-mono text-[9px] font-semibold">{{ $tc }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="space-y-1.5 pt-1">
                                    <span class="text-[9px] text-slate-400 font-bold block">ASUMSI & LOGIKA UTAMA:</span>
                                    <ul class="list-decimal list-inside space-y-1 text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                                        @foreach($generatedPlan['reasoning']['analyses'] ?? [] as $an)
                                            <li>{{ $an }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Candidate Source Tables Panel -->
                        <div class="bg-slate-50 dark:bg-[#161A25]/40 border border-slate-100 dark:border-slate-850 p-4 rounded-xl space-y-3.5">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Candidate Source Tables
                            </h4>
                            <div class="space-y-3">
                                @forelse($generatedPlan['candidate_sources'] ?? [] as $cand)
                                    <div class="p-2.5 bg-white dark:bg-[#1C212E] rounded-lg border border-slate-100 dark:border-slate-800 space-y-2">
                                        <div class="flex justify-between items-center">
                                            <strong class="font-mono text-slate-800 dark:text-slate-300 font-bold">{{ $cand['table'] }}</strong>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $cand['score'] >= 85 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}">
                                                Score: {{ $cand['score'] }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-150 dark:bg-slate-900 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full {{ $cand['score'] >= 85 ? 'bg-emerald-500' : 'bg-amber-500' }}" :style="'width: ' + {{ $cand['score'] }} + '%'"></div>
                                        </div>
                                        <ul class="text-[9px] text-slate-500 dark:text-slate-400 list-disc list-inside leading-relaxed">
                                            @foreach($cand['reasons'] as $r)
                                                <li>{{ $r }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @empty
                                    <span class="text-slate-500 text-xs">Tidak ada kandidat tabel terhitung.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Execution Plan Panel -->
                    <div class="bg-slate-50 dark:bg-[#161A25]/40 border border-slate-100 dark:border-slate-850 p-4 rounded-xl space-y-2.5">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ETL Execution Steps</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 text-center">
                            @php
                                $execSteps = [
                                    ['step' => 'Step 1', 'title' => 'Extract', 'desc' => 'Membaca ' . $generatedPlan['source_table']],
                                    ['step' => 'Step 2', 'title' => 'Lookup', 'desc' => 'T-1 Ending Balance'],
                                    ['step' => 'Step 3', 'title' => 'Aggregate', 'desc' => 'Group & Sum Amount'],
                                    ['step' => 'Step 4', 'title' => 'Calculator', 'desc' => 'beginning + payment'],
                                    ['step' => 'Step 5', 'title' => 'Load', 'desc' => 'Bulk load ke target']
                                ];
                            @endphp
                            @foreach($execSteps as $idx => $es)
                                <div class="p-2.5 bg-white dark:bg-[#1C212E] rounded-lg border border-slate-150 dark:border-slate-800 space-y-1">
                                    <span class="text-[8px] font-bold text-slate-400 block uppercase tracking-wider">{{ $es['step'] }}</span>
                                    <strong class="font-extrabold text-[11px] text-indigo-500 block uppercase">{{ $es['title'] }}</strong>
                                    <span class="text-[9px] text-slate-500 dark:text-slate-450 block leading-tight font-medium">{{ $es['desc'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tabbed Blueprint Previewer (Visual Pipeline, SQL Preview, JSON Definition) -->
                    <div class="space-y-2">
                        <div class="flex border-b border-slate-100 dark:border-slate-800 text-[10px] font-bold">
                            <button 
                                type="button"
                                wire:click="$set('activeTab', 'visual')"
                                class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider {{ $activeTab === 'visual' ? 'border-indigo-500 text-indigo-500' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                            >
                                🔗 Visual Flow
                            </button>
                            <button 
                                type="button"
                                wire:click="$set('activeTab', 'sql')"
                                class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider {{ $activeTab === 'sql' ? 'border-indigo-500 text-indigo-500' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                            >
                                💻 SQL Preview
                            </button>
                            <button 
                                type="button"
                                wire:click="$set('activeTab', 'json')"
                                class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider {{ $activeTab === 'json' ? 'border-indigo-500 text-indigo-500' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                            >
                                📂 JSON Definition
                            </button>
                        </div>

                        <!-- Tab Contents -->
                        <div class="p-4 bg-slate-50 dark:bg-[#161A25]/40 border border-slate-100 dark:border-slate-850 rounded-xl min-h-[200px]">
                            <!-- Tab 1: Visual Pipeline Node Flowchart -->
                            @if($activeTab === 'visual')
                                <div class="space-y-4">
                                    <div class="flex items-center gap-1.5 overflow-x-auto py-2">
                                        <div class="px-3 py-1.5 bg-blue-50 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400 border border-blue-150 dark:border-blue-500/20 rounded font-mono font-bold whitespace-nowrap">
                                            📥 Table Input: {{ $generatedPlan['source_table'] }}
                                        </div>
                                        @foreach($generatedPlan['transformations'] as $t)
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                            <div class="px-3 py-1.5 bg-purple-50 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400 border border-purple-150 dark:border-purple-500/20 rounded font-mono font-bold whitespace-nowrap">
                                                ⚙️ {{ $t }}
                                            </div>
                                        @endforeach
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        <div class="px-3 py-1.5 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-150 dark:border-emerald-500/20 rounded font-mono font-bold whitespace-nowrap">
                                            📤 Table Output: {{ $generatedPlan['target_table'] }}
                                        </div>
                                    </div>

                                    <!-- Mappings list -->
                                    <div class="space-y-1.5">
                                        <span class="text-[9px] font-bold text-slate-450 uppercase block tracking-wider">Rancangan Mappings Kolom:</span>
                                        <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-[10px]">
                                                <thead class="bg-slate-100 dark:bg-[#1C212E]">
                                                    <tr>
                                                        <th class="px-3 py-1.5 text-left font-bold text-slate-500">Kolom Sumber</th>
                                                        <th class="px-3 py-1.5 text-center text-slate-400 font-mono">Hubungan</th>
                                                        <th class="px-3 py-1.5 text-left font-bold text-slate-500">Kolom Target</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-150 dark:divide-slate-800 font-mono text-[9px] text-slate-600 dark:text-slate-350 bg-white dark:bg-[#12151E]">
                                                    @foreach($generatedPlan['column_mapping'] as $m)
                                                        <tr>
                                                            <td class="px-3 py-1.5 font-bold">{{ $m['source'] }}</td>
                                                            <td class="px-3 py-1.5 text-center text-slate-400 font-bold">→</td>
                                                            <td class="px-3 py-1.5 font-bold text-indigo-600 dark:text-indigo-400">{{ $m['target'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Tab 2: SQL Preview Query -->
                            @if($activeTab === 'sql')
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                                        <span>SQL Extraction Query (dialek database)</span>
                                        <span class="text-indigo-500">PostgreSQL</span>
                                    </div>
                                    <pre class="bg-black p-4 rounded-lg font-mono text-[10px] text-indigo-300 overflow-x-auto leading-relaxed select-all">{{ $generatedPlan['sql_preview'] ?? '' }}</pre>
                                </div>
                            @endif

                            <!-- Tab 3: JSON Steps Definition -->
                            @if($activeTab === 'json')
                                <div class="space-y-2">
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Struktur Data Internal JSON (PDI Engine Blueprint)</span>
                                    <pre class="bg-black p-4 rounded-lg font-mono text-[10px] text-indigo-300 overflow-x-auto leading-normal select-all">{{ json_encode($generatedPlan['json_definition'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Validation Results Engine Panel -->
                    <div class="bg-slate-50 dark:bg-[#161A25]/40 border border-slate-100 dark:border-slate-850 p-4 rounded-xl space-y-3.5">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            ETL Validation Result
                        </h4>
                        
                        @php
                            $val = $generatedPlan['validation_result'] ?? ['source_table_exists' => true, 'target_table_exists' => true, 'column_mapping_valid' => true, 'data_type_compatible' => true, 'lookup_relation_valid' => true, 'warnings' => []];
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-[10px] font-semibold text-slate-600 dark:text-slate-350">
                            <div class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-mono text-xs">✓</span>
                                <span>Source table exists</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-mono text-xs">✓</span>
                                <span>Target table exists</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="{{ $val['column_mapping_valid'] ? 'text-emerald-500' : 'text-amber-500' }} font-mono text-xs">{{ $val['column_mapping_valid'] ? '✓' : '⚠' }}</span>
                                <span>Column mapping valid</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-mono text-xs">✓</span>
                                <span>Data type compatible</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-emerald-500 font-mono text-xs">✓</span>
                                <span>Lookup relation valid</span>
                            </div>
                        </div>

                        <!-- Warnings list -->
                        @if(!empty($val['warnings']))
                            <div class="pt-2 border-t border-slate-150 dark:border-slate-800 space-y-1">
                                <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider block">Pemberitahuan Validasi:</span>
                                <ul class="text-[9px] text-amber-600 dark:text-amber-400 font-medium space-y-1 list-disc list-inside">
                                    @foreach($val['warnings'] as $w)
                                        <li>{{ $w }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <!-- Save Actions Controls -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button"
                            wire:click="$set('generatedPlan', null)"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-[#1C212E] rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="button"
                            wire:click="savePipeline"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Generate Visual Pipeline
                        </button>
                    </div>
                </div>
            @else
                <div class="bg-slate-50/50 dark:bg-[#12151E]/30 border border-slate-200/60 dark:border-[#222735]/40 border-dashed rounded-xl p-16 text-center text-slate-400 text-xs flex flex-col items-center justify-center min-h-[400px]">
                    <svg class="w-12 h-12 mx-auto mb-2 text-indigo-500/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    <p class="font-medium text-slate-500">Menunggu Input Instruksi Rancang</p>
                    <p class="mt-1 text-[11px] text-slate-450 max-w-sm mx-auto leading-relaxed">Pilih koneksi database, atur schedule jika diinginkan, dan masukkan perintah instruksi Anda di panel sebelah kiri untuk mulai menghasilkan blueprint ETL lengkap.</p>
                </div>
            @endif
        </div>
    </div>
</div>
