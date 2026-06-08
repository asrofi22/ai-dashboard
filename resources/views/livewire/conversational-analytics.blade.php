<div class="bg-white dark:bg-[#12151E] shadow-sm rounded-xl border border-slate-200 dark:border-[#222735] flex flex-col h-[680px] overflow-hidden relative">

    <!-- Header -->
    <div class="p-4 border-b border-slate-200 dark:border-[#222735] bg-white dark:bg-[#12151E] z-10 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-500/20">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">AI Data Analyst</h2>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Online &amp; Dapat Query Database Langsung</span>
                </div>
            </div>
        </div>
        <!-- Capability badge -->
        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8M8 8h5M8 16h3"></path></svg>
            SQL Live Query
        </span>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 p-5 overflow-y-auto space-y-5 bg-slate-50/50 dark:bg-[#0B0D13]/50 relative scroll-smooth" id="chat-container">

        <!-- Welcome Pattern Background -->
        <div class="absolute inset-0 z-0 opacity-[0.03] dark:opacity-[0.02] pointer-events-none" style="background-image: radial-gradient(#6366f1 1px, transparent 1px); background-size: 24px 24px;"></div>

        <div class="relative z-10 space-y-5">
            @foreach($messages as $idx => $msg)
                @if($msg['role'] === 'assistant')
                    <div class="flex items-start gap-3 max-w-[90%]">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-800 mt-1">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div class="space-y-2 flex-1">
                            <!-- AI Message Bubble -->
                            <div class="bg-white dark:bg-[#1C212E] p-4 rounded-2xl rounded-tl-sm border border-slate-200 dark:border-[#2A303F] shadow-sm text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                                {!! Str::markdown($msg['content']) !!}
                            </div>

                            {{-- SQL Result Table (if this message has a query result) --}}
                            @if(isset($msg['has_result']) && isset($queryResults[$msg['has_result']]))
                                @php $qr = $queryResults[$msg['has_result']]; @endphp
                                <div class="rounded-xl border border-slate-200 dark:border-[#2A303F] overflow-hidden shadow-sm">
                                    <!-- SQL Header -->
                                    <div class="flex items-center justify-between px-3 py-2 bg-slate-800 dark:bg-[#0E1118]">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"></path></svg>
                                            <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">SQL Dieksekusi</span>
                                        </div>
                                        @if(!empty($qr['rows']))
                                            <span class="text-[10px] text-slate-400 font-mono">{{ count($qr['rows']) }} baris dikembalikan</span>
                                        @endif
                                    </div>
                                    <!-- SQL Code -->
                                    <div class="bg-slate-900 dark:bg-[#080A10] px-3 py-2 overflow-x-auto">
                                        <code class="text-[11px] text-indigo-300 font-mono whitespace-pre-wrap">{{ $qr['sql'] }}</code>
                                    </div>

                                    @if(!empty($qr['error']))
                                        <div class="px-3 py-2 bg-red-50 dark:bg-red-900/10 text-xs text-red-600 dark:text-red-400 font-mono">
                                            ⚠️ {{ $qr['error'] }}
                                        </div>
                                    @elseif(empty($qr['rows']))
                                        <div class="px-3 py-2 bg-slate-50 dark:bg-[#0E1118] text-xs text-slate-500 dark:text-slate-400">
                                            Tidak ada data yang dikembalikan.
                                        </div>
                                    @else
                                        <!-- Data Table -->
                                        <div class="overflow-x-auto max-h-[220px] overflow-y-auto">
                                            <table class="min-w-full text-[11px] divide-y divide-slate-200 dark:divide-slate-800">
                                                <thead class="bg-slate-100 dark:bg-[#161A25] sticky top-0 z-10">
                                                    <tr>
                                                        @foreach($qr['cols'] as $col)
                                                            <th class="px-3 py-2 text-left font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider whitespace-nowrap">
                                                                {{ $col }}
                                                            </th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50 bg-white dark:bg-[#12151E] font-mono">
                                                    @foreach($qr['rows'] as $row)
                                                        <tr class="hover:bg-slate-50 dark:hover:bg-[#1C212E]/50 transition-colors">
                                                            @foreach($row as $cell)
                                                                <td class="px-3 py-1.5 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                                                    {{ is_null($cell) ? '—' : (is_array($cell) ? json_encode($cell) : $cell) }}
                                                                </td>
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex items-end gap-3 flex-row-reverse max-w-[85%] ml-auto">
                        <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center shrink-0 border border-slate-300 dark:border-slate-600">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-300">AD</span>
                        </div>
                        <div class="bg-indigo-600 dark:bg-indigo-500 p-4 rounded-2xl rounded-br-sm shadow-sm text-sm text-white leading-relaxed">
                            {{ $msg['content'] }}
                        </div>
                    </div>
                @endif
            @endforeach

            <!-- Loading State -->
            <div wire:loading wire:target="ask" class="flex items-start gap-3 max-w-[90%]">
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-800 mt-1">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="space-y-1.5">
                    <div class="bg-white dark:bg-[#1C212E] py-4 px-5 rounded-2xl rounded-tl-sm border border-slate-200 dark:border-[#2A303F] shadow-sm flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                    <span class="text-[10px] text-slate-400 ml-1 italic">Menganalisis &amp; menjalankan query...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggested Prompts (Chips) -->
    <div class="px-4 py-3 bg-white dark:bg-[#12151E] border-t border-slate-100 dark:border-[#222735] flex overflow-x-auto gap-2 scrollbar-hide shrink-0">
        <button wire:click="$set('query', 'Ringkas tren duplikat'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">✨ Ringkas tren duplikat</button>
        <button wire:click="$set('query', 'Sumber mana paling banyak masalah?'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">📊 Sumber paling banyak masalah</button>
        <button wire:click="$set('query', 'Tampilkan 10 record pertama dari tabel orders'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">🗄️ Query tabel orders</button>
        <button wire:click="$set('query', 'Pipeline mana yang sering gagal?'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">⚠️ Pipeline sering gagal</button>
        <button wire:click="$set('query', 'Risiko Tinggi'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">🎯 Risiko Tinggi</button>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white dark:bg-[#12151E] shrink-0 border-t border-slate-100 dark:border-[#222735]">
        <form wire:submit.prevent="ask" class="relative flex items-center gap-2">
            <div class="relative flex-1">
                <input
                    type="text"
                    wire:model="query"
                    placeholder="Tanya data aktual, e.g. 'Customer mana yang punya saldo tertinggi?'"
                    class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-200 dark:border-[#2A303F] rounded-xl pl-4 pr-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white dark:placeholder-slate-500 outline-none transition-shadow"
                >
            </div>
            <button
                type="submit"
                class="p-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50 shrink-0 shadow-sm"
                wire:loading.attr="disabled"
            >
                <svg class="w-4 h-4" wire:loading.remove wire:target="ask" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                <svg class="w-4 h-4 animate-spin" wire:loading wire:target="ask" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
            </button>
        </form>
        <p class="text-[10px] text-center text-slate-400 mt-2">AI dapat menjalankan SELECT query langsung. Verifikasi hasil sebelum mengambil keputusan.</p>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', ({ component, el }) => {
                const container = document.getElementById('chat-container');
                if (container) container.scrollTop = container.scrollHeight;
            });
        });
    </script>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        /* Markdown prose styling */
        #chat-container p { margin-bottom: 0.5rem; }
        #chat-container ul { list-style: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
        #chat-container ol { list-style: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
        #chat-container strong { font-weight: 700; }
        #chat-container code { font-family: monospace; background: rgba(99,102,241,0.1); padding: 1px 4px; border-radius: 4px; font-size: 0.85em; }
        #chat-container pre { background: rgba(0,0,0,0.1); padding: 0.5rem; border-radius: 0.5rem; overflow-x: auto; }
    </style>
</div>
