<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar - Tables List -->
    <div class="lg:col-span-1 bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl shadow-sm overflow-hidden flex flex-col h-[calc(100vh-12rem)]">
        <div class="p-4 border-b border-slate-200 dark:border-[#222735] bg-slate-50/50 dark:bg-[#161A25]/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Daftar Tabel
            </h3>
            <span class="bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-400 text-xs px-2 py-0.5 rounded-full font-bold">
                {{ count($tables) }}
            </span>
        </div>
        <div class="flex-1 overflow-y-auto p-2 space-y-1">
            @foreach($tables as $table)
                <button 
                    wire:click="selectTable('{{ $table->name }}')"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-left text-sm font-medium transition-colors {{ $activeTableName === $table->name ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-[#1C212E]' }}"
                >
                    <span class="truncate">{{ $table->name }}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                        {{ number_format($table->row_count) }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="lg:col-span-3 space-y-6">
        @if($selectedTable)
            <!-- Table Header Stats -->
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white font-mono">{{ $selectedTable->name }}</h2>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                {{ $selectedTable->source_system }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Diperbarui: {{ $selectedTable->last_refresh->diffForHumans() }} ({{ $selectedTable->last_refresh->format('d M Y H:i') }})
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-6 bg-slate-50 dark:bg-[#161A25] p-3 rounded-lg border border-slate-100 dark:border-slate-800">
                        <div class="text-center">
                            <span class="block text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">Kolom</span>
                            <span class="text-lg font-bold text-slate-800 dark:text-slate-200">{{ $selectedTable->col_count }}</span>
                        </div>
                        <div class="w-px h-8 bg-slate-200 dark:bg-slate-800"></div>
                        <div class="text-center">
                            <span class="block text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">Baris</span>
                            <span class="text-lg font-bold text-slate-800 dark:text-slate-200">{{ number_format($selectedTable->row_count) }}</span>
                        </div>
                        <div class="w-px h-8 bg-slate-200 dark:bg-slate-800"></div>
                        <div class="text-center">
                            <span class="block text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wider font-bold">Skor Kualitas</span>
                            <span class="text-lg font-bold {{ $selectedTable->quality_score >= 90 ? 'text-emerald-500' : ($selectedTable->quality_score >= 75 ? 'text-amber-500' : 'text-red-500') }}">
                                {{ $selectedTable->quality_score }}%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="flex border-b border-slate-200 dark:border-[#222735] mt-6">
                    @foreach(['schema' => 'Schema', 'preview' => 'Data Preview', 'profiling' => 'Data Profiling', 'catalog' => 'AI Catalog', 'lineage' => 'Data Lineage'] as $tab => $label)
                        <button 
                            wire:click="selectTab('{{ $tab }}')"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-all -mb-px {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Tab Content -->
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm min-h-[400px]">
                
                <!-- SCHEMA TAB -->
                @if($activeTab === 'schema')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735]">
                            <thead class="bg-slate-50 dark:bg-[#161A25]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nama Kolom</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipe Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nullable</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nilai Unik</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Missing %</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-[#222735] text-sm">
                                @foreach($schema as $col)
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                        <td class="px-4 py-3 font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ $col->name }}</td>
                                        <td class="px-4 py-3 text-slate-600 dark:text-slate-400 font-mono text-xs">{{ $col->data_type }}</td>
                                        <td class="px-4 py-3 text-slate-500">
                                            <span class="px-1.5 py-0.5 rounded text-xs {{ $col->is_nullable === 'YES' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                                {{ $col->is_nullable }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">{{ number_format($col->distinct_count) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">
                                            <span class="{{ $col->missing_percentage > 0 ? 'text-amber-500 font-semibold' : 'text-slate-400' }}">
                                                {{ number_format($col->missing_percentage, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- PREVIEW DATA TAB -->
                @if($activeTab === 'preview')
                    @if(!empty($previewData['columns']))
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735]">
                                <thead class="bg-slate-50 dark:bg-[#161A25]">
                                    <tr>
                                        @foreach($previewData['columns'] as $col)
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider font-mono">{{ $col }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-[#222735] text-sm font-mono">
                                    @foreach($previewData['rows'] as $row)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                            @foreach($previewData['columns'] as $col)
                                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300 max-w-[200px] truncate" title="{{ $row[$col] }}">
                                                    {{ $row[$col] ?? 'null' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                            <p class="text-sm font-medium">Gagal memuat data preview. Pastikan tabel terisi.</p>
                        </div>
                    @endif
                @endif

                <!-- DATA PROFILING TAB -->
                @if($activeTab === 'profiling')
                    <div class="space-y-6">
                        <!-- Profile Summary Stats -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-slate-50 dark:bg-[#161A25] p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider">Missing Values</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-2xl font-bold text-slate-800 dark:text-slate-200">
                                        {{ $schema->sum('missing_percentage') > 0 ? 'Ada Masalah' : '0%' }}
                                    </span>
                                </div>
                            </div>
                            <div class="bg-slate-50 dark:bg-[#161A25] p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider">Duplicate Rows</span>
                                <div class="mt-1">
                                    <span class="text-2xl font-bold text-slate-800 dark:text-slate-200">0 Baris</span>
                                </div>
                            </div>
                            <div class="bg-slate-50 dark:bg-[#161A25] p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider">Anomali Terdeteksi</span>
                                <div class="mt-1">
                                    <span class="text-2xl font-bold {{ count($recommendations) > 0 ? 'text-amber-500' : 'text-slate-800 dark:text-slate-200' }}">
                                        {{ count($recommendations) }} Isu
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Data Quality Recommendations -->
                        <div>
                            <h4 class="font-bold text-slate-900 dark:text-white mb-3 text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Rekomendasi Kualitas Data AI
                            </h4>
                            @if(count($recommendations) > 0)
                                <div class="space-y-4">
                                    @foreach($recommendations as $rec)
                                        <div class="border border-slate-150 dark:border-slate-800 rounded-xl p-4 flex gap-4 bg-slate-50/30 dark:bg-slate-800/10">
                                            <div class="w-2 h-auto rounded-full {{ $rec->priority_level === 'High' ? 'bg-red-500' : 'bg-amber-500' }}"></div>
                                            <div class="flex-1 space-y-1">
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $rec->finding_type }}</span>
                                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $rec->priority_level === 'High' ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                                        {{ $rec->priority_level }} Priority
                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-600 dark:text-slate-400 font-medium">{{ $rec->finding_summary }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-500"><strong class="text-slate-700 dark:text-slate-300">Dampak Bisnis:</strong> {{ $rec->business_impact }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-500"><strong class="text-indigo-600 dark:text-indigo-400">Rekomendasi Tindakan:</strong> {{ $rec->recommended_action }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-6 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-slate-400">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <p class="text-sm">Tidak ada isu kualitas data yang terdeteksi pada tabel ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- AI DATA CATALOG TAB -->
                @if($activeTab === 'catalog')
                    <div class="space-y-6">
                        <div class="flex justify-between items-center">
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                Dokumentasi Metadata Otomatis
                            </h4>
                            <button 
                                wire:click="generateCatalog"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold flex items-center gap-2 transition-colors disabled:opacity-50"
                                {{ $isGeneratingCatalog ? 'disabled' : '' }}
                            >
                                <svg class="w-4 h-4 {{ $isGeneratingCatalog ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                {{ $isGeneratingCatalog ? 'Sedang Generate...' : 'Regenerate via Gemini' }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Left: General Summary -->
                            <div class="md:col-span-2 space-y-4">
                                <div class="bg-slate-50/50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-xl p-5">
                                    <h5 class="font-bold text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Deskripsi Tabel</h5>
                                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                                        {{ $selectedTable->description ?? 'Deskripsi belum dihasilkan. Klik button di kanan atas untuk membuat katalog otomatis.' }}
                                    </p>
                                </div>

                                <div class="bg-slate-50/50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-xl p-5">
                                    <h5 class="font-bold text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Kolom Penting</h5>
                                    <ul class="list-disc list-inside space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                        @if(is_array($selectedTable->key_columns))
                                            @foreach($selectedTable->key_columns as $kc)
                                                <li><code class="font-mono text-xs px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400">{{ $kc }}</code></li>
                                            @endforeach
                                        @else
                                            <li>Belum didokumentasikan.</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            <!-- Right: Business Context -->
                            <div class="md:col-span-1 space-y-4">
                                <div class="bg-slate-50/50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-xl p-5">
                                    <h5 class="font-bold text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Business Owner</h5>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        {{ $selectedTable->business_owner ?? 'N/A' }}
                                    </p>
                                </div>

                                <div class="bg-slate-50/50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-xl p-5">
                                    <h5 class="font-bold text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Dashboard Pengguna</h5>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @if(is_array($selectedTable->dashboards_used))
                                            @foreach($selectedTable->dashboards_used as $dbName)
                                                <span class="text-xs px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 font-semibold">
                                                    {{ $dbName }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-xs text-slate-500">N/A</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- DATA LINEAGE TAB -->
                @if($activeTab === 'lineage')
                    <div class="space-y-6">
                        <h4 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 12l-3-3m3 3l3-3M17 8V20m0-12l-3 3m3-3l3 3"></path></svg>
                            Silsilah Alur Data (Data Lineage)
                        </h4>
                        
                        <!-- Lineage Visual SVG -->
                        <div class="p-6 bg-slate-50/50 dark:bg-[#161A25]/50 border border-slate-150 dark:border-slate-800 rounded-xl flex items-center justify-center">
                            <svg viewBox="0 0 800 220" class="w-full max-w-4xl h-auto" xmlns="http://www.w3.org/2000/svg">
                                <!-- Definitions for markers -->
                                <defs>
                                    <marker id="arrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                        <path d="M 0 0 L 10 5 L 0 10 z" fill="#818cf8"/>
                                    </marker>
                                </defs>

                                <!-- ERP Database Source Node -->
                                <g transform="translate(20, 75)">
                                    <rect width="130" height="70" rx="12" fill="#3b82f6" opacity="0.1" stroke="#3b82f6" stroke-width="1.5" />
                                    <text x="65" y="32" font-weight="bold" fill="#3b82f6" font-size="12" text-anchor="middle">ERP Database</text>
                                    <text x="65" y="52" fill="#60a5fa" font-size="10" text-anchor="middle">Source Connection</text>
                                </g>

                                <!-- Flow Arrow 1 -->
                                <line x1="150" y1="110" x2="200" y2="110" stroke="#818cf8" stroke-width="2" marker-end="url(#arrow)" />

                                <!-- ETL Pipeline Transformation Node -->
                                <g transform="translate(210, 75)">
                                    <rect width="150" height="70" rx="12" fill="#a855f7" opacity="0.1" stroke="#a855f7" stroke-width="1.5" />
                                    <text x="75" y="32" font-weight="bold" fill="#a855f7" font-size="12" text-anchor="middle">Pentaho ETL</text>
                                    <text x="75" y="52" fill="#c084fc" font-size="10" text-anchor="middle">Sales_Ingestion_Job</text>
                                </g>

                                <!-- Flow Arrow 2 -->
                                <line x1="360" y1="110" x2="410" y2="110" stroke="#818cf8" stroke-width="2" marker-end="url(#arrow)" />

                                <!-- Warehouse Tables Node -->
                                <g transform="translate(420, 75)">
                                    <rect width="160" height="70" rx="12" fill="#6366f1" opacity="0.1" stroke="#6366f1" stroke-dasharray="2" stroke-width="2" />
                                    <text x="80" y="32" font-weight="bold" fill="#6366f1" font-size="13" text-anchor="middle">{{ $selectedTable->name }}</text>
                                    <text x="80" y="52" fill="#818cf8" font-size="10" text-anchor="middle">Data Warehouse Table</text>
                                </g>

                                <!-- Flow Arrow 3 -->
                                <line x1="580" y1="110" x2="630" y2="110" stroke="#818cf8" stroke-width="2" marker-end="url(#arrow)" />

                                <!-- BI Dashboard Node -->
                                <g transform="translate(640, 75)">
                                    <rect width="140" height="70" rx="12" fill="#10b981" opacity="0.1" stroke="#10b981" stroke-width="1.5" />
                                    <text x="70" y="32" font-weight="bold" fill="#10b981" font-size="12" text-anchor="middle">Power BI</text>
                                    <text x="70" y="52" fill="#34d399" font-size="10" text-anchor="middle">Revenue Dashboard</text>
                                </g>
                            </svg>
                        </div>
                    </div>
                @endif

            </div>
        @else
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-12 text-center text-slate-400">
                <p>Silakan pilih tabel di sidebar untuk memulai eksplorasi.</p>
            </div>
        @endif
    </div>
</div>
