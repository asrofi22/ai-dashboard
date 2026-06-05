<div class="space-y-6">
    <!-- Action Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Desainer Pipeline ETL</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Rancang skema integrasi data visual, konfigurasi kolom mapping, dan tentukan alur transformasi data.</p>
        </div>
        <button 
            wire:click="openCreate"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 shrink-0"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Rancang Pipeline Baru
        </button>
    </div>

    <!-- Notifications -->
    @if (session()->has('message'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg text-xs font-semibold border border-emerald-100 dark:border-emerald-500/20">
            {{ session('message') }}
        </div>
    @endif

    <!-- Pipelines List Grid -->
    <div class="grid grid-cols-1 gap-6">
        @forelse($pipelines as $pipe)
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4 hover:shadow-md transition-shadow">
                <!-- Header of Pipeline -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-extrabold text-slate-900 dark:text-white text-sm font-mono">
                            {{ $pipe['name'] }}
                        </h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Kunci Unik ID: #{{ $pipe['id'] }} | Diperbarui: {{ date('d M Y H:i', strtotime($pipe['updated_at'])) }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 uppercase tracking-wider">
                            {{ $pipe['is_active'] }}
                        </span>
                        <div class="flex gap-2">
                            <button 
                                wire:click="openEdit({{ $pipe['id'] }})"
                                class="px-2.5 py-1 text-slate-600 dark:text-slate-350 hover:text-indigo-600 dark:hover:text-indigo-400 rounded text-xs font-semibold border border-slate-200 dark:border-slate-800 transition-colors"
                            >
                                Edit
                            </button>
                            <button 
                                wire:click="delete({{ $pipe['id'] }})"
                                wire:confirm="Yakin ingin menghapus pipeline ini?"
                                class="px-2.5 py-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded text-xs font-semibold border border-red-200 dark:border-red-900/30 transition-colors"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Workflow Diagram Visualization (The requested visual workflow) -->
                <div class="py-2 overflow-x-auto">
                    <div class="flex items-center gap-3 min-w-max text-xs font-semibold">
                        <!-- Source Node -->
                        <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-500/10 border border-blue-150 dark:border-blue-500/20 rounded-xl p-3 max-w-[200px]">
                            <div class="w-5 h-5 rounded-lg bg-blue-500 text-white flex items-center justify-center text-[10px] font-bold shrink-0">S</div>
                            <div class="overflow-hidden">
                                <span class="block text-[8px] text-blue-400 uppercase tracking-wider">Source Table</span>
                                <strong class="text-blue-700 dark:text-blue-400 font-mono text-[10px] truncate block" title="{{ $pipe['source_table'] }}">{{ $pipe['source_table'] }}</strong>
                            </div>
                        </div>

                        <!-- Connection arrow -->
                        <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>

                        <!-- Transform Nodes -->
                        @forelse($pipe['transformations'] ?? [] as $t)
                            <div class="flex items-center gap-2 bg-purple-50 dark:bg-purple-500/10 border border-purple-150 dark:border-purple-500/20 rounded-xl p-2.5">
                                <span class="w-4 h-4 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold shrink-0">T</span>
                                <span class="text-purple-700 dark:text-purple-400 text-[10px]">{{ $t }}</span>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        @empty
                            <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 text-slate-400">
                                <span class="text-[10px] italic">Direct Load (No Transforms)</span>
                            </div>
                            <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        @endforelse

                        <!-- Target Node -->
                        <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-150 dark:border-emerald-500/20 rounded-xl p-3 max-w-[200px]">
                            <div class="w-5 h-5 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold shrink-0">T</div>
                            <div class="overflow-hidden">
                                <span class="block text-[8px] text-emerald-400 uppercase tracking-wider">Target Table</span>
                                <strong class="text-emerald-700 dark:text-emerald-400 font-mono text-[10px] truncate block" title="{{ $pipe['target_table'] }}">{{ $pipe['target_table'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info footer section -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Source Conn</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-350">{{ $pipe['source_connection']['name'] ?? 'Unknown' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Target Conn</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-350">{{ $pipe['target_connection']['name'] ?? 'Unknown' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Jadwal / Frekuensi</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-350 font-mono">Daily</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Mapping Kolom</span>
                        <span class="font-semibold text-indigo-600 dark:text-indigo-400 font-mono">{{ count($pipe['column_mapping'] ?? []) }} Kolom Terhubung</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-12 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-sm font-medium">Belum ada pipeline ETL yang terdaftar. Klik button **"Rancang Pipeline Baru"** di kanan atas untuk mendesain.</p>
            </div>
        @endforelse
    </div>

    <!-- Create/Edit Pipeline Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-150 dark:border-[#222735] flex justify-between items-center bg-slate-50/50 dark:bg-[#161A25]/50">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                        {{ $isEditing ? 'Ubah Desain Pipeline' : 'Rancang Pipeline ETL Baru' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>

                <!-- Modal Body Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-6 max-h-[calc(100vh-12rem)] overflow-y-auto">
                    <!-- General Details -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-3 sm:col-span-2 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Pipeline</label>
                            <input 
                                type="text" 
                                wire:model="name"
                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-mono"
                                placeholder="Contoh: sync_customer_gl"
                            />
                            @error('name') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-3 sm:col-span-1 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Default</label>
                            <select 
                                wire:model="isActive"
                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Workflow Source & Target Config -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Source Configuration Card -->
                        <div class="bg-blue-50/10 dark:bg-blue-500/5 border border-blue-200/50 dark:border-blue-500/10 rounded-xl p-4 space-y-3">
                            <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1.5 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> 1. EXTRACTION SOURCE
                            </h4>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="col-span-2 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase font-bold">Koneksi Sumber</label>
                                    <select 
                                        wire:model.live="sourceConnectionId"
                                        class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white"
                                    >
                                        <option value="">-- Pilih Koneksi Sumber --</option>
                                        @foreach($connections as $c)
                                            <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['driver'] }})</option>
                                        @endforeach
                                    </select>
                                    @error('sourceConnectionId') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase font-bold">Tabel / Berkas Sumber</label>
                                    <select 
                                        wire:model.live="sourceTable"
                                        class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white font-mono"
                                        {{ empty($sourceTables) ? 'disabled' : '' }}
                                    >
                                        <option value="">-- Pilih Tabel / Berkas --</option>
                                        @foreach($sourceTables as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    @error('sourceTable') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Target Configuration Card -->
                        <div class="bg-emerald-50/10 dark:bg-emerald-500/5 border border-emerald-200/50 dark:border-emerald-500/10 rounded-xl p-4 space-y-3">
                            <h4 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> 2. LOAD TARGET
                            </h4>
                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="col-span-2 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase font-bold">Koneksi Target DWH</label>
                                    <select 
                                        wire:model.live="targetConnectionId"
                                        class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white"
                                    >
                                        <option value="">-- Pilih Koneksi Target --</option>
                                        @foreach($connections as $c)
                                            <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['driver'] }})</option>
                                        @endforeach
                                    </select>
                                    @error('targetConnectionId') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase font-bold">Tabel Target Gudang Data</label>
                                    <select 
                                        wire:model.live="targetTable"
                                        class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white font-mono"
                                        {{ empty($targetTables) ? 'disabled' : '' }}
                                    >
                                        <option value="">-- Pilih Tabel Target --</option>
                                        @foreach($targetTables as $t)
                                            <option value="{{ $t }}">{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    @error('targetTable') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transformations Choices Checkbox Matrix -->
                    <div class="bg-white dark:bg-[#161A25]/20 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-3">
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 font-mono">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> 3. TRANFORMA DATA & RAGAM MODIFIKASI
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($availableTransformations as $t)
                                <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-[#161A25]/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl border border-slate-200/50 dark:border-slate-850 cursor-pointer select-none text-xs text-slate-700 dark:text-slate-350 transition-colors font-medium">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $t }}"
                                        wire:model="selectedTransformations"
                                        class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-750"
                                    />
                                    <span>{{ $t }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Visual Workflow Preview Inline in designer form -->
                    <div class="bg-slate-50/50 dark:bg-[#161A25]/10 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Visualisasi Alur Workflow Rencana:</span>
                        <div class="flex items-center gap-2 overflow-x-auto py-1.5 text-[10px] font-mono font-bold">
                            <div class="bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 px-2 py-1 rounded">
                                Source: {{ $sourceTable ?: '[Belum Dipilih]' }}
                            </div>
                            @foreach($selectedTransformations as $t)
                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20 px-2 py-1 rounded">
                                    {{ $t }}
                                </div>
                            @endforeach
                            <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <div class="bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 px-2 py-1 rounded">
                                Target DWH: {{ $targetTable ?: '[Belum Dipilih]' }}
                            </div>
                        </div>
                    </div>

                    <!-- Column Mapping Editor -->
                    <div class="bg-white dark:bg-[#161A25]/20 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                        <div class="flex justify-between items-center">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> 4. AI COLUMN MAPPING
                            </h4>
                            <button 
                                type="button"
                                wire:click="autoGenerateMapping"
                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded text-[10px] font-bold flex items-center gap-1 transition-colors"
                                {{ empty($sourceColumns) || empty($targetColumns) ? 'disabled' : '' }}
                            >
                                @if($isMappingLoading)
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                    Memetakan...
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                    Gunakan AI Automap
                                @endif
                            </button>
                        </div>

                        <div class="space-y-3">
                            @foreach($columnMappings as $index => $map)
                                <div class="flex items-center gap-3 animate-in fade-in slide-in-from-top-1 duration-150">
                                    <div class="flex-1">
                                        <select 
                                            wire:model="columnMappings.{{ $index }}.source"
                                            class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs font-mono dark:text-white"
                                        >
                                            <option value="">-- Pilih Kolom Sumber --</option>
                                            @foreach($sourceColumns as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                    <div class="flex-1">
                                        <select 
                                            wire:model="columnMappings.{{ $index }}.target"
                                            class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs font-mono dark:text-white"
                                        >
                                            <option value="">-- Pilih Kolom Target --</option>
                                            @foreach($targetColumns as $c)
                                                <option value="{{ $c }}">{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button 
                                        type="button" 
                                        wire:click="removeMappingRow({{ $index }})"
                                        class="p-1 text-slate-400 hover:text-red-500 rounded transition-colors"
                                    >
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <button 
                            type="button" 
                            wire:click="addMappingRow"
                            class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-bold flex items-center gap-1 mt-2"
                        >
                            + Tambah Kolom Mapping Baru
                        </button>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-150 dark:border-[#222735]">
                        <button 
                            type="button" 
                            wire:click="$set('showModal', false)"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-[#1C212E] rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors"
                        >
                            Simpan Pipeline
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
