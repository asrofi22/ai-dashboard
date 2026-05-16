<div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-6 lg:p-8 max-w-4xl mx-auto">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Impor Data</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pilih sumber dan unggah file untuk analisis duplikasi.</p>
        </div>
        <div class="hidden sm:flex items-center gap-2">
            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold">1</span>
            <span class="h-0.5 w-4 bg-slate-200 dark:bg-slate-700"></span>
            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold">2</span>
            <span class="h-0.5 w-4 bg-slate-200 dark:bg-slate-700"></span>
            <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold">3</span>
        </div>
    </div>

    <!-- Step 1: Source Selection (Simulated as cards instead of dropdown) -->
    <div class="mb-8">
        <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">1. Pilih Sumber Data</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($sources as $source)
                <div 
                    wire:click="$set('sourceId', {{ $source->id }})"
                    class="cursor-pointer rounded-lg border-2 p-4 text-center transition-all duration-200 relative
                    {{ $sourceId == $source->id 
                        ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/10 dark:border-indigo-500' 
                        : 'border-slate-200 dark:border-[#222735] hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:bg-slate-50 dark:hover:bg-[#1C212E]' 
                    }}"
                >
                    @if($sourceId == $source->id)
                        <div class="absolute -top-2 -right-2 bg-indigo-600 text-white rounded-full p-0.5 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                    @endif
                    
                    <div class="mx-auto w-10 h-10 mb-3 flex items-center justify-center rounded-full bg-white dark:bg-[#12151E] shadow-sm border border-slate-100 dark:border-slate-800 text-indigo-600 dark:text-indigo-400">
                        @if(strtolower($source->type) == 'excel' || strtolower($source->type) == 'csv')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        @elseif(strtolower($source->type) == 'database' || strtolower($source->type) == 'pgsql')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        @endif
                    </div>
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $source->name }}</div>
                    <div class="text-xs text-slate-500 mt-1 uppercase tracking-wider">{{ $source->type }}</div>
                </div>
            @endforeach
        </div>
        @error('sourceId') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <!-- Step 2: Upload Area -->
    <div class="mb-8">
        <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">2. Unggah File (Opsional jika DB Sync)</label>
        
        <div class="relative group">
            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed rounded-xl cursor-pointer transition-colors
                {{ $file 
                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-500/5 dark:border-emerald-500/50' 
                    : 'border-slate-300 dark:border-[#3E4557] bg-slate-50 dark:bg-[#161A25] hover:bg-slate-100 dark:hover:bg-[#1C212E] hover:border-indigo-400 dark:hover:border-indigo-500' 
                }}">
                
                @if ($file)
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="w-16 h-16 mb-4 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-400">{{ $file->getClientOriginalName() }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">Siap untuk diproses</p>
                        <button type="button" wire:click="$set('file', null)" class="mt-4 text-xs font-medium text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition-colors">
                            Batal atau pilih file lain
                        </button>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-center px-4">
                        <div class="w-16 h-16 mb-4 rounded-full bg-white dark:bg-[#12151E] shadow-sm flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        </div>
                        <p class="mb-2 text-sm text-slate-700 dark:text-slate-300"><span class="font-bold text-indigo-600 dark:text-indigo-400">Klik untuk mencari file</span> atau seret file ke sini</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Hanya mendukung format CSV dan Excel (Maks. 50MB)</p>
                    </div>
                @endif
                <input id="dropzone-file" type="file" class="hidden" wire:model="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" />
            </label>
        </div>
        @error('file') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <!-- Status Messages -->
    @if($successMessage)
        <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 dark:bg-emerald-500/10 dark:border-emerald-500/20 flex items-start gap-3">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <h4 class="text-sm font-bold text-emerald-800 dark:text-emerald-400">Impor Selesai!</h4>
                <p class="text-sm text-emerald-700 dark:text-emerald-500 mt-1">{{ $successMessage }}</p>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20 flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <h4 class="text-sm font-bold text-red-800 dark:text-red-400">Gagal Mengimpor</h4>
                <p class="text-sm text-red-700 dark:text-red-500 mt-1">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    <!-- Step 3: Submit -->
    <div class="flex items-center justify-between pt-6 border-t border-slate-200 dark:border-[#222735]">
        <div class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Data akan otomatis dibersihkan dan dianalisis untuk duplikasi.
        </div>
        <button 
            wire:click="processUpload" 
            wire:loading.attr="disabled"
            class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-sm"
        >
            <span wire:loading.remove wire:target="processUpload">Mulai Proses ETL</span>
            <span wire:loading wire:target="processUpload" class="flex items-center gap-2">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memproses Data...
            </span>
        </button>
    </div>
</div>
