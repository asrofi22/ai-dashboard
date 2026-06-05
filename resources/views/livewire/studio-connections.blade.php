<div class="space-y-6">
    <!-- Action Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">Koneksi Sumber & Target Data</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kelola kredensial koneksi PostgreSQL, MySQL, Oracle, SharePoint, CSV, dan Excel.</p>
        </div>
        <button 
            wire:click="openCreate"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 shrink-0"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Koneksi Baru
        </button>
    </div>

    <!-- Alert Notifications -->
    @if (session()->has('message'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg text-xs font-semibold border border-emerald-100 dark:border-emerald-500/20">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Connections Grid List (Left Side) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($connections as $conn)
                    <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4 hover:shadow-md transition-shadow relative">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border border-slate-250 dark:border-slate-800 uppercase tracking-wider">
                                    {{ $conn['type'] }}
                                </span>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm mt-2 flex items-center gap-1.5 font-mono">
                                    {{ $conn['name'] }}
                                </h3>
                            </div>
                            <span class="w-2.5 h-2.5 rounded-full {{ $conn['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}" title="Status: {{ $conn['status'] }}"></span>
                        </div>

                        <!-- Info Row -->
                        <div class="text-xs text-slate-500 space-y-1">
                            <div><span class="text-slate-400">Driver:</span> <code class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ strtoupper($conn['driver']) }}</code></div>
                            @if(in_array($conn['driver'], ['pgsql', 'mysql', 'oracle']))
                                <div><span class="text-slate-400">Database:</span> <span class="font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $conn['config']['database'] ?? 'N/A' }}</span></div>
                                <div><span class="text-slate-400">Host:</span> <span class="font-medium text-slate-700 dark:text-slate-300 font-mono">{{ $conn['config']['host'] ?? 'N/A' }}:{{ $conn['config']['port'] ?? '' }}</span></div>
                            @elseif($conn['driver'] === 'sharepoint')
                                <div class="truncate"><span class="text-slate-400">Folder:</span> <span class="font-medium text-slate-700 dark:text-slate-300 truncate" title="{{ $conn['config']['folder_url'] ?? '' }}">{{ $conn['config']['folder_url'] ?? 'N/A' }}</span></div>
                            @elseif(in_array($conn['driver'], ['csv', 'excel']))
                                <div class="truncate"><span class="text-slate-400">Path:</span> <span class="font-medium text-slate-700 dark:text-slate-300 truncate" title="{{ $conn['config']['file_path'] ?? '' }}">{{ $conn['config']['file_path'] ?? 'Local Upload' }}</span></div>
                            @endif
                        </div>

                        <!-- Card Actions -->
                        <div class="flex justify-between items-center pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button 
                                wire:click="viewMetadata({{ $conn['id'] }})"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-semibold flex items-center gap-1"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                Lihat Skema
                            </button>
                            <div class="flex items-center gap-2">
                                <button 
                                    wire:click="openEdit({{ $conn['id'] }})"
                                    class="text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 text-xs font-semibold"
                                >
                                    Edit
                                </button>
                                <span class="text-slate-200 dark:text-slate-800">|</span>
                                <button 
                                    wire:click="delete({{ $conn['id'] }})"
                                    wire:confirm="Yakin ingin menghapus koneksi '{{ $conn['name'] }}'? Seluruh pipeline terkait akan terpengaruh."
                                    class="text-red-500 hover:text-red-600 text-xs font-semibold"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-8 text-center text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                        <p class="text-xs font-medium">Belum ada koneksi yang terdaftar. Klik button di kanan atas untuk membuat koneksi pertama Anda.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Metadata Viewer Detail Panel (Right Side) -->
        <div class="lg:col-span-1">
            @if($selectedMetadata)
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4 sticky top-6 animate-in fade-in slide-in-from-right-4 duration-200">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 uppercase tracking-wider font-mono">
                                Profiling Skema Metadata
                            </span>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-sm mt-2 font-mono">
                                {{ $selectedMetadata['name'] }}
                            </h3>
                        </div>
                        <button wire:click="closeMetadata" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">&times;</button>
                    </div>

                    @if($selectedMetadata['driver'] === 'sharepoint')
                        <!-- SharePoint Metadata -->
                        <div class="space-y-4 text-xs">
                            <div>
                                <span class="text-slate-400 font-bold block mb-1">Daftar Folder SharePoint:</span>
                                <div class="space-y-1 bg-slate-50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-lg p-2.5">
                                    @foreach($selectedMetadata['metadata']['folders'] ?? [] as $f)
                                        <div class="flex justify-between items-center font-mono text-[10px]">
                                            <span class="text-slate-600 dark:text-slate-350 truncate">📂 {{ $f['name'] }}</span>
                                            <span class="text-slate-400">{{ $f['files_count'] }} File</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <span class="text-slate-400 font-bold block mb-1">Daftar File yang Terbaca:</span>
                                <div class="space-y-2 max-h-60 overflow-y-auto">
                                    @foreach($selectedMetadata['metadata']['files'] ?? [] as $f)
                                        <div class="bg-slate-50 dark:bg-[#161A25]/55 border border-slate-100 dark:border-slate-800 rounded-lg p-2.5">
                                            <div class="font-bold text-slate-700 dark:text-slate-300 font-mono text-[10px]">📄 {{ $f['name'] }}</div>
                                            <div class="text-[9px] text-slate-400 mt-1 flex justify-between">
                                                <span>Ukuran: {{ $f['size'] }}</span>
                                                <span>Modifikasi: {{ $f['last_modified'] }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Database & File Schema Metadata -->
                        <div class="space-y-4 text-xs">
                            <span class="text-slate-400 font-bold block mb-1">Daftar Tabel & Kolom Terdaftar:</span>
                            <div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">
                                @foreach($selectedMetadata['metadata']['tables'] ?? [] as $tbl)
                                    <div class="bg-slate-50 dark:bg-[#161A25]/50 border border-slate-100 dark:border-slate-800 rounded-xl p-3 space-y-2">
                                        <div class="flex justify-between items-center">
                                            <strong class="text-slate-800 dark:text-slate-200 font-mono text-[11px]">📊 {{ $tbl['name'] }}</strong>
                                            <span class="text-[9px] px-1.5 py-0.2 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 rounded-full font-bold">
                                                {{ number_format($tbl['row_count']) }} baris
                                            </span>
                                        </div>
                                        <div class="pt-2 border-t border-slate-200/50 dark:border-slate-800 flex flex-wrap gap-1">
                                            @foreach($tbl['columns'] ?? [] as $col)
                                                <code class="text-[9px] font-mono bg-white dark:bg-[#1F2535] px-1.5 py-0.5 rounded border border-slate-150 dark:border-slate-800 text-slate-600 dark:text-slate-400">{{ $col }}</code>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-slate-50/50 dark:bg-[#12151E]/30 border border-slate-200/60 dark:border-[#222735]/40 border-dashed rounded-xl p-12 text-center text-slate-400 text-xs">
                    <p>Klik tombol <strong>"Lihat Skema"</strong> di salah satu kartu koneksi untuk menampilkan rincian struktur skema metadata otomatis.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Create/Edit Modal Dialog -->
    @if($showModal)
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-150 dark:border-[#222735] flex justify-between items-center">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        {{ $isEditing ? 'Ubah Koneksi Data' : 'Tambah Koneksi Data Baru' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>

                <!-- Modal Body Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Koneksi</label>
                            <input 
                                type="text" 
                                wire:model="name"
                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                                placeholder="Contoh: Oracle Finance ERP"
                            />
                            @error('name') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tipe Koneksi</label>
                            <select 
                                wire:model.live="type"
                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                            >
                                <option value="Database">Database</option>
                                <option value="File Source">File Source</option>
                                <option value="Collaboration Platform">Collaboration Platform</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Driver</label>
                            <select 
                                wire:model.live="driver"
                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                            >
                                @if($type === 'Database')
                                    <option value="pgsql">PostgreSQL</option>
                                    <option value="mysql">MySQL</option>
                                    <option value="oracle">Oracle Database</option>
                                @elseif($type === 'File Source')
                                    <option value="csv">CSV File</option>
                                    <option value="excel">Excel Spreadsheet</option>
                                @else
                                    <option value="sharepoint">SharePoint Server</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <!-- Config Parameters based on Driver type -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Parameter Konfigurasi</h4>

                        @if(in_array($driver, ['pgsql', 'mysql', 'oracle']))
                            <!-- Database Credentials Form -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase">Host Server</label>
                                    <input type="text" wire:model="config.host" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="127.0.0.1 atau domain" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase">Port</label>
                                    <input type="text" wire:model="config.port" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="{{ $driver==='pgsql'?'5432':($driver==='mysql'?'3306':'1521') }}" />
                                </div>
                                <div class="col-span-3 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase">Database Name / SID</label>
                                    <input type="text" wire:model="config.database" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="erp_prod" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase">Username</label>
                                    <input type="text" wire:model="config.username" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="postgres" />
                                </div>
                                <div class="col-span-2 space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase">Password</label>
                                    <input type="password" wire:model="config.password" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="••••••••" />
                                </div>
                            </div>
                        @elseif($driver === 'sharepoint')
                            <!-- SharePoint Form -->
                            <div class="space-y-3">
                                <div class="space-y-1">
                                    <label class="text-[9px] text-slate-400 uppercase">SharePoint Site Folder URL</label>
                                    <input type="text" wire:model="config.folder_url" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="https://enterprise.sharepoint.com/teams/..." />
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <label class="text-[9px] text-slate-400 uppercase">Client ID</label>
                                        <input type="text" wire:model="config.client_id" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="UUID" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[9px] text-slate-400 uppercase">Client Secret</label>
                                        <input type="password" wire:model="config.client_secret" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="••••••••" />
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- File Local Path Form -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase">Local File Path (Staging Directory)</label>
                                <input type="text" wire:model="config.file_path" class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs dark:text-white" placeholder="Contoh: /data/uploads/sales.csv" />
                            </div>
                        @endif
                    </div>

                    <!-- Test Connection status panel -->
                    @if($testResult)
                        <div class="p-3 rounded-lg text-xs font-semibold flex items-center gap-1.5 {{ $testResult === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20' : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-500/20' }}">
                            @if($testResult === 'success')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Koneksi Berhasil! Parameter valid dan konektivitas aktif.</span>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Koneksi Gagal. Periksa alamat host, port, atau kredensial login Anda.</span>
                            @endif
                        </div>
                    @endif

                    <!-- Footer Actions -->
                    <div class="flex justify-between items-center pt-3 border-t border-slate-150 dark:border-[#222735]">
                        <button 
                            type="button" 
                            wire:click="testConnection"
                            class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 disabled:opacity-50"
                            {{ $isTesting ? 'disabled' : '' }}
                        >
                            @if($isTesting)
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                Sedang Ping...
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                Test Koneksi
                            @endif
                        </button>

                        <div class="flex gap-2">
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
                                Simpan Koneksi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
