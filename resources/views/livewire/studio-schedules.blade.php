<div
    @if(!$isEditModalOpen) wire:poll.30s="loadData" @endif
    class="space-y-6"
    x-data="{
        showToast: false,
        toastMessage: '',
        now: Date.now(),
        _interval: null,

        init() {
            // Clear any leftover interval from previous Livewire morph cycles
            if (this._interval) clearInterval(this._interval);
            this._interval = setInterval(() => {
                this.now = Date.now();
                // Push live relative-time into every [data-rtime] element inside this component
                this.$root.querySelectorAll('[data-rtime]').forEach(el => {
                    const ts = parseInt(el.getAttribute('data-rtime'));
                    if (ts) el.textContent = this._relativeTime(ts);
                });
            }, 1000);
        },

        _relativeTime(ts) {
            const diff = ts * 1000 - this.now;
            if (diff <= 0) return 'sudah lewat';
            const secs = Math.floor(diff / 1000);
            if (secs < 60) return secs + ' dtk lagi';
            const mins = Math.floor(secs / 60);
            if (mins < 60) return mins + ' mnt lagi';
            const hrs  = Math.floor(mins / 60);
            const remM = mins % 60;
            if (hrs < 24) return hrs + ' jam' + (remM > 0 ? ' ' + remM + ' mnt' : '') + ' lagi';
            return Math.floor(hrs / 24) + ' hari lagi';
        },

        _clock() {
            return new Date(this.now).toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
        }
    }"
    x-on:schedule-updated.window="
        toastMessage = $event.detail.message;
        showToast = true;
        setTimeout(() => showToast = false, 4000);
    "
>
    {{-- Toast notification --}}
    <div x-show="showToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-5 right-5 bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-xl flex items-center gap-2.5 text-xs font-semibold z-50 border border-emerald-500/30"
         style="display: none;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span x-text="toastMessage"></span>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        {{-- Card 1: Total Terjadwal --}}
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300 dark:hover:border-slate-800">
            <div>
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Total Terjadwal</span>
                <strong class="text-2xl font-extrabold text-slate-900 dark:text-white mt-1 block font-mono">
                    {{ $this->summary_metrics['total_scheduled'] }}
                </strong>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        {{-- Card 2: Jadwal Aktif --}}
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300 dark:hover:border-slate-800">
            <div>
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Jadwal Aktif</span>
                <strong class="text-2xl font-extrabold text-emerald-500 dark:text-emerald-400 mt-1 block font-mono">
                    {{ $this->summary_metrics['active_schedules'] }}
                </strong>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        {{-- Card 3: Next Pipeline --}}
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm flex items-center justify-between col-span-1 md:col-span-2 transition-all hover:border-slate-300 dark:hover:border-slate-800">
            <div class="overflow-hidden">
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Eksekusi Terdekat Berikutnya</span>
                <strong class="text-sm font-bold text-slate-900 dark:text-white mt-1 block truncate">
                    {{ $this->summary_metrics['next_pipeline'] }}
                </strong>
                <span class="text-[10px] text-slate-500 mt-0.5 block font-mono">
                    @if($this->summary_metrics['next_time_ts'])
                        {{ $this->summary_metrics['next_time_formatted'] }}
                        (<span data-rtime="{{ $this->summary_metrics['next_time_ts'] }}">{{ $this->summary_metrics['next_time_relative'] }}</span>)
                    @else
                        N/A
                    @endif
                </span>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
        </div>

        {{-- Card 4: Live WIB Clock --}}
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm flex items-center justify-between transition-all hover:border-slate-300 dark:hover:border-slate-800">
            <div>
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Waktu WIB</span>
                <strong class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1 block font-mono tabular-nums" x-text="_clock()">
                    --:--:--
                </strong>
                <span class="text-[9px] text-emerald-500 font-bold uppercase tracking-wider block mt-0.5">● LIVE</span>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    {{-- Main Layout Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Schedules List (Left) --}}
        <div class="lg:col-span-3 bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm">Daftar Jadwal Pipeline</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Atur dan kelola waktu pemrosesan otomatis untuk setiap ETL pipeline Anda.</p>
                </div>
                {{-- Realtime refresh indicator --}}
                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 bg-slate-50 dark:bg-[#161A25] px-3 py-1.5 rounded-lg border border-slate-150 dark:border-slate-800">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="font-semibold">Auto-refresh 30s</span>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-150 dark:border-slate-850 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                    <thead class="bg-slate-50 dark:bg-[#161A25] text-slate-500 dark:text-slate-400 uppercase font-semibold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-4 py-3.5 text-left">Pipeline Name</th>
                            <th class="px-4 py-3.5 text-left">Schedule Interval</th>
                            <th class="px-4 py-3.5 text-left">Status</th>
                            <th class="px-4 py-3.5 text-left">Last Run</th>
                            <th class="px-4 py-3.5 text-left">Next Run (WIB)</th>
                            <th class="px-4 py-3.5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-150 dark:divide-slate-850 bg-white dark:bg-[#12151E] text-slate-700 dark:text-slate-300">
                        @forelse($pipelines as $pipe)
                            @php
                                $interval = $pipe['schedule_interval'];
                                $badgeColor = match(true) {
                                    $interval === 'manual' => 'bg-slate-100 text-slate-700 dark:bg-slate-800/40 dark:text-slate-400',
                                    $interval === 'hourly' || str_contains($interval, ' * * * *') => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                    str_ends_with($interval, ' * * *') => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                    str_contains($interval, ' * * ') && !str_ends_with($interval, ' * * *') => 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
                                    default => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400'
                                };

                                $intervalLabel = match(true) {
                                    $interval === 'manual' => 'Manual',
                                    $interval === 'hourly' => 'Hourly (0 * * * *)',
                                    str_starts_with($interval, '*/') && str_ends_with($interval, ' * * * *') => 'Hourly (' . $interval . ')',
                                    $this->isDailyCron($interval) => 'Daily (' . $this->parseTimeFromCron($interval) . ')',
                                    $this->isWeeklyCron($interval) => 'Weekly',
                                    $this->isMonthlyCron($interval) => 'Monthly',
                                    default => 'Custom (' . $interval . ')'
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-[#161A25]/50 transition-colors">
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ $pipe['name'] }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium {{ $badgeColor }} font-mono">
                                        {{ $intervalLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        wire:click="togglePipelineActive({{ $pipe['id'] }})"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold transition-all focus:outline-none {{ $pipe['is_active'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20' : 'bg-red-500/10 text-red-400 hover:bg-red-500/20' }}"
                                    >
                                        {{ strtoupper($pipe['is_active']) }}
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-500 dark:text-slate-400 text-[11px]">
                                    {{ $pipe['last_run'] ? \Carbon\Carbon::parse($pipe['last_run'])->setTimezone('Asia/Jakarta')->format('d M H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-[11px] font-bold text-slate-900 dark:text-indigo-400">
                                    @if($pipe['next_run'] === 'Tidak Aktif / Manual' || $pipe['next_run'] === 'Manual' || $pipe['next_run'] === 'Ekspresi Cron Invalid')
                                        <span class="text-slate-400 font-normal">{{ $pipe['next_run'] }}</span>
                                    @else
                                        {{ \Carbon\Carbon::parse($pipe['next_run'])->setTimezone('Asia/Jakarta')->format('d M H:i') }} WIB
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Run Now Button --}}
                                        <button
                                            wire:click="runNow({{ $pipe['id'] }})"
                                            title="Eksekusi Sekarang"
                                            class="p-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                        {{-- Edit Button --}}
                                        <button
                                            wire:click="editSchedule({{ $pipe['id'] }})"
                                            title="Ubah Jadwal"
                                            class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-[#1C212E] dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 rounded transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                    Tidak ada pipeline terdaftar yang dapat dijadwalkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 24h Timeline Sidebar (Right) --}}
        <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-6 shadow-sm space-y-4">
            <div>
                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Timeline Eksekusi Terjadwal</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Proyeksi jadwal eksekusi 24 jam ke depan.</p>
            </div>

            <div class="relative pl-4 border-l border-slate-150 dark:border-slate-800 space-y-5 py-2">
                @forelse($this->upcoming_timeline as $timeItem)
                    <div class="relative group">
                        {{-- Timeline Dot --}}
                        <div class="absolute -left-[20.5px] top-1 w-2.5 h-2.5 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-[#12151E] group-hover:scale-125 transition-transform duration-200"></div>

                        <div class="bg-slate-50 dark:bg-[#161A25] p-3 rounded-lg border border-slate-150 dark:border-slate-850/50 hover:border-indigo-500/30 transition-all">
                            <div class="flex justify-between items-start gap-1">
                                <span class="font-mono text-indigo-600 dark:text-indigo-400 text-xs font-bold shrink-0">
                                    {{ $timeItem['time'] }} WIB
                                </span>
                                <span class="text-[9px] text-slate-400 text-right uppercase tracking-wider font-bold">
                                    {{ $timeItem['date'] }}
                                </span>
                            </div>
                            <strong class="text-xs font-bold text-slate-800 dark:text-slate-250 block mt-1 truncate">
                                {{ $timeItem['pipeline_name'] }}
                            </strong>
                            {{-- data-rtime attribute → JS updates textContent every second --}}
                            <span class="text-[9px] text-emerald-500 dark:text-emerald-400 mt-0.5 block font-mono font-bold tabular-nums"
                                  data-rtime="{{ $timeItem['timestamp'] }}">
                                {{ $timeItem['relative'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-500 dark:text-slate-400 text-xs">
                        Tidak ada eksekusi terjadwal dalam 24 jam ke depan.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Edit Schedule Modal --}}
    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden text-xs flex flex-col">
                {{-- Modal Header --}}
                <div class="p-5 border-b border-slate-150 dark:border-slate-850 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-950 dark:text-white">Ubah Konfigurasi Penjadwalan</h4>
                        <p class="text-[10px] text-slate-500 mt-0.5">Tentukan bagaimana dan kapan pipeline ini akan dieksekusi secara otomatis.</p>
                    </div>
                    <button wire:click="$set('isEditModalOpen', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors text-lg font-bold">&times;</button>
                </div>

                {{-- Modal Content / Form --}}
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    {{-- Toggle Aktif/Nonaktif --}}
                    <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-[#161A25] rounded-xl border border-slate-150 dark:border-slate-850">
                        <div>
                            <span class="font-bold text-slate-800 dark:text-slate-200 block">Jadwal Aktif</span>
                            <span class="text-[10px] text-slate-500">Aktifkan untuk memproses pipeline secara terjadwal di background.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="isActive" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 dark:bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>

                    {{-- Pilihan Mode Jadwal --}}
                    <div class="space-y-1.5">
                        <label class="block font-bold text-slate-800 dark:text-slate-300">Jadwal Eksekusi (Schedule Interval):</label>
                        <select wire:model.live="scheduleMode" class="w-full px-3 py-2 bg-slate-50 dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-850 dark:text-slate-200 font-medium focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                            <option value="manual">Manual (Tidak Terjadwal)</option>
                            <option value="hourly">Hourly (Setiap Jam / Menit Tertentu)</option>
                            <option value="daily">Daily (Setiap Hari pada Waktu Tertentu)</option>
                            <option value="weekly">Weekly (Setiap Minggu pada Hari &amp; Jam Tertentu)</option>
                            <option value="monthly">Monthly (Setiap Bulan pada Tanggal &amp; Jam Tertentu)</option>
                            <option value="custom">Custom Cron Expression</option>
                        </select>
                    </div>

                    {{-- Dynamic Subforms berdasarkan Mode --}}
                    <div class="p-4 bg-slate-50 dark:bg-[#0E1118]/80 border border-slate-150 dark:border-slate-850/60 rounded-xl space-y-3">
                        @if($scheduleMode === 'manual')
                            <p class="text-slate-500 italic">Pipeline ini tidak akan dieksekusi secara terjadwal. Anda harus menjalankannya secara manual dari dasbor atau workspace.</p>
                        @elseif($scheduleMode === 'hourly')
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-800 dark:text-slate-300">Setiap Berapa Menit:</label>
                                <select wire:model.live="hourlyMinutes" class="w-full px-3 py-2 bg-white dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-850 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                    <option value="0">Tepat pada pergantian jam (Setiap 1 jam)</option>
                                    <option value="5">Setiap 5 menit</option>
                                    <option value="10">Setiap 10 menit</option>
                                    <option value="15">Setiap 15 menit</option>
                                    <option value="30">Setiap 30 menit</option>
                                </select>
                            </div>
                        @elseif($scheduleMode === 'daily')
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-800 dark:text-slate-300">Waktu Eksekusi Harian (WIB):</label>
                                <input type="time" wire:model.live="dailyTime" class="px-3 py-2 bg-white dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-800 dark:text-slate-200 font-mono focus:ring-1 focus:ring-indigo-500 focus:outline-none block w-full">
                            </div>
                        @elseif($scheduleMode === 'weekly')
                            <div class="space-y-3">
                                <div class="space-y-1.5">
                                    <label class="block font-bold text-slate-800 dark:text-slate-300">Hari Eksekusi Mingguan:</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        @php
                                            $days = [
                                                ['id' => '0', 'name' => 'Minggu'],
                                                ['id' => '1', 'name' => 'Senin'],
                                                ['id' => '2', 'name' => 'Selasa'],
                                                ['id' => '3', 'name' => 'Rabu'],
                                                ['id' => '4', 'name' => 'Kamis'],
                                                ['id' => '5', 'name' => 'Jumat'],
                                                ['id' => '6', 'name' => 'Sabtu']
                                            ];
                                        @endphp
                                        @foreach($days as $day)
                                            <label class="flex items-center gap-1.5 p-2 bg-white dark:bg-[#161A25] rounded-lg border border-slate-200 dark:border-slate-850 cursor-pointer">
                                                <input type="checkbox" wire:model.live="weeklyDays" value="{{ $day['id'] }}" class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300 dark:border-slate-800">
                                                <span class="text-[10px] font-medium text-slate-800 dark:text-slate-300">{{ $day['name'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block font-bold text-slate-800 dark:text-slate-300">Waktu Eksekusi (WIB):</label>
                                    <input type="time" wire:model.live="weeklyTime" class="px-3 py-2 bg-white dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-800 dark:text-slate-200 font-mono focus:ring-1 focus:ring-indigo-500 focus:outline-none block w-full">
                                </div>
                            </div>
                        @elseif($scheduleMode === 'monthly')
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label class="block font-bold text-slate-800 dark:text-slate-300">Tanggal Eksekusi (1-31):</label>
                                    <input type="number" min="1" max="31" wire:model.live="monthlyDay" class="px-3 py-2 bg-white dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-800 dark:text-slate-200 font-mono focus:ring-1 focus:ring-indigo-500 focus:outline-none block w-full">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block font-bold text-slate-800 dark:text-slate-300">Waktu Eksekusi (WIB):</label>
                                    <input type="time" wire:model.live="monthlyTime" class="px-3 py-2 bg-white dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-800 dark:text-slate-200 font-mono focus:ring-1 focus:ring-indigo-500 focus:outline-none block w-full">
                                </div>
                            </div>
                        @elseif($scheduleMode === 'custom')
                            <div class="space-y-1.5">
                                <label class="block font-bold text-slate-800 dark:text-slate-300">Ekspresi Cron Kustom:</label>
                                <input type="text" wire:model.live="customCron" class="px-3 py-2 bg-white dark:bg-[#161A25] border border-slate-200 dark:border-slate-850 rounded-lg text-slate-800 dark:text-slate-200 font-mono focus:ring-1 focus:ring-indigo-500 focus:outline-none block w-full">
                                @error('customCron')
                                    <span class="text-[10px] text-red-500 block font-semibold">{{ $message }}</span>
                                @enderror
                                <p class="text-[9px] text-slate-500 leading-relaxed pt-1">
                                    Ekspresi cron standar memiliki 5 parameter dipisahkan spasi: <br>
                                    <code class="text-indigo-400 font-bold">menit jam tanggal bulan hari_dalam_minggu</code> (contoh: <code class="text-emerald-400">*/15 * * * *</code> untuk setiap 15 menit).
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="p-5 border-t border-slate-150 dark:border-slate-850 flex items-center justify-end gap-3 shrink-0">
                    <button
                        wire:click="$set('isEditModalOpen', false)"
                        class="px-4 py-2 border border-slate-200 dark:border-slate-850 rounded-xl hover:bg-slate-50 dark:hover:bg-[#1C212E] text-slate-650 dark:text-slate-400 transition-colors font-semibold"
                    >
                        Batal
                    </button>
                    <button
                        wire:click="saveSchedule"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-md transition-colors"
                    >
                        Simpan Jadwal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
