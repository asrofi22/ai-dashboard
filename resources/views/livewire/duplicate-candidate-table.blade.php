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
                                <button class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 dark:bg-transparent dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-800 transition-colors w-28 shadow-sm">
                                    Tinjau Manual
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
</div>
