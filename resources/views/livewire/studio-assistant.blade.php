<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 dark:text-white">AI ETL Assistant</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tulis instruksi dalam bahasa natural dan biarkan AI merancang pipeline, memetakan skema kolom, serta menyusun skrip ETL secara otomatis.</p>
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
        <!-- Assistant Prompt Input (Left Side) -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4">
                <form wire:submit.prevent="generatePipeline" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tulis Instruksi Anda</label>
                        <textarea 
                            wire:model="prompt"
                            rows="5"
                            class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                            placeholder="Tulis di sini..."
                        ></textarea>
                        @error('prompt') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Sample Prompts suggestions -->
                    <div class="space-y-1 text-xs">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Rekomendasi Prompt:</span>
                        <div class="space-y-1.5">
                            <button 
                                type="button" 
                                @click="$wire.set('prompt', 'Ambil data customer dari Oracle ERP, hapus duplicate berdasarkan email, lalu simpan ke dim_customer pada PostgreSQL Data Warehouse.')"
                                class="w-full text-left p-2 bg-slate-50 dark:bg-[#161A25]/40 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-[10px] text-slate-600 dark:text-slate-400 font-medium transition-colors border border-slate-200/50 dark:border-slate-850"
                            >
                                💡 "Ambil data customer dari Oracle ERP, hapus duplicate berdasarkan email, lalu simpan ke dim_customer pada PostgreSQL Data Warehouse."
                            </button>
                            <button 
                                type="button" 
                                @click="$wire.set('prompt', 'Ambil file leads_export.csv dari SharePoint Sales Repo, bersihkan baris dengan email null, ubah email menjadi lowercase, lalu simpan ke dim_customer.')"
                                class="w-full text-left p-2 bg-slate-50 dark:bg-[#161A25]/40 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-[10px] text-slate-600 dark:text-slate-400 font-medium transition-colors border border-slate-200/50 dark:border-slate-850"
                            >
                                💡 "Ambil file leads_export.csv dari SharePoint, bersihkan email null, ubah ke lowercase, lalu simpan ke dim_customer."
                            </button>
                        </div>
                    </div>

                    <button 
                        type="submit"
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
                        {{ $isGenerating ? 'disabled' : '' }}
                    >
                        @if($isGenerating)
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                            Menganalisis Kebutuhan...
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Rancang dengan AI
                        @endif
                    </button>
                </form>
            </div>
        </div>

        <!-- Assistant Generation Review (Right Side) -->
        <div class="lg:col-span-2">
            @if($generatedPlan)
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-5 animate-in fade-in slide-in-from-right-4 duration-200">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 uppercase tracking-wider font-mono">
                                Rancangan Hasil Generasi AI
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-sm mt-2 font-mono">
                                {{ $generatedPlan['pipeline_name'] }}
                            </h3>
                        </div>
                        <button 
                            wire:click="$set('generatedPlan', null)"
                            class="text-xs text-slate-400 hover:text-slate-600"
                        >
                            Batal
                        </button>
                    </div>

                    <!-- Workflow visualization diagram (Visual Workflow) -->
                    <div class="space-y-1.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Visual Alur Workflow:</span>
                        <div class="p-3 bg-slate-50 dark:bg-[#161A25]/40 border border-slate-200/60 dark:border-slate-800 rounded-xl overflow-x-auto">
                            <div class="flex items-center gap-2.5 min-w-max text-[10px] font-mono font-bold">
                                <div class="bg-blue-50 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400 border border-blue-150 dark:border-blue-500/20 px-2 py-1 rounded">
                                    Sumber: {{ $generatedPlan['source_table'] }} ({{ $generatedPlan['source_connection_name'] }})
                                </div>
                                @foreach($generatedPlan['transformations'] as $t)
                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    <div class="bg-purple-50 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400 border border-purple-150 dark:border-purple-500/20 px-2 py-1 rounded">
                                        {{ $t }}
                                    </div>
                                @endforeach
                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-150 dark:border-emerald-500/20 px-2 py-1 rounded">
                                    Target: {{ $generatedPlan['target_table'] }} ({{ $generatedPlan['target_connection_name'] }})
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column mapping review -->
                    <div class="space-y-1.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">AI Column Mapping:</span>
                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden text-xs">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735]">
                                <thead class="bg-slate-50 dark:bg-[#161A25]">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Kolom Sumber</th>
                                        <th class="px-4 py-2 text-center text-slate-400">Hubungan</th>
                                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Kolom Target</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-150 dark:divide-[#222735] font-mono text-[10px] text-slate-700 dark:text-slate-350">
                                    @foreach($generatedPlan['column_mapping'] as $m)
                                        <tr>
                                            <td class="px-4 py-2 font-bold">{{ $m['source'] }}</td>
                                            <td class="px-4 py-2 text-center text-slate-400">→</td>
                                            <td class="px-4 py-2 font-bold text-indigo-600 dark:text-indigo-400">{{ $m['target'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Execution plan -->
                    <div class="space-y-1.5">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Rencana Eksekusi:</span>
                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed bg-slate-50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-850 p-3 rounded-xl font-medium">
                            {{ $generatedPlan['execution_plan'] }}
                        </p>
                    </div>

                    <!-- Save Actions -->
                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
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
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors shadow-sm flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Simpan Pipeline ke Database
                        </button>
                    </div>
                </div>
            @else
                <div class="bg-slate-50/50 dark:bg-[#12151E]/30 border border-slate-200/60 dark:border-[#222735]/40 border-dashed rounded-xl p-16 text-center text-slate-400 text-xs">
                    <svg class="w-12 h-12 mx-auto mb-2 text-indigo-500/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                    <p class="font-medium text-slate-500">Menunggu Input Instruksi</p>
                    <p class="mt-1 text-[11px] text-slate-400">Tulis kebutuhan integrasi data Anda di panel sebelah kiri untuk merancang alur visual pipeline otomatis.</p>
                </div>
            @endif
        </div>
    </div>
</div>
