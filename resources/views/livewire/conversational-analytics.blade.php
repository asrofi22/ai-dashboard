<div class="bg-white dark:bg-[#12151E] shadow-sm rounded-xl border border-slate-200 dark:border-[#222735] flex flex-col h-[600px] overflow-hidden relative">
    
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
                    <span class="text-xs text-slate-500 dark:text-slate-400">Online & Siap Membantu</span>
                </div>
            </div>
        </div>
        <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
        </button>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 p-5 overflow-y-auto space-y-6 bg-slate-50/50 dark:bg-[#0B0D13]/50 relative scroll-smooth" id="chat-container">
        
        <!-- Welcome Pattern Background -->
        <div class="absolute inset-0 z-0 opacity-[0.03] dark:opacity-[0.02] pointer-events-none" style="background-image: radial-gradient(#6366f1 1px, transparent 1px); background-size: 24px 24px;"></div>

        <div class="relative z-10 space-y-6">
            @foreach($messages as $msg)
                @if($msg['role'] === 'assistant')
                    <div class="flex items-end gap-3 max-w-[85%]">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-800">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div class="bg-white dark:bg-[#1C212E] p-4 rounded-2xl rounded-bl-sm border border-slate-200 dark:border-[#2A303F] shadow-sm text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                            {!! Str::markdown($msg['content']) !!}
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
            <div wire:loading wire:target="ask" class="flex items-end gap-3 max-w-[85%]">
                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-800">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div class="bg-white dark:bg-[#1C212E] py-4 px-5 rounded-2xl rounded-bl-sm border border-slate-200 dark:border-[#2A303F] shadow-sm flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggested Prompts (Chips) -->
    <div class="px-4 py-3 bg-white dark:bg-[#12151E] border-t border-slate-100 dark:border-[#222735] flex overflow-x-auto gap-2 scrollbar-hide shrink-0">
        <button wire:click="$set('query', 'Ringkas tren duplikat'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">✨ Ringkas tren duplikat</button>
        <button wire:click="$set('query', 'Sumber mana paling banyak masalah?'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">📊 Sumber paling banyak masalah</button>
        <button wire:click="$set('query', 'Tampilkan duplikat keyakinan tinggi'); ask()" class="whitespace-nowrap px-3 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-[#1C212E] dark:hover:bg-[#2A303F] text-slate-600 dark:text-slate-300 text-xs rounded-full border border-slate-200 dark:border-[#3E4557] transition-colors">🎯 Risiko Tinggi</button>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white dark:bg-[#12151E] shrink-0">
        <form wire:submit.prevent="ask" class="relative flex items-center">
            <input 
                type="text" 
                wire:model="query" 
                placeholder="Ketik pertanyaan analitik Anda..." 
                class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-200 dark:border-[#2A303F] rounded-xl pl-4 pr-12 py-3.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white dark:placeholder-slate-500 outline-none transition-shadow"
            >
            <button 
                type="submit" 
                class="absolute right-2 p-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50"
                wire:loading.attr="disabled"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
            </button>
        </form>
        <p class="text-[10px] text-center text-slate-400 mt-2">AI dapat membuat kesalahan. Harap verifikasi hasil analitik.</p>
    </div>

    <script>
        // Auto scroll to bottom
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', ({ component, el }) => {
                const container = document.getElementById('chat-container');
                container.scrollTop = container.scrollHeight;
            });
        });
    </script>
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</div>
