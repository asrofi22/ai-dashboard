<div class="bg-white dark:bg-[#12151E] shadow-sm rounded-xl border border-slate-200 dark:border-[#222735] flex flex-col h-full overflow-hidden">
    <!-- Header & Filters -->
    <div class="p-5 border-b border-slate-200 dark:border-[#222735] bg-white dark:bg-[#12151E] flex flex-col sm:flex-row sm:items-center justify-between gap-4 sticky top-0 z-10">
        <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Tinjauan Kandidat Duplikat</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Filter, analisis, dan tindak lanjuti potensi duplikasi.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama proyek..." class="pl-9 pr-4 py-2 w-full sm:w-64 bg-slate-50 dark:bg-[#1C212E] border border-slate-200 dark:border-[#2A303F] text-sm rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white dark:placeholder-slate-500 transition-shadow outline-none">
            </div>

            <select wire:model.live="batchId" class="py-2 pl-3 pr-8 bg-slate-50 dark:bg-[#1C212E] border border-slate-200 dark:border-[#2A303F] text-sm rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white outline-none appearance-none font-medium text-slate-700 dark:text-slate-300">
                <option value="">Batch: Semua</option>
                @foreach($batches as $batch)
                    <option value="{{ $batch->id }}">Batch #{{ $batch->id }} ({{ $batch->sourceConnection->name }})</option>
                @endforeach
            </select>
            
            <select wire:model.live="statusFilter" class="py-2 pl-3 pr-8 bg-slate-50 dark:bg-[#1C212E] border border-slate-200 dark:border-[#2A303F] text-sm rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white outline-none appearance-none font-medium text-slate-700 dark:text-slate-300">
                <option value="">Status: Semua</option>
                <option value="pending">Menunggu Keputusan</option>
                <option value="confirmed">Dikonfirmasi (Duplikat)</option>
                <option value="rejected">Ditolak (Bukan Duplikat)</option>
            </select>
        </div>
    </div>

    <!-- Table Content -->
    <div class="overflow-x-auto flex-1">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-[#222735] border-collapse">
            <thead class="bg-slate-50/50 dark:bg-[#1C212E]/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Entitas A</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Entitas B</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Skor Kemiripan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-48">Opini AI</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-[#12151E] divide-y divide-slate-100 dark:divide-[#1C212E]">
                @forelse ($candidates as $candidate)
                    <tr class="hover:bg-slate-50 dark:hover:bg-[#1C212E]/50 transition-colors group">
                        
                        <!-- Project A -->
                        <td class="px-6 py-4 relative">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white leading-snug">{{ $candidate->projectA->original_name }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate max-w-xs font-mono bg-slate-100 dark:bg-[#1C212E] py-0.5 px-1.5 rounded">{{ $candidate->projectA->normalized_name }}</span>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $candidate->projectA->sourceConnection->name }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Project B -->
                        <td class="px-6 py-4 relative">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900 dark:text-white leading-snug">{{ $candidate->projectB->original_name }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 truncate max-w-xs font-mono bg-slate-100 dark:bg-[#1C212E] py-0.5 px-1.5 rounded">{{ $candidate->projectB->normalized_name }}</span>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $candidate->projectB->sourceConnection->name }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Score -->
                        <td class="px-6 py-4 align-top pt-5">
                            <div class="flex flex-col gap-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($candidate->similarity_score * 100, 1) }}%</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-tighter bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">Batch #{{ $candidate->import_log_id }}</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                                    @php $scorePct = min($candidate->similarity_score * 100, 100); @endphp
                                    <div class="h-1.5 rounded-full {{ $candidate->confidence_level == 'high' ? 'bg-red-500' : ($candidate->confidence_level == 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $scorePct }}%"></div>
                                </div>
                                @if($candidate->confidence_level == 'high')
                                    <span class="text-[11px] font-medium text-red-600 dark:text-red-400 mt-1 uppercase tracking-wider">Risiko Tinggi</span>
                                @elseif($candidate->confidence_level == 'medium')
                                    <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400 mt-1 uppercase tracking-wider">Risiko Menengah</span>
                                @else
                                    <span class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 mt-1 uppercase tracking-wider">Risiko Rendah</span>
                                @endif
                            </div>
                        </td>
                        
                        <!-- AI Validation -->
                        <td class="px-6 py-4 align-top pt-5">
                            @if($candidate->aiValidationLog)
                                @php
                                    $res = $candidate->aiValidationLog->result;
                                    if ($res === 'SAME') {
                                        $badgeBg = 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/30';
                                        $icon = '<svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
                                    } elseif ($res === 'POSSIBLY') {
                                        $badgeBg = 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/30';
                                        $icon = '<svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                    } else {
                                        $badgeBg = 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/30';
                                        $icon = '<svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                                    }
                                @endphp
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {!! $badgeBg !!}">
                                            {!! $icon !!}
                                            {{ $res === 'SAME' ? 'SAMA' : ($res === 'POSSIBLY' ? 'MUNGKIN SAMA' : 'BERBEDA') }}
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">({{ number_format($candidate->aiValidationLog->confidence_score * 100, 0) }}%)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-tight border-l-2 border-slate-200 dark:border-slate-700 pl-2">
                                        {{ $candidate->aiValidationLog->reasoning }}
                                    </p>
                                </div>
                            @else
                                <div class="flex flex-col gap-2">
                                    <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Belum Dianalisis</span>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 italic">Klik "Validasi AI" untuk menjalankan analisis mendalam.</p>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-4 align-top pt-5 text-right">
                            <div class="flex flex-col gap-2 items-end">
                                @if(!$candidate->aiValidationLog)
                                    <button 
                                        wire:click="validateWithAi({{ $candidate->id }})" 
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:text-indigo-300 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 border border-indigo-200 dark:border-indigo-500/30 transition-colors w-28 shadow-sm"
                                    >
                                        <svg wire:loading.remove wire:target="validateWithAi({{ $candidate->id }})" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                        <svg wire:loading wire:target="validateWithAi({{ $candidate->id }})" class="animate-spin w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <span wire:loading.remove wire:target="validateWithAi({{ $candidate->id }})">Validasi AI</span>
                                        <span wire:loading wire:target="validateWithAi({{ $candidate->id }})">Memproses..</span>
                                    </button>
                                @endif
                                <button 
                                    wire:click="openDetail({{ $candidate->id }})"
                                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 dark:bg-transparent dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-800 transition-colors w-28 shadow-sm"
                                >
                                    Tinjau Detail
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-50 dark:bg-[#1C212E] rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Tidak Ada Kandidat</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm">Data master Anda terlihat bersih atau tidak ada data yang cocok dengan filter saat ini.</p>
                                <button onclick="document.querySelector('[x-data]').__x.$data.activeTab = 'upload'" class="mt-4 px-4 py-2 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 transition-colors">
                                    Mulai Impor Data
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($candidates->hasPages())
        <div class="px-6 py-4 border-t border-slate-200 dark:border-[#222735] bg-slate-50 dark:bg-[#1C212E]/50">
            {{ $candidates->links() }}
        </div>
    @endif

    {{-- ======================== DETAIL MODAL ======================== --}}
    @if($showModal && $selectedCandidate)
    @teleport('body')
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md transition-opacity cursor-pointer" wire:click="closeModal"></div>

        <!-- Modal Panel -->
        <div class="relative w-full max-w-4xl bg-white dark:bg-[#12151E] rounded-3xl shadow-2xl transform transition-all border border-slate-200 dark:border-[#222735] z-10 overflow-hidden my-auto">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-slate-200 dark:border-[#222735] flex items-center justify-between bg-slate-50 dark:bg-[#1C212E]/50">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-indigo-600/20 dark:border-indigo-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Detail Perbandingan Data</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Verifikasi manual dan hasil analisis AI.</p>
                    </div>
                </div>
                <button wire:click="closeModal" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body (Scrollable if content is long) -->
            <div class="p-6 max-h-[75vh] overflow-y-auto custom-scrollbar">
                <!-- Score & Batch Banner -->
                <div class="mb-8 p-5 rounded-2xl bg-gradient-to-r from-indigo-500/5 to-purple-500/5 dark:from-indigo-500/10 dark:to-purple-500/10 border border-indigo-500/20 dark:border-indigo-500/30 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400 leading-none">{{ number_format($selectedCandidate->similarity_score * 100, 1) }}%</div>
                            <div class="text-[10px] uppercase font-bold text-indigo-400 dark:text-indigo-500 tracking-widest mt-1">Similarity</div>
                        </div>
                        <div class="h-12 w-px bg-indigo-200 dark:bg-indigo-500/20"></div>
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide {{ $selectedCandidate->confidence_level == 'high' ? 'bg-red-500/10 text-red-600' : 'bg-amber-500/10 text-amber-600' }}">
                                <span class="w-2 h-2 rounded-full {{ $selectedCandidate->confidence_level == 'high' ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                                {{ $selectedCandidate->confidence_level == 'high' ? 'Risiko Tinggi' : 'Risiko Menengah' }}
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 font-medium">Algoritma mendeteksi kesamaan karakter yang signifikan.</p>
                        </div>
                    </div>
                    <div class="px-4 py-2 rounded-xl bg-white dark:bg-[#1C212E] border border-slate-200 dark:border-slate-700 shadow-sm">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">ID Batch Impor</span>
                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">#{{ $selectedCandidate->import_log_id }}</span>
                    </div>
                </div>

                <!-- Side-by-Side Comparison -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Entity A -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                            <span class="w-6 h-6 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 border border-slate-200 dark:border-slate-700">A</span>
                            Data Existing (Master)
                        </div>
                        <div class="p-6 rounded-2xl border border-slate-200 dark:border-[#2A303F] bg-slate-50/30 dark:bg-[#0B0D13]/20 space-y-5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5 tracking-wider">Nama Original</label>
                                <div class="text-base font-bold text-slate-900 dark:text-white leading-tight break-words">{{ $selectedCandidate->projectA->original_name }}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5 tracking-wider">Nama Dinormalisasi</label>
                                <div class="text-xs font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-500/5 p-3 rounded-xl border border-indigo-500/10">{{ $selectedCandidate->projectA->normalized_name }}</div>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">ID Proyek</label>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">#{{ $selectedCandidate->projectA->id }}</div>
                                </div>
                                <div class="text-right">
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-0.5">Sumber Data</label>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $selectedCandidate->projectA->sourceConnection->name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Entity B -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                            <span class="w-6 h-6 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">B</span>
                            Data Incoming (Baru)
                        </div>
                        <div class="p-6 rounded-2xl border border-indigo-200 dark:border-indigo-500/20 bg-indigo-50/20 dark:bg-indigo-500/5 space-y-5 shadow-inner">
                            <div>
                                <label class="block text-[10px] font-bold text-indigo-400 dark:text-indigo-500 uppercase mb-1.5 tracking-wider">Nama Original</label>
                                <div class="text-base font-bold text-slate-900 dark:text-white leading-tight break-words">{{ $selectedCandidate->projectB->original_name }}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-indigo-400 dark:text-indigo-500 uppercase mb-1.5 tracking-wider">Nama Dinormalisasi</label>
                                <div class="text-xs font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-500/5 p-3 rounded-xl border border-indigo-500/10">{{ $selectedCandidate->projectB->normalized_name }}</div>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-indigo-200/50 dark:border-indigo-500/20">
                                <div>
                                    <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-0.5">ID Proyek</label>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">#{{ $selectedCandidate->projectB->id }}</div>
                                </div>
                                <div class="text-right">
                                    <label class="block text-[10px] font-bold text-indigo-400 uppercase mb-0.5">Sumber Data</label>
                                    <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $selectedCandidate->projectB->sourceConnection->name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Semantic Insights -->
                <div class="mt-8 border-t border-slate-200 dark:border-[#222735] pt-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-slate-900 dark:text-white">Analisis Validasi AI Gemini</h4>
                        </div>
                        @if(!$selectedCandidate->aiValidationLog)
                            <button wire:click="validateWithAi({{ $selectedCandidate->id }})" wire:loading.attr="disabled" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-md shadow-indigo-500/20 flex items-center gap-2">
                                <span wire:loading.remove wire:target="validateWithAi">Analisis Sekarang</span>
                                <span wire:loading wire:target="validateWithAi" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Memproses...
                                </span>
                            </button>
                        @endif
                    </div>

                    @if($selectedCandidate->aiValidationLog)
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-1 p-6 rounded-2xl border border-slate-200 dark:border-[#2A303F] bg-white dark:bg-[#1C212E] flex flex-col items-center justify-center text-center shadow-sm">
                                @php
                                    $res = $selectedCandidate->aiValidationLog->result;
                                    $conf = $selectedCandidate->aiValidationLog->confidence_score * 100;
                                @endphp
                                <span class="text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-widest">Kesimpulan AI</span>
                                <div class="px-4 py-1.5 rounded-full text-sm font-black tracking-wide mb-4 {{ $res === 'SAME' ? 'bg-red-500/10 text-red-500' : ($res === 'POSSIBLY' ? 'bg-amber-500/10 text-amber-500' : 'bg-emerald-500/10 text-emerald-500') }}">
                                    {{ $res === 'SAME' ? 'DUPLIKAT' : ($res === 'POSSIBLY' ? 'MUNGKIN' : 'BERBEDA') }}
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mb-2">
                                    <div class="h-1.5 rounded-full {{ $res === 'SAME' ? 'bg-red-500' : ($res === 'POSSIBLY' ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $conf }}%"></div>
                                </div>
                                <div class="text-[10px] font-bold text-slate-500 tracking-wide">{{ number_format($conf, 0) }}% Confidence</div>
                            </div>
                            <div class="md:col-span-3 p-6 rounded-2xl border border-slate-200 dark:border-[#2A303F] bg-white dark:bg-[#1C212E] shadow-sm">
                                <span class="text-[10px] font-bold text-slate-400 uppercase mb-3 tracking-widest block">Analisis & Pertimbangan</span>
                                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                                    "{{ $selectedCandidate->aiValidationLog->reasoning }}"
                                </p>
                                <div class="mt-4 flex items-center gap-2 text-[10px] text-slate-400 italic">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Divalidasi menggunakan Gemini 1.5 Flash
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-10 rounded-3xl border-2 border-dashed border-slate-200 dark:border-[#2A303F] text-center bg-slate-50/50 dark:bg-[#12151E]/50">
                            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium max-w-sm mx-auto">Klik tombol "Analisis Sekarang" untuk mendapatkan insight cerdas tentang kesamaan semantik kedua proyek ini.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-6 border-t border-slate-200 dark:border-[#222735] bg-slate-50 dark:bg-[#1C212E] flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span class="text-xs font-bold uppercase tracking-widest">Pusat Resolusi Data</span>
                </div>
                <div class="flex items-center gap-4 w-full sm:w-auto">
                    <button wire:click="resolveAsNotDuplicate" class="flex-1 sm:flex-none px-8 py-3 rounded-2xl text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 dark:bg-transparent dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-800 transition-all shadow-sm">
                        Bukan Duplikat
                    </button>
                    <button wire:click="resolveAsDuplicate" class="flex-1 sm:flex-none px-8 py-3 rounded-2xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xl shadow-indigo-600/20 transition-all transform active:scale-95">
                        Konfirmasi Duplikat
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endteleport
    @endif

</div>
