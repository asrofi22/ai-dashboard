<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
    x-data="{ 
        sidebarOpen: true, 
        activeTab: 'dashboard',
        darkMode: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
    }"
    :class="{ 'dark': darkMode }"
    x-init="$watch('darkMode', val => { 
        localStorage.setItem('theme', val ? 'dark' : 'light');
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: val }));
    })"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>AI DataGov - Enterprise Data Quality</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- TailwindCSS -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <style>
            body { font-family: 'Inter', sans-serif; }
            /* Custom Scrollbar for modern look */
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
            .dark ::-webkit-scrollbar-thumb { background: #334155; }
            ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        </style>
    </head>
    <body class="bg-slate-50 dark:bg-[#0B0D13] text-slate-800 dark:text-slate-200 min-h-screen flex overflow-hidden selection:bg-indigo-500 selection:text-white">
        
        <!-- Sidebar -->
        <aside 
            :class="sidebarOpen ? 'w-64' : 'w-20'" 
            class="bg-white dark:bg-[#12151E] border-r border-slate-200 dark:border-[#222735] flex flex-col transition-all duration-300 z-20 shadow-sm relative h-screen shrink-0"
        >
            <!-- Logo Area -->
            <div class="h-16 flex items-center px-4 border-b border-slate-200 dark:border-[#222735] shrink-0 justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path></svg>
                    </div>
                    <span x-show="sidebarOpen" class="font-semibold text-lg tracking-tight whitespace-nowrap text-slate-900 dark:text-white">AI DataGov</span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                <!-- Dashboard -->
                <button @click="activeTab = 'dashboard'" :class="activeTab === 'dashboard' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-[#1C212E]'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">Dasbor</span>
                </button>

                <!-- Upload Data -->
                <button @click="activeTab = 'upload'" :class="activeTab === 'upload' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-[#1C212E]'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">Unggah Data</span>
                </button>

                <!-- Duplicate Detection -->
                <button @click="activeTab = 'duplicates'" :class="activeTab === 'duplicates' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-[#1C212E]'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">Deteksi Duplikat</span>
                    <span x-show="sidebarOpen" class="ml-auto bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 py-0.5 px-2 rounded-full text-xs font-semibold">Baru</span>
                </button>

                <!-- AI Analytics -->
                <button @click="activeTab = 'analytics'" :class="activeTab === 'analytics' ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-[#1C212E]'" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors group">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span x-show="sidebarOpen" class="font-medium text-sm whitespace-nowrap">Asisten AI</span>
                </button>
            </nav>

            <!-- User Area -->
            <div class="p-4 border-t border-slate-200 dark:border-[#222735]">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm shrink-0 ring-2 ring-white dark:ring-[#12151E]">
                        AD
                    </div>
                    <div x-show="sidebarOpen" class="flex flex-col overflow-hidden">
                        <span class="text-sm font-medium text-slate-900 dark:text-white truncate">Admin Pengguna</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400 truncate">admin@enterprise.com</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            
            <!-- Topbar -->
            <header class="h-16 bg-white/80 dark:bg-[#12151E]/80 backdrop-blur-md border-b border-slate-200 dark:border-[#222735] flex items-center justify-between px-4 sm:px-6 z-10 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-[#1C212E] dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </button>
                    <!-- Breadcrumbs -->
                    <div class="hidden sm:flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <span>AI DataGov</span>
                        <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <span class="font-medium text-slate-900 dark:text-white" x-text="
                            activeTab === 'dashboard' ? 'Dasbor' : 
                            activeTab === 'upload' ? 'Unggah Data' : 
                            activeTab === 'duplicates' ? 'Kandidat Duplikat' : 'Asisten AI'
                        "></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:hover:bg-[#1C212E] dark:text-slate-400 transition-colors relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white dark:border-[#12151E]"></span>
                    </button>
                    <button @click="darkMode = !darkMode" class="p-2 rounded-full text-slate-500 hover:bg-slate-100 dark:hover:bg-[#1C212E] dark:text-slate-400 transition-colors">
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto space-y-6">
                    
                    <!-- View: Dashboard -->
                    <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tinjauan Kualitas Data</h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pantau metrik kualitas data master Anda hari ini.</p>
                        </div>
                        @livewire('dashboard-analytics')
                    </div>

                    <!-- View: Upload -->
                    <div x-show="activeTab === 'upload'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Impor Sumber Data Baru</h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Unggah file Excel atau CSV untuk memulai proses pembersihan dan validasi AI.</p>
                        </div>
                        @livewire('upload-manager')
                    </div>

                    <!-- View: Duplicates -->
                    <div x-show="activeTab === 'duplicates'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Pusat Validasi Duplikat</h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tinjau anomali data dan gunakan AI untuk menentukan resolusi.</p>
                        </div>
                        @livewire('duplicate-candidate-table')
                    </div>

                    <!-- View: AI Analytics -->
                    <div x-show="activeTab === 'analytics'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Asisten Cerdas</h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajukan pertanyaan kompleks tentang pola duplikasi Anda.</p>
                        </div>
                        <div class="max-w-4xl mx-auto">
                            @livewire('conversational-analytics')
                        </div>
                    </div>

                </div>
            </main>
        </div>

        @livewireScripts
    </body>
</html>
