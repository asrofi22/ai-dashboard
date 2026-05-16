<div class="max-w-5xl mx-auto">

    {{-- ======================== STEP: UPLOAD ======================== --}}
    @if ($step === 'upload')
    <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] p-6 lg:p-8">

        <!-- Header with step indicator -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Impor Data Baru</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">File akan dipreview terlebih dahulu — <strong class="text-indigo-600 dark:text-indigo-400">belum disimpan ke database</strong> sampai Anda konfirmasi.</p>
            </div>
            <div class="hidden sm:flex items-center gap-2 text-xs font-semibold">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-600 text-white">1</span>
                <span class="text-slate-400">Unggah</span>
                <span class="h-px w-6 bg-slate-200 dark:bg-slate-700"></span>
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500">2</span>
                <span class="text-slate-400">Preview</span>
                <span class="h-px w-6 bg-slate-200 dark:bg-slate-700"></span>
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500">3</span>
                <span class="text-slate-400">Konfirmasi</span>
            </div>
        </div>

        <!-- Source Selection -->
        <div class="mb-8">
            <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">1. Pilih Sumber Data</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @foreach($sources as $source)
                    <div
                        wire:click="$set('sourceId', {{ $source['id'] }})"
                        class="cursor-pointer rounded-lg border-2 p-4 text-center transition-all duration-200 relative
                        {{ $sourceId == $source['id']
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/10 dark:border-indigo-500'
                            : 'border-slate-200 dark:border-[#222735] hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:bg-slate-50 dark:hover:bg-[#1C212E]'
                        }}"
                    >
                        @if($sourceId == $source['id'])
                            <div class="absolute -top-2 -right-2 bg-indigo-600 text-white rounded-full p-0.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                        @endif
                        <div class="mx-auto w-9 h-9 mb-2 flex items-center justify-center rounded-full bg-white dark:bg-[#12151E] shadow-sm border border-slate-100 dark:border-slate-800 text-indigo-600 dark:text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $source['name'] }}</div>
                        <div class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">{{ $source['type'] }}</div>
                    </div>
                @endforeach
            </div>
            @error('sourceId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Upload Area -->
        <div class="mb-8">
            <label class="block text-sm font-semibold text-slate-900 dark:text-white mb-3">2. Pilih File</label>
            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-44 border-2 border-dashed rounded-xl cursor-pointer transition-colors
                {{ $file
                    ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-500/5 dark:border-emerald-500/50'
                    : 'border-slate-300 dark:border-[#3E4557] bg-slate-50 dark:bg-[#161A25] hover:bg-slate-100 dark:hover:bg-[#1C212E] hover:border-indigo-400' }}">
                @if($file)
                    <div class="flex flex-col items-center justify-center py-5 text-center">
                        <div class="w-14 h-14 mb-3 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-400">{{ $file->getClientOriginalName() }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">Siap untuk dipreview — belum disimpan</p>
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-5 px-4 text-center">
                        <div class="w-14 h-14 mb-3 rounded-full bg-white dark:bg-[#12151E] shadow-sm flex items-center justify-center text-indigo-500">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300"><span class="font-bold text-indigo-600 dark:text-indigo-400">Klik</span> atau seret file ke sini</p>
                        <p class="text-xs text-slate-400 mt-1">CSV atau Excel · Maks. 50MB</p>
                    </div>
                @endif
                <input id="dropzone-file" type="file" class="hidden" wire:model="file" accept=".csv,.xlsx,.xls,.txt" />
            </label>
            @error('file') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if($errorMessage)
            <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
            </div>
        @endif

        <div class="flex items-center justify-between pt-6 border-t border-slate-200 dark:border-[#222735]">
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Data hanya akan disimpan ke database setelah Anda mengkonfirmasi di langkah berikutnya.
            </div>
            <button wire:click="previewFile" wire:loading.attr="disabled"
                class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:opacity-50 flex items-center gap-2 shadow-sm">
                <span wire:loading.remove wire:target="previewFile">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    Preview Data
                </span>
                <span wire:loading wire:target="previewFile" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Membaca file...
                </span>
            </button>
        </div>
    </div>
    @endif

    {{-- ======================== STEP: PREVIEW ======================== --}}
    @if ($step === 'preview')
    <div class="space-y-5">

        <!-- Preview Header Card -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-amber-300 dark:border-amber-500/30 p-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white">Preview Data — Belum Disimpan</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($previewTotal) }} baris</span>
                            terdeteksi dari file Anda untuk sumber "<span class="font-semibold">{{ $sourceName }}</span>".
                            @if($hasMore)
                                Menampilkan 20 baris pertama.
                            @endif
                            <br>
                            <span class="text-amber-600 dark:text-amber-400 font-medium">⚠️ Data di bawah ini hanya ditampilkan sementara. Klik "Konfirmasi & Simpan" agar data masuk ke database dan siap dideteksi duplikatnya.</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button wire:click="cancelPreview" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 transition-colors">
                        ✕ Batal
                    </button>
                    <button wire:click="confirmAndImport" wire:loading.attr="disabled"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-500/20 transition-all disabled:opacity-50 flex items-center gap-2 shadow-sm">
                        <span wire:loading.remove wire:target="confirmAndImport">✅ Konfirmasi & Simpan</span>
                        <span wire:loading wire:target="confirmAndImport" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        @if($errorMessage)
            <div class="p-4 rounded-lg bg-red-50 border border-red-200 dark:bg-red-500/10 dark:border-red-500/20 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
            </div>
        @endif

        <!-- Preview Table -->
        <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-slate-200 dark:border-[#222735] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735] text-sm">
                    <thead class="bg-slate-50 dark:bg-[#1C212E]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-10">#</th>
                            @foreach($previewKeys as $key)
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider whitespace-nowrap">{{ $key }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-[#1C212E]">
                        @foreach($previewRows as $i => $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                <td class="px-4 py-3 text-xs text-slate-400 dark:text-slate-500 font-mono">{{ $i + 1 }}</td>
                                @foreach($previewKeys as $key)
                                    <td class="px-4 py-3 text-slate-700 dark:text-slate-300 max-w-[180px] truncate" title="{{ $row[$key] ?? '' }}">{{ $row[$key] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($hasMore)
                <div class="px-4 py-3 bg-slate-50 dark:bg-[#1C212E]/50 border-t border-slate-200 dark:border-[#222735] text-center text-xs text-slate-500 dark:text-slate-400">
                    Menampilkan 20 dari {{ number_format($previewTotal) }} baris. Semua baris akan disimpan saat dikonfirmasi.
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ======================== STEP: DONE ======================== --}}
    @if ($step === 'done')
    <div class="bg-white dark:bg-[#12151E] rounded-xl shadow-sm border border-emerald-300 dark:border-emerald-500/30 p-8 text-center">
        <div class="w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-500/20 flex items-center justify-center mx-auto mb-5">
            <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Data Berhasil Disimpan!</h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 max-w-md mx-auto leading-relaxed">
            {{ $successMessage }}
        </p>
        <div class="mt-6 p-4 bg-indigo-50 dark:bg-indigo-500/10 rounded-lg border border-indigo-200 dark:border-indigo-500/20 text-sm text-indigo-700 dark:text-indigo-300 text-left max-w-md mx-auto">
            <p class="font-semibold mb-1">ℹ️ Catatan Penting:</p>
            <p>Deteksi duplikat hanya membandingkan data <strong>dalam batch ini</strong>. Data dari unggahan sebelumnya tidak akan dicampur.</p>
        </div>
        <button wire:click="startNew" class="mt-6 px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm">
            Unggah File Berikutnya
        </button>
    </div>
    @endif

</div>
