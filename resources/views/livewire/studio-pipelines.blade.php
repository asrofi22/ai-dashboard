<div class="space-y-6" x-data="studioPipelines({ viewMode: @entangle('viewMode') })">
    <script>
        window.studioPipelines = function(config) {
            return {
                viewMode: config.viewMode,
                canvas: { nodes: [], connections: [] },
                selectedNodeId: null,
                isConfigModalOpen: false,
                configNode: null,
                activeTab: 'select_alter',
                panX: 0,
                panY: 0,
                isDragging: false,
                draggedNodeId: null,
                dragStartX: 0,
                dragStartY: 0,
                
                init() {
                    this.loadCanvas();
                    this.$watch('canvas', value => {
                        this.updateCanvasJson();
                    }, { deep: true });
                    
                    this.$watch('$wire.canvasDataJson', value => {
                        if (value) {
                            if (value !== JSON.stringify(this.canvas)) {
                                this.loadCanvas();
                            }
                        } else {
                            this.canvas = { nodes: [], connections: [] };
                        }
                    });
                    
                    // Listen to livewire updates
                    window.addEventListener('canvas-updated', () => {
                        this.loadCanvas();
                    });
                },
                
                loadCanvas() {
                    if (this.$wire.canvasDataJson) {
                        try {
                            this.canvas = JSON.parse(this.$wire.canvasDataJson);
                        } catch(e) {
                            console.error(e);
                        }
                    }
                },
                
                updateCanvasJson() {
                    this.$wire.canvasDataJson = JSON.stringify(this.canvas);
                },
                
                addNode(type, name, label) {
                    let id = 'node_' + Date.now() + '_' + Math.floor(Math.random() * 100);
                    let newNode = {
                        id: id,
                        type: type,
                        name: name,
                        label: label,
                        x: 200 - this.panX,
                        y: 150 - this.panY,
                        settings: {
                            select_alter: [],
                            remove: [],
                            metadata: []
                        },
                        metadata: { fieldsIn: [], fieldsOut: [] },
                        validation: { status: 'valid', messages: [] }
                    };
                    
                    this.canvas.nodes.push(newNode);
                    this.selectedNodeId = id;
                    this.updateCanvasJson();
                    this.$wire.updateLivewire();
                },
                
                deleteNode(id) {
                    this.canvas.nodes = this.canvas.nodes.filter(n => n.id !== id);
                    this.canvas.connections = this.canvas.connections.filter(c => c.from !== id && c.to !== id);
                    if (this.selectedNodeId === id) this.selectedNodeId = null;
                    this.updateCanvasJson();
                    this.$wire.updateLivewire();
                },
                
                startNodeDrag(e, nodeId) {
                    this.draggedNodeId = nodeId;
                    let node = this.canvas.nodes.find(n => n.id === nodeId);
                    this.dragStartX = e.clientX - node.x;
                    this.dragStartY = e.clientY - node.y;
                    e.stopPropagation();
                },
                
                onMouseMove(e) {
                    if (this.draggedNodeId) {
                        let node = this.canvas.nodes.find(n => n.id === this.draggedNodeId);
                        if (node) {
                            node.x = e.clientX - this.dragStartX;
                            node.y = e.clientY - this.dragStartY;
                        }
                    }
                },
                
                stopNodeDrag() {
                    if (this.draggedNodeId) {
                        this.draggedNodeId = null;
                        this.updateCanvasJson();
                        this.$wire.updateLivewire();
                    }
                },
                
                selectNode(id) {
                    this.selectedNodeId = id;
                },
                
                doubleClickNode(node) {
                    if (!node) return;
                    this.configNode = JSON.parse(JSON.stringify(node));
                    if (!this.configNode.settings) this.configNode.settings = {};
                    
                    if (this.configNode.name === 'Select Values' || this.configNode.label === 'Select Values') {
                        if (!this.configNode.settings.select_alter) this.configNode.settings.select_alter = [];
                        if (!this.configNode.settings.remove) this.configNode.settings.remove = [];
                        if (!this.configNode.settings.metadata) this.configNode.settings.metadata = [];
                        this.activeTab = 'select_alter';
                    } else if (this.configNode.name === 'Formula') {
                        if (!this.configNode.settings.formulas) this.configNode.settings.formulas = [];
                        this.activeTab = 'formula';
                    } else if (this.configNode.name === 'Data Grid') {
                        if (!this.configNode.settings.fields) this.configNode.settings.fields = [];
                        if (!this.configNode.settings.data) this.configNode.settings.data = [];
                        this.activeTab = 'meta';
                    } else if (this.configNode.name === 'Sort Rows') {
                        if (!this.configNode.settings.fields) this.configNode.settings.fields = [];
                    } else if (this.configNode.type === 'input' || this.configNode.name === 'source' || this.configNode.name === 'Table Input' || this.configNode.name === 'Database Input') {
                        if (!this.configNode.settings.sql) this.configNode.settings.sql = '';
                        if (!this.configNode.settings.connection_id) this.configNode.settings.connection_id = '';
                    } else if (this.configNode.name === 'Modified JavaScript Value') {
                        if (!this.configNode.settings.js) this.configNode.settings.js = '';
                    } else if (this.configNode.type === 'output' || this.configNode.name === 'Table Output' || this.configNode.name === 'Insert Update') {
                        if (!this.configNode.settings.connection_id) this.configNode.settings.connection_id = '';
                        if (!this.configNode.settings.target_table) this.configNode.settings.target_table = '';
                    }
                    
                    this.isConfigModalOpen = true;
                },
                
                saveConfig() {
                    let index = this.canvas.nodes.findIndex(n => n.id === this.configNode.id);
                    if (index !== -1) {
                        this.canvas.nodes[index].label = this.configNode.label;
                        this.canvas.nodes[index].settings = this.configNode.settings;
                    }
                    this.isConfigModalOpen = false;
                    this.updateCanvasJson();
                    this.$wire.updateLivewire();
                },
                
                getIncomingFields() {
                    return (this.configNode && this.configNode.metadata && this.configNode.metadata.fieldsIn) ? this.configNode.metadata.fieldsIn : [];
                },
                
                getFieldsSelectAlter() {
                    let fields = this.getIncomingFields();
                    this.configNode.settings.select_alter = fields.map(f => ({
                        field: f.name || f,
                        rename: '',
                        length: f.length || '',
                        precision: f.precision || ''
                    }));
                },
                
                getFieldsRemove() {
                    let fields = this.getIncomingFields();
                    this.configNode.settings.remove = fields.map(f => ({
                        field: f.name || f
                    }));
                },
                
                getFieldsMetadata() {
                    let fields = this.getIncomingFields();
                    this.configNode.settings.metadata = fields.map(f => ({
                        field: f.name || f,
                        type: f.type || 'String',
                        length: f.length || '',
                        precision: f.precision || ''
                    }));
                },
                
                addSelectAlterRow() {
                    this.configNode.settings.select_alter.push({ field: '', rename: '', length: '', precision: '' });
                },
                
                addRemoveRow() {
                    this.configNode.settings.remove.push({ field: '' });
                },
                
                addMetadataRow() {
                    this.configNode.settings.metadata.push({ field: '', type: 'String', length: '', precision: '' });
                },
                
                getPath(c) {
                    if (!c) return '';
                    let fromNode = this.canvas.nodes.find(n => n.id === (c.from || c.fromNodeId));
                    let toNode = this.canvas.nodes.find(n => n.id === (c.to || c.toNodeId));
                    if (!fromNode || !toNode) return '';
                    let x1 = fromNode.x + 176;
                    let y1 = fromNode.y + 24;
                    let x2 = toNode.x;
                    let y2 = toNode.y + 24;
                    return `M ${x1} ${y1} C ${(x1 + x2)/2} ${y1}, ${(x1 + x2)/2} ${y2}, ${x2} ${y2}`;
                },
                
                renderConnections() {
                    let html = '';
                    if (!this.canvas || !this.canvas.connections) return html;
                    this.canvas.connections.forEach(c => {
                        let path = this.getPath(c);
                        if (path) {
                            html += `<path d="${path}" stroke="#818cf8" stroke-width="2.5" fill="none" marker-end="url(#arrow)"/>`;
                        }
                    });
                    return html;
                },
                
                get selectedNodeLabel() {
                    if (!this.canvas || !this.canvas.nodes) return '';
                    let node = this.canvas.nodes.find(n => n.id === this.selectedNodeId);
                    return node ? node.label : '';
                },
                
                set selectedNodeLabel(val) {
                    if (!this.canvas || !this.canvas.nodes) return;
                    let node = this.canvas.nodes.find(n => n.id === this.selectedNodeId);
                    if (node) {
                        node.label = val;
                        this.updateCanvasJson();
                    }
                }
            };
        };
    </script>
    @if($viewMode === 'list')
        <!-- Action Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm gap-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Desainer Pipeline ETL</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Rancang skema integrasi data visual, konfigurasi kolom mapping, dan tentukan alur transformasi data.</p>
            </div>
            <button 
                wire:click="openCreate"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 shrink-0"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Rancang Pipeline Baru
            </button>
        </div>

        <!-- Notifications -->
        @if (session()->has('message'))
            <div class="p-3.5 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg text-xs font-semibold border border-emerald-100 dark:border-emerald-500/20">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="p-3.5 bg-red-50 dark:bg-red-500/10 text-red-650 dark:text-red-400 rounded-lg text-xs font-semibold border border-red-100 dark:border-red-500/20">
                {{ session('error') }}
            </div>
        @endif

        <!-- Pipelines List Grid -->
        <div class="grid grid-cols-1 gap-6">
            @forelse($pipelines as $pipe)
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-5 shadow-sm space-y-4 hover:shadow-md transition-shadow">
                    <!-- Header of Pipeline -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="font-extrabold text-slate-900 dark:text-white text-sm font-mono">
                                {{ $pipe['name'] }}
                            </h3>
                            <p class="text-[10px] text-slate-400 mt-0.5">Kunci Unik ID: #{{ $pipe['id'] }} | Diperbarui: {{ date('d M Y H:i', strtotime($pipe['updated_at'])) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 uppercase tracking-wider">
                                {{ $pipe['is_active'] }}
                            </span>
                            <div class="flex gap-2">
                                <button 
                                    wire:click="openEdit({{ $pipe['id'] }})"
                                    class="px-2.5 py-1 text-slate-600 dark:text-slate-350 hover:text-indigo-600 dark:hover:text-indigo-400 rounded text-xs font-semibold border border-slate-200 dark:border-slate-800 transition-colors"
                                >
                                    Buka Workspace
                                </button>
                                <button 
                                    wire:click="delete({{ $pipe['id'] }})"
                                    wire:confirm="Yakin ingin menghapus pipeline ini?"
                                    class="px-2.5 py-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded text-xs font-semibold border border-red-200 dark:border-red-900/30 transition-colors"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Diagram Visualization -->
                    <div class="py-2 overflow-x-auto">
                        <div class="flex items-center gap-3 min-w-max text-xs font-semibold">
                            <!-- Source Node -->
                            <div class="flex items-center gap-2 bg-blue-50 dark:bg-blue-500/10 border border-blue-150 dark:border-blue-500/20 rounded-xl p-3 max-w-[200px]">
                                <div class="w-5 h-5 rounded-lg bg-blue-500 text-white flex items-center justify-center text-[10px] font-bold shrink-0 font-mono">S</div>
                                <div class="overflow-hidden">
                                    <span class="block text-[8px] text-blue-400 uppercase tracking-wider">Source Table</span>
                                    <strong class="text-blue-700 dark:text-blue-400 font-mono text-[10px] truncate block" title="{{ $pipe['source_table'] }}">{{ $pipe['source_table'] }}</strong>
                                </div>
                            </div>

                            <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>

                            <!-- Transform Nodes -->
                            @forelse($pipe['transformations'] ?? [] as $t)
                                <div class="flex items-center gap-2 bg-purple-50 dark:bg-purple-500/10 border border-purple-150 dark:border-purple-500/20 rounded-xl p-2.5">
                                    <span class="w-4 h-4 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold shrink-0">T</span>
                                    <span class="text-purple-700 dark:text-purple-400 text-[10px]">{{ $t }}</span>
                                </div>
                                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            @empty
                                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl p-2.5 text-slate-400">
                                    <span class="text-[10px] italic">Direct Load (No Transforms)</span>
                                </div>
                                <svg class="w-5 h-5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            @endforelse

                            <!-- Target Node -->
                            <div class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-150 dark:border-emerald-500/20 rounded-xl p-3 max-w-[200px]">
                                <div class="w-5 h-5 rounded-lg bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold shrink-0 font-mono">T</div>
                                <div class="overflow-hidden">
                                    <span class="block text-[8px] text-emerald-400 uppercase tracking-wider">Target Table</span>
                                    <strong class="text-emerald-700 dark:text-emerald-400 font-mono text-[10px] truncate block" title="{{ $pipe['target_table'] }}">{{ $pipe['target_table'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info footer section -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Source Conn</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-350">{{ $pipe['source_connection']['name'] ?? 'Unknown' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Target Conn</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-350">{{ $pipe['target_connection']['name'] ?? 'Unknown' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Jadwal / Frekuensi</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-350 font-mono">Daily</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[9px] font-bold uppercase tracking-wider">Mapping Kolom</span>
                            <span class="font-semibold text-indigo-600 dark:text-indigo-400 font-mono">{{ count($pipe['column_mapping'] ?? []) }} Kolom Terhubung</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-xl p-12 text-center text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <p class="text-sm font-medium">Belum ada pipeline ETL yang terdaftar. Klik button **"Rancang Pipeline Baru"** di kanan atas untuk mendesain.</p>
                </div>
            @endforelse
        </div>

        <!-- Create/Edit Pipeline Modal (Legacy Form Model compatibility check) -->
        @if($showModal)
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto">
                <div class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-200">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-150 dark:border-[#222735] flex justify-between items-center bg-slate-50/50 dark:bg-[#161A25]/50">
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                            <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                            {{ $isEditing ? 'Ubah Desain Pipeline' : 'Rancang Pipeline ETL Baru' }}
                        </h3>
                        <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                    </div>

                    <!-- Modal Body Form -->
                    <form wire:submit.prevent="save" class="p-6 space-y-6 max-h-[calc(100vh-12rem)] overflow-y-auto">
                        <!-- General Details -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-3 sm:col-span-2 space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Pipeline</label>
                                <input 
                                    type="text" 
                                    wire:model="name"
                                    class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-mono"
                                    placeholder="Contoh: sync_customer_gl"
                                />
                                @error('name') <span class="text-red-500 text-[10px] font-semibold">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-3 sm:col-span-1 space-y-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Default</label>
                                <select 
                                    wire:model="isActive"
                                    class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-xl p-2.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Workflow Source & Target Config -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Source Configuration Card -->
                            <div class="bg-blue-50/10 dark:bg-blue-500/5 border border-blue-200/50 dark:border-blue-500/10 rounded-xl p-4 space-y-3">
                                <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> 1. EXTRACTION SOURCE
                                </h4>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div class="col-span-2 space-y-1">
                                        <label class="text-[9px] text-slate-400 uppercase font-bold">Koneksi Sumber</label>
                                        <select 
                                            wire:model.live="sourceConnectionId"
                                            class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white"
                                        >
                                            <option value="">-- Pilih Koneksi Sumber --</option>
                                            @foreach($connections as $c)
                                                <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['driver'] }})</option>
                                            @endforeach
                                        </select>
                                        @error('sourceConnectionId') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-span-2 space-y-1">
                                        <label class="text-[9px] text-slate-400 uppercase font-bold">Tabel / Berkas Sumber</label>
                                        <select 
                                            wire:model.live="sourceTable"
                                            class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white font-mono"
                                            {{ empty($sourceTables) ? 'disabled' : '' }}
                                        >
                                            <option value="">-- Pilih Tabel / Berkas --</option>
                                            @foreach($sourceTables as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                        @error('sourceTable') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Target Configuration Card -->
                            <div class="bg-emerald-50/10 dark:bg-emerald-500/5 border border-emerald-200/50 dark:border-emerald-500/10 rounded-xl p-4 space-y-3">
                                <h4 class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> 2. LOAD TARGET
                                </h4>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div class="col-span-2 space-y-1">
                                        <label class="text-[9px] text-slate-400 uppercase font-bold">Koneksi Target DWH</label>
                                        <select 
                                            wire:model.live="targetConnectionId"
                                            class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white"
                                        >
                                            <option value="">-- Pilih Koneksi Target --</option>
                                            @foreach($connections as $c)
                                                <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['driver'] }})</option>
                                            @endforeach
                                        </select>
                                        @error('targetConnectionId') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-span-2 space-y-1">
                                        <label class="text-[9px] text-slate-400 uppercase font-bold">Tabel Target Gudang Data</label>
                                        <select 
                                            wire:model.live="targetTable"
                                            class="w-full bg-white dark:bg-[#161A25]/80 border border-slate-200 dark:border-slate-800 rounded-lg p-2 dark:text-white font-mono"
                                            {{ empty($targetTables) ? 'disabled' : '' }}
                                        >
                                            <option value="">-- Pilih Tabel Target --</option>
                                            @foreach($targetTables as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                        @error('targetTable') <span class="text-red-500 text-[9px] font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transformations Choices Checkbox Matrix -->
                        <div class="bg-white dark:bg-[#161A25]/20 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-3">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 font-mono">
                                <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> 3. TRANFORMA DATA & RAGAM MODIFIKASI
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach($availableTransformations as $t)
                                    <label class="flex items-center gap-2 p-2.5 bg-slate-50 dark:bg-[#161A25]/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl border border-slate-200/50 dark:border-slate-850 cursor-pointer select-none text-xs text-slate-700 dark:text-slate-350 transition-colors font-medium">
                                        <input 
                                            type="checkbox" 
                                            value="{{ $t }}"
                                            wire:model="selectedTransformations"
                                            class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-750"
                                        />
                                        <span>{{ $t }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Visual Workflow Preview Inline in designer form -->
                        <div class="bg-slate-50/50 dark:bg-[#161A25]/10 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Visualisasi Alur Workflow Rencana:</span>
                            <div class="flex items-center gap-2 overflow-x-auto py-1.5 text-[10px] font-mono font-bold">
                                <div class="bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 px-2 py-1 rounded">
                                    Source: {{ $sourceTable ?: '[Belum Dipilih]' }}
                                </div>
                                @foreach($selectedTransformations as $t)
                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    <div class="bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20 px-2 py-1 rounded">
                                        {{ $t }}
                                    </div>
                                @endforeach
                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                <div class="bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20 px-2 py-1 rounded">
                                    Target DWH: {{ $targetTable ?: '[Belum Dipilih]' }}
                                </div>
                            </div>
                        </div>

                        <!-- Column Mapping Editor -->
                        <div class="bg-white dark:bg-[#161A25]/20 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-4">
                            <div class="flex justify-between items-center">
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> 4. AI COLUMN MAPPING
                                </h4>
                                <button 
                                    type="button"
                                    wire:click="autoGenerateMapping"
                                    wire:loading.attr="disabled"
                                    wire:target="autoGenerateMapping"
                                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded text-[10px] font-bold flex items-center gap-1 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    {{ empty($sourceColumns) || empty($targetColumns) ? 'disabled' : '' }}
                                >
                                    <span wire:loading.remove wire:target="autoGenerateMapping" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                                        Gunakan AI Automap
                                    </span>
                                    <span wire:loading wire:target="autoGenerateMapping" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89"></path></svg>
                                        Memetakan...
                                    </span>
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach($columnMappings as $index => $map)
                                    <div class="flex items-center gap-3 animate-in fade-in slide-in-from-top-1 duration-150">
                                        <div class="flex-1">
                                            <select 
                                                wire:model="columnMappings.{{ $index }}.source"
                                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs font-mono dark:text-white"
                                            >
                                                <option value="">-- Pilih Kolom Sumber --</option>
                                                @foreach($sourceColumns as $c)
                                                    <option value="{{ $c }}">{{ $c }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                        <div class="flex-1">
                                            <select 
                                                wire:model="columnMappings.{{ $index }}.target"
                                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-xs font-mono dark:text-white"
                                            >
                                                <option value="">-- Pilih Kolom Target --</option>
                                                @foreach($targetColumns as $c)
                                                    <option value="{{ $c }}">{{ $c }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button 
                                            type="button" 
                                            wire:click="removeMappingRow({{ $index }})"
                                            class="p-1 text-slate-400 hover:text-red-500 rounded transition-colors"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <button 
                                type="button" 
                                wire:click="addMappingRow"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-bold flex items-center gap-1 mt-2"
                            >
                                + Tambah Kolom Mapping Baru
                            </button>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-150 dark:border-[#222735]">
                            <button 
                                type="button" 
                                wire:click="$set('showModal', false)"
                                class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-[#1C212E] rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="save">Simpan Pipeline</span>
                                <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @elseif($viewMode === 'workspace')
        <!-- VISUAL CANVAS ETL WORKSPACE -->
        <div class="flex flex-col bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-2xl shadow-xl overflow-hidden h-[calc(100vh-10rem)]">
            <!-- Workspace Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-[#222735] flex justify-between items-center bg-slate-50/80 dark:bg-[#161A25]/80 backdrop-blur-md shrink-0">
                <div class="flex items-center gap-3">
                    <button 
                        @click="viewMode = 'list'; $wire.closeWorkspace()"
                        class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-500 dark:text-slate-400 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </button>
                    <div>
                        <input 
                            type="text" 
                            wire:model="name"
                            class="bg-transparent border-b border-transparent hover:border-slate-300 dark:hover:border-slate-700 focus:border-indigo-500 focus:outline-none text-sm font-bold font-mono dark:text-white"
                            placeholder="Nama Pipeline"
                        />
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        @click="viewMode = 'list'; $wire.closeWorkspace()"
                        class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-[#1C212E] rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-350 transition-colors"
                    >
                        Tutup
                    </button>
                    <button 
                        @click="$wire.save()"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="save">Simpan Pipeline</span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Workspace Tabs -->
            <div class="flex border-b border-slate-200 dark:border-[#222735] bg-slate-50/50 dark:bg-[#161A25]/50 px-6 text-[10px] font-bold shrink-0">
                <button 
                    type="button"
                    wire:click="$set('workspaceTab', 'canvas')"
                    class="px-4 py-3 border-b-2 transition-all uppercase tracking-wider {{ $workspaceTab === 'canvas' ? 'border-indigo-500 text-indigo-650 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                >
                    🔗 Visual Flow
                </button>
                <button 
                    type="button"
                    wire:click="$set('workspaceTab', 'sql')"
                    class="px-4 py-3 border-b-2 transition-all uppercase tracking-wider {{ $workspaceTab === 'sql' ? 'border-indigo-500 text-indigo-650 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                >
                    💻 SQL Preview
                </button>
                <button 
                    type="button"
                    wire:click="$set('workspaceTab', 'json')"
                    class="px-4 py-3 border-b-2 transition-all uppercase tracking-wider {{ $workspaceTab === 'json' ? 'border-indigo-500 text-indigo-650 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                >
                    📂 JSON Definition
                </button>
                <button 
                    type="button"
                    wire:click="$set('workspaceTab', 'airflow')"
                    class="px-4 py-3 border-b-2 transition-all uppercase tracking-wider {{ $workspaceTab === 'airflow' ? 'border-indigo-500 text-indigo-650 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-750' }}"
                >
                    💨 Airflow DAG
                </button>
            </div>

            <!-- Workspace Body -->
            <div class="flex-1 flex overflow-hidden relative">
                @if($workspaceTab === 'canvas')
                    <!-- 1. LEFT PALETTE (Steps List) -->
                <div class="w-64 border-r border-slate-200 dark:border-[#222735] bg-slate-50/30 dark:bg-[#12151E]/30 p-4 space-y-4 overflow-y-auto shrink-0">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Palette Komponen</h3>
                    
                    <div class="space-y-3">
                        <!-- 1. INPUT CATEGORY -->
                        <details class="group space-y-1" open>
                            <summary class="flex justify-between items-center cursor-pointer list-none p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-350 transition-colors">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded bg-blue-500 shrink-0"></span>
                                    Input
                                </span>
                                <span class="transition group-open:rotate-180 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                            </summary>
                            <div class="space-y-1 pl-3.5 pt-1 pb-2">
                                <button type="button" @click="addNode('input', 'Table Input', 'Table Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">TI</span>
                                        <span class="truncate">Table Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">DB</span>
                                </button>
                                <button type="button" @click="addNode('input', 'CSV File Input', 'CSV File Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">CSV</span>
                                        <span class="truncate">CSV File Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('input', 'Microsoft Excel Input', 'Microsoft Excel Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">XLS</span>
                                        <span class="truncate">Excel Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('input', 'Text File Input', 'Text File Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">TXT</span>
                                        <span class="truncate">Text File Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('input', 'JSON Input', 'JSON Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">JSN</span>
                                        <span class="truncate">JSON Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">API</span>
                                </button>
                                <button type="button" @click="addNode('input', 'XML Input', 'XML Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">XML</span>
                                        <span class="truncate">XML Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('input', 'Data Grid', 'Data Grid')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">DG</span>
                                        <span class="truncate">Data Grid</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">MEM</span>
                                </button>
                                <button type="button" @click="addNode('input', 'Generate Rows', 'Generate Rows')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">GEN</span>
                                        <span class="truncate">Generate Rows</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('input', 'Get System Info', 'Get System Info')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">SYS</span>
                                        <span class="truncate">Get System Info</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">SYS</span>
                                </button>
                                <button type="button" @click="addNode('input', 'SharePoint Input', 'SharePoint Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">SP</span>
                                        <span class="truncate">SharePoint Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">CLOUD</span>
                                </button>
                                <button type="button" @click="addNode('input', 'REST API Input', 'REST API Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-blue-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">REST</span>
                                        <span class="truncate">REST API Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">HTTP</span>
                                </button>
                            </div>
                        </details>

                        <!-- 2. TRANSFORM CATEGORY -->
                        <details class="group space-y-1">
                            <summary class="flex justify-between items-center cursor-pointer list-none p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-350 transition-colors">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded bg-amber-500 shrink-0"></span>
                                    Transform
                                </span>
                                <span class="transition group-open:rotate-180 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                            </summary>
                            <div class="space-y-1 pl-3.5 pt-1 pb-2">
                                <button type="button" @click="addNode('transform', 'Select Values', 'Select Values')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">SL</span>
                                        <span class="truncate">Select Values</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Formula', 'Formula')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">FX</span>
                                        <span class="truncate">Formula</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Calculator', 'Calculator')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">CAL</span>
                                        <span class="truncate">Calculator</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Filter Rows', 'Filter Rows')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">FLT</span>
                                        <span class="truncate">Filter Rows</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FLOW</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Sort Rows', 'Sort Rows')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">SR</span>
                                        <span class="truncate">Sort Rows</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Unique Rows', 'Unique Rows')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">UNQ</span>
                                        <span class="truncate">Unique Rows</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Group By', 'Group By')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">GRP</span>
                                        <span class="truncate">Group By</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'String Operations', 'String Operations')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">STR</span>
                                        <span class="truncate">String Operations</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">STR</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Replace In String', 'Replace In String')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">RPL</span>
                                        <span class="truncate">Replace In String</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">STR</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Value Mapper', 'Value Mapper')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">MAP</span>
                                        <span class="truncate">Value Mapper</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">MAP</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Data Validator', 'Data Validator')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">VAL</span>
                                        <span class="truncate">Data Validator</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">QUAL</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Merge Join', 'Merge Join')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">MRG</span>
                                        <span class="truncate">Merge Join</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">JOIN</span>
                                </button>
                                <button type="button" @click="addNode('transform', 'Stream Lookup', 'Stream Lookup')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-amber-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">LKP</span>
                                        <span class="truncate">Stream Lookup</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">JOIN</span>
                                </button>
                            </div>
                        </details>

                        <!-- 3. OUTPUT CATEGORY -->
                        <details class="group space-y-1">
                            <summary class="flex justify-between items-center cursor-pointer list-none p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-350 transition-colors">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded bg-emerald-500 shrink-0"></span>
                                    Output
                                </span>
                                <span class="transition group-open:rotate-180 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                            </summary>
                            <div class="space-y-1 pl-3.5 pt-1 pb-2">
                                <button type="button" @click="addNode('output', 'Table Output', 'Table Output')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">TO</span>
                                        <span class="truncate">Table Output</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">DB</span>
                                </button>
                                <button type="button" @click="addNode('output', 'Insert Update', 'Insert Update')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">INS</span>
                                        <span class="truncate">Insert Update</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">DB</span>
                                </button>
                                <button type="button" @click="addNode('output', 'CSV File Output', 'CSV File Output')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">CSV</span>
                                        <span class="truncate">CSV File Output</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('output', 'Microsoft Excel Output', 'Microsoft Excel Output')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">XLS</span>
                                        <span class="truncate">Excel Output</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('output', 'JSON Output', 'JSON Output')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">JSN</span>
                                        <span class="truncate">JSON Output</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                                <button type="button" @click="addNode('output', 'XML Output', 'XML Output')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-emerald-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">XML</span>
                                        <span class="truncate">XML Output</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">FILE</span>
                                </button>
                            </div>
                        </details>

                        <!-- 4. FLOW CATEGORY -->
                        <details class="group space-y-1">
                            <summary class="flex justify-between items-center cursor-pointer list-none p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-350 transition-colors">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded bg-purple-500 shrink-0"></span>
                                    Flow
                                </span>
                                <span class="transition group-open:rotate-180 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                            </summary>
                            <div class="space-y-1 pl-3.5 pt-1 pb-2">
                                <button type="button" @click="addNode('flow', 'Dummy', 'Dummy')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">DM</span>
                                        <span class="truncate">Dummy (Do Nothing)</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('flow', 'Abort', 'Abort')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">ABR</span>
                                        <span class="truncate">Abort</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">PDI</span>
                                </button>
                                <button type="button" @click="addNode('flow', 'Mapping', 'Mapping')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">MAP</span>
                                        <span class="truncate">Mapping (Sub-pipeline)</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">SUB</span>
                                </button>
                                <button type="button" @click="addNode('flow', 'Mapping Input', 'Mapping Input')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">M-IN</span>
                                        <span class="truncate">Mapping Input</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">SUB</span>
                                </button>
                                <button type="button" @click="addNode('flow', 'Mapping Output', 'Mapping Output')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-purple-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">M-OT</span>
                                        <span class="truncate">Mapping Output</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">SUB</span>
                                </button>
                            </div>
                        </details>

                        <!-- 5. SCRIPTING CATEGORY -->
                        <details class="group space-y-1">
                            <summary class="flex justify-between items-center cursor-pointer list-none p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-350 transition-colors">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded bg-violet-500 shrink-0"></span>
                                    Scripting
                                </span>
                                <span class="transition group-open:rotate-180 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                            </summary>
                            <div class="space-y-1 pl-3.5 pt-1 pb-2">
                                <button type="button" @click="addNode('scripting', 'Modified JavaScript Value', 'Modified JavaScript Value')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-violet-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">JS</span>
                                        <span class="truncate">Modified JS Value</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">CODE</span>
                                </button>
                                <button type="button" @click="addNode('scripting', 'User Defined Java Class', 'User Defined Java Class')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-violet-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">JV</span>
                                        <span class="truncate">UD Java Class</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">CODE</span>
                                </button>
                            </div>
                        </details>

                        <!-- 6. UTILITY CATEGORY -->
                        <details class="group space-y-1">
                            <summary class="flex justify-between items-center cursor-pointer list-none p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-700 dark:text-slate-350 transition-colors">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1.5 font-mono">
                                    <span class="w-2.5 h-2.5 rounded bg-slate-500 shrink-0"></span>
                                    Utility
                                </span>
                                <span class="transition group-open:rotate-180 text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                                </span>
                            </summary>
                            <div class="space-y-1 pl-3.5 pt-1 pb-2">
                                <button type="button" @click="addNode('utility', 'Execute SQL Script', 'Execute SQL Script')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-slate-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">SQL</span>
                                        <span class="truncate">Execute SQL Script</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">DB</span>
                                </button>
                                <button type="button" @click="addNode('utility', 'Write To Log', 'Write To Log')" class="w-full flex items-center justify-between p-2 bg-white dark:bg-[#161A25] hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-medium dark:text-white transition-colors shadow-sm mb-1">
                                    <div class="flex items-center gap-2 truncate">
                                        <span class="w-5 h-5 rounded bg-slate-500 text-white flex items-center justify-center text-[8px] font-bold font-mono">LOG</span>
                                        <span class="truncate">Write To Log</span>
                                    </div>
                                    <span class="text-[8px] text-slate-400 font-mono">LOG</span>
                                </button>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- 2. CANVAS INTERACTIVE GRID (Middle) -->
                <div 
                    class="flex-1 overflow-auto relative select-none bg-slate-50/50 dark:bg-[#12151E]/25"
                    style="background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px); background-size: 20px 20px;"
                    @mousemove="onMouseMove($event)"
                    @mouseup="stopNodeDrag()"
                >
                    <!-- Connections SVG overlay -->
                    <svg class="absolute inset-0 pointer-events-none w-full h-full z-0" wire:ignore>
                        <defs>
                            <marker id="arrow" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                                <path d="M 0 0 L 10 5 L 0 10 z" fill="#818cf8"/>
                            </marker>
                        </defs>
                        <g x-html="renderConnections()"></g>
                    </svg>

                    <!-- Render Canvas Nodes -->
                    <template x-for="node in canvas.nodes">
                        <div 
                            class="absolute w-44 bg-white dark:bg-[#161A25] border-2 rounded-xl p-3 shadow-md cursor-grab active:cursor-grabbing hover:shadow-lg transition-all"
                            :class="selectedNodeId === node.id ? 'border-indigo-650 ring-2 ring-indigo-500/10' : (node.validation?.status === 'error' ? 'border-red-500' : 'border-slate-200 dark:border-slate-800')"
                            :style="'left: ' + node.x + 'px; top: ' + node.y + 'px; z-index: 10;'"
                            @mousedown="startNodeDrag($event, node.id)"
                            @click.stop="selectNode(node.id)"
                            @dblclick="doubleClickNode(node)"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-2 truncate">
                                    <span 
                                        class="w-5 h-5 rounded flex items-center justify-center text-[7.5px] font-bold text-white shrink-0"
                                        :class="node.type === 'input' ? 'bg-blue-500' : (node.type === 'output' ? 'bg-emerald-500' : (node.type === 'transform' ? 'bg-amber-500' : (node.type === 'flow' ? 'bg-purple-500' : (node.type === 'scripting' ? 'bg-violet-500' : 'bg-slate-500'))))"
                                        x-text="node.name === 'Table Input' ? 'TI' : (node.name === 'CSV File Input' ? 'CSV' : (node.name === 'Select Values' ? 'SL' : (node.name === 'Formula' ? 'FX' : (node.name === 'Data Grid' ? 'DG' : (node.name === 'Calculator' ? 'CAL' : (node.name === 'Sort Rows' ? 'SR' : (node.name === 'Group By' ? 'GRP' : (node.name === 'Table Output' ? 'TO' : (node.name === 'Dummy' ? 'DM' : (node.name === 'Write To Log' ? 'LOG' : (node.type === 'input' ? 'IN' : (node.type === 'output' ? 'OUT' : 'T'))))))))))))"
                                    ></span>
                                    <span class="text-[11px] font-semibold dark:text-white truncate" x-text="node.label || node.name"></span>
                                </div>
                                
                                <div class="flex items-center gap-1 shrink-0">
                                    <!-- Edit Gear Button -->
                                    <button 
                                        type="button" 
                                        @click.stop="doubleClickNode(node)"
                                        class="p-0.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-indigo-500"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <button 
                                        type="button" 
                                        @click.stop="deleteNode(node.id)"
                                        class="p-0.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-400 hover:text-red-500 font-bold"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </div>
                            <!-- Validation warning badge -->
                            <template x-if="node.validation && node.validation.status !== 'valid'">
                                <div class="mt-1 flex items-center gap-1 text-[8.5px] font-semibold text-amber-600 dark:text-amber-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    <span class="truncate" :title="node.validation.messages.join(', ')" x-text="node.validation.messages[0]"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- 3. RIGHT SIDEBAR PROPERTIES -->
                <div class="w-72 border-l border-slate-200 dark:border-[#222735] bg-white dark:bg-[#12151E] p-5 space-y-5 overflow-y-auto shrink-0">
                    <template x-if="selectedNodeId && canvas.nodes.find(n => n.id === selectedNodeId)">
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider font-mono">Informasi Properti</h4>
                            
                            <!-- Display step detail -->
                            <div class="space-y-3 text-xs bg-slate-50 dark:bg-[#161A25]/50 p-3.5 rounded-xl border border-slate-150 dark:border-slate-850">
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">ID Node</span>
                                    <strong class="font-mono text-slate-700 dark:text-slate-300 select-all" x-text="selectedNodeId"></strong>
                                </div>
                                <div>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider block">Tipe Komponen</span>
                                    <strong class="text-indigo-650 dark:text-indigo-400 uppercase font-mono" x-text="canvas.nodes.find(n => n.id === selectedNodeId)?.name"></strong>
                                </div>
                            </div>

                            <div class="space-y-3.5">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Nama Step (Label)</label>
                                    <input 
                                        type="text" 
                                        @input="updateCanvasJson()"
                                        x-model="selectedNodeLabel"
                                        class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                                    />
                                </div>

                                <!-- Connect Target Step -->
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Hubungkan Aliran ke Step</label>
                                    <select 
                                        @change="
                                            let toId = $event.target.value;
                                            if (toId) {
                                                let exists = canvas.connections.some(c => c.from === selectedNodeId && c.to === toId);
                                                if (!exists) {
                                                    canvas.connections.push({ from: selectedNodeId, to: toId });
                                                    updateCanvasJson();
                                                    $wire.updateLivewire();
                                                }
                                                $event.target.value = '';
                                            }
                                        "
                                        class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-medium"
                                    >
                                        <option value="">-- Pilih Step Target --</option>
                                        <template x-for="n in canvas.nodes.filter(n => n.id !== selectedNodeId)">
                                            <option :value="n.id" x-text="n.label || n.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Outgoing List -->
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Aliran Keluar Aktif</label>
                                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                                        <template x-for="c in canvas.connections.filter(c => c.from === selectedNodeId)">
                                            <div class="flex justify-between items-center bg-slate-50 dark:bg-[#161A25]/60 hover:bg-slate-100 dark:hover:bg-slate-850 p-2 rounded-lg border border-slate-200/50 dark:border-slate-850 text-[10.5px] font-medium text-slate-700 dark:text-slate-350">
                                                <span class="truncate" x-text="'Aliran ke: ' + (canvas.nodes.find(n => n.id === c.to)?.label || c.to)"></span>
                                                <button 
                                                    type="button"
                                                    @click="
                                                        canvas.connections = canvas.connections.filter(conn => conn !== c);
                                                        updateCanvasJson();
                                                        $wire.updateLivewire();
                                                    " 
                                                    class="text-red-500 hover:text-red-750 font-bold shrink-0 text-xs px-1"
                                                >
                                                    &times;
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                
                                <!-- Spoon Config Button Trigger -->
                                <template x-if="selectedNodeId">
                                    <button 
                                        type="button"
                                        @click="doubleClickNode(canvas.nodes.find(n => n.id === selectedNodeId))"
                                        class="w-full py-2 bg-indigo-550 hover:bg-indigo-650 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        Buka Konfigurasi Lengkap
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedNodeId">
                        <div class="h-full flex items-center justify-center text-center text-slate-400 dark:text-slate-500 text-xs">
                            Pilih salah satu node langkah di kanvas untuk menampilkan atau mengubah detail properti.
                        </div>
                    </template>
                </div>
                @endif

                @if($workspaceTab === 'sql')
                    <div class="flex-1 p-6 bg-slate-50 dark:bg-[#161A25]/30 overflow-y-auto space-y-4 relative">
                        <!-- Tab Loading Overlay -->
                        <div wire:loading wire:target="$set('workspaceTab', 'sql')" class="absolute inset-0 bg-white/70 dark:bg-[#12151E]/70 backdrop-blur-sm z-20 flex flex-col items-center justify-center space-y-3">
                            <svg class="w-8 h-8 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-350">Memuat SQL Preview...</span>
                        </div>
                        <div class="max-w-4xl mx-auto bg-white dark:bg-[#12151E] p-6 rounded-xl border border-slate-200 dark:border-[#222735] shadow-sm space-y-3">
                            <div class="flex justify-between items-center text-[10px] text-slate-450 font-bold uppercase tracking-wider">
                                <span>SQL Extraction Query (dialek database)</span>
                                <span class="text-indigo-500 font-mono">PostgreSQL Preview</span>
                            </div>
                            <pre class="bg-black p-4 rounded-lg font-mono text-xs text-indigo-300 overflow-x-auto leading-relaxed select-all">{{ $this->getSqlQueryPreview() }}</pre>
                        </div>
                    </div>
                @endif

                @if($workspaceTab === 'json')
                    <div class="flex-1 p-6 bg-slate-50 dark:bg-[#161A25]/30 overflow-y-auto space-y-4 relative">
                        <!-- Tab Loading Overlay -->
                        <div wire:loading wire:target="$set('workspaceTab', 'json')" class="absolute inset-0 bg-white/70 dark:bg-[#12151E]/70 backdrop-blur-sm z-20 flex flex-col items-center justify-center space-y-3">
                            <svg class="w-8 h-8 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-350">Memuat JSON Definition...</span>
                        </div>
                        <div class="max-w-4xl mx-auto bg-white dark:bg-[#12151E] p-6 rounded-xl border border-slate-200 dark:border-[#222735] shadow-sm space-y-3">
                            <span class="text-[10px] text-slate-450 font-bold uppercase tracking-wider block font-mono">Struktur Data Internal JSON (PDI Engine Blueprint)</span>
                            <pre class="bg-black p-4 rounded-lg font-mono text-xs text-indigo-300 overflow-x-auto leading-normal select-all">{{ json_encode($this->getJsonDefinition(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                @endif

                @if($workspaceTab === 'airflow')
                    <div class="flex-1 p-6 bg-slate-50 dark:bg-[#161A25]/30 overflow-y-auto space-y-4 relative" x-data="{ 
                        copied: false,
                        copyCode() {
                            navigator.clipboard.writeText(this.$refs.dagCode.innerText);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        }
                    }">
                        <!-- Tab Loading Overlay -->
                        <div wire:loading wire:target="$set('workspaceTab', 'airflow')" class="absolute inset-0 bg-white/70 dark:bg-[#12151E]/70 backdrop-blur-sm z-20 flex flex-col items-center justify-center space-y-3">
                            <svg class="w-8 h-8 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-350">Menghasilkan Airflow DAG...</span>
                        </div>
                        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/python.min.js"></script>

                        <div class="max-w-4xl mx-auto bg-white dark:bg-[#12151E] p-6 rounded-xl border border-slate-200 dark:border-[#222735] shadow-sm space-y-4">
                            <div class="flex justify-between items-center text-[10px] text-slate-450 font-bold uppercase tracking-wider">
                                <span>Apache Airflow Python DAG Code</span>
                                <div class="flex gap-2.5">
                                    <!-- Copy Code Button -->
                                    <button 
                                        type="button"
                                        @click="copyCode()"
                                        class="px-3.5 py-1.5 bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white rounded-lg text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                        <span x-text="copied ? 'Disalin!' : 'Copy Code'"></span>
                                    </button>
                                    
                                    <!-- Download .py Button -->
                                    @if($isEditing && $selectedPipelineId)
                                        <a 
                                            href="{{ route('studio.pipelines.download-dag', $selectedPipelineId) }}"
                                            class="px-3.5 py-1.5 bg-indigo-650 text-white hover:bg-indigo-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            Download .py
                                        </a>
                                    @else
                                        @php
                                            $pipelineData = $this->getPipelineDataFromCanvas();
                                        @endphp
                                        <form method="POST" action="{{ route('studio.pipelines.download-dag-draft') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="pipeline_name" value="{{ $pipelineData['name'] }}">
                                            <input type="hidden" name="source_table" value="{{ $pipelineData['source_table'] }}">
                                            <input type="hidden" name="target_table" value="{{ $pipelineData['target_table'] }}">
                                            @foreach($pipelineData['transformations'] as $t)
                                                <input type="hidden" name="transformations[]" value="{{ $t }}">
                                            @endforeach
                                            @foreach($pipelineData['column_mapping'] as $index => $map)
                                                <input type="hidden" name="column_mapping[{{ $index }}][source]" value="{{ $map['source'] }}">
                                                <input type="hidden" name="column_mapping[{{ $index }}][target]" value="{{ $map['target'] }}">
                                            @endforeach
                                            <input type="hidden" name="schedule_interval" value="{{ $pipelineData['schedule_interval'] }}">
                                            
                                            <button 
                                                type="submit"
                                                class="px-3.5 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                Download .py
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            <div class="relative rounded-lg overflow-hidden border border-slate-200 dark:border-slate-800 bg-[#0d1117]">
                                <pre class="p-5 font-mono text-[11px] overflow-x-auto leading-relaxed text-slate-355"><code x-ref="dagCode" class="language-python" x-init="$nextTick(() => { if (typeof hljs !== 'undefined') { hljs.highlightElement($el); } })">{{ $this->getAirflowDagCode() }}</code></pre>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pentaho PDI (Spoon) style Configuration Modal for Select Values -->
        <div 
            x-show="isConfigModalOpen" 
            x-cloak
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 overflow-y-auto"
        >
            <div 
                class="bg-white dark:bg-[#12151E] border border-slate-200 dark:border-[#222735] rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden my-8 animate-in fade-in zoom-in-95 duration-200"
                @click.away="isConfigModalOpen = false"
            >
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-150 dark:border-[#222735] flex justify-between items-center bg-slate-50/50 dark:bg-[#161A25]/50 shrink-0">
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <svg class="w-4.5 h-4.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        <span x-text="configNode ? configNode.name + ' Configuration' : 'Step Configuration'"></span>
                    </h3>
                    <button @click="isConfigModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold">&times;</button>
                </div>

                <!-- Modal Body with Tabs -->
                <template x-if="configNode">
                    <div class="p-6 space-y-4 max-h-[calc(100vh-14rem)] overflow-y-auto">
                        <!-- Step Label Input -->
                        <div class="space-y-1">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block font-mono">Step Name</label>
                            <input 
                                type="text" 
                                x-model="configNode.label" 
                                class="w-full bg-slate-50 dark:bg-[#161A25]/50 border border-slate-250 dark:border-slate-800 rounded-lg p-2 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white font-mono font-bold"
                            />
                        </div>

                        <!-- 1. TABLE INPUT / DATABASE INPUT EDITOR -->
                        <template x-if="configNode.type === 'input' || configNode.name === 'source' || configNode.name === 'Table Input' || configNode.name === 'Database Input'">
                            <div class="space-y-4" x-data="{ 
                                isPreviewing: false, 
                                isSelectingTable: false,
                                testConnStatus: '',
                                previewRows: [] 
                            }">
                                <!-- Connection Selection -->
                                <div class="grid grid-cols-2 gap-4 text-xs">
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Database Connection</label>
                                        <select 
                                            class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded-lg p-2 dark:text-white font-mono"
                                            x-model="configNode.settings.connection_id"
                                        >
                                            <option value="">-- Choose Connection --</option>
                                            @foreach($connections as $c)
                                                <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['driver'] }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="space-y-1 flex items-end">
                                        <button 
                                            type="button" 
                                            @click="
                                                testConnStatus = 'Connecting...';
                                                if (configNode.settings.connection_id) {
                                                    $wire.testConnection(configNode.settings.connection_id).then(res => {
                                                        testConnStatus = res.message;
                                                    });
                                                } else {
                                                    testConnStatus = 'Select connection first.';
                                                }
                                            "
                                            class="px-3.5 py-2 bg-indigo-550 hover:bg-indigo-650 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center justify-center gap-1.5"
                                        >
                                            Test Connection
                                        </button>
                                        <span class="ml-3 text-xs font-extrabold text-emerald-600 dark:text-emerald-450" x-text="testConnStatus"></span>
                                    </div>
                                </div>

                                <!-- SQL Query Editor -->
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">SQL Query</label>
                                        <div class="flex gap-2">
                                            <button 
                                                type="button" 
                                                @click="isSelectingTable = !isSelectingTable"
                                                class="px-2.5 py-1 bg-slate-50 hover:bg-slate-105 text-slate-700 dark:bg-slate-800 dark:text-slate-350 border border-slate-200 dark:border-slate-750 rounded text-[10px] font-bold transition-colors"
                                            >
                                                Select Table
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table Selector Popover -->
                                    <div x-show="isSelectingTable" class="bg-slate-50 dark:bg-[#161A25] p-3 rounded-lg border border-slate-200 dark:border-slate-800 space-y-2 max-h-40 overflow-y-auto">
                                        <span class="text-[9px] font-bold text-slate-400 block uppercase">Pick Table Schema:</span>
                                        <div class="grid grid-cols-2 gap-1.5 text-xs font-mono">
                                            @foreach($sourceTables as $t)
                                                <button 
                                                    type="button"
                                                    @click="
                                                        configNode.settings.sql = 'SELECT * FROM ' + '{{ $t }}';
                                                        isSelectingTable = false;
                                                    "
                                                    class="text-left p-1.5 bg-white dark:bg-slate-900 border border-slate-150 dark:border-slate-800 hover:border-indigo-500 rounded truncate"
                                                >
                                                    {{ $t }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <textarea 
                                        x-model="configNode.settings.sql"
                                        rows="8"
                                        class="w-full bg-[#0D1117] text-emerald-400 font-mono text-xs p-3 rounded-xl focus:outline-none border border-slate-750 focus:ring-2 focus:ring-indigo-500"
                                        placeholder="SELECT customer_id, first_name, last_name, email FROM customer WHERE active = 1"
                                    ></textarea>
                                </div>

                                <!-- Toolbar buttons -->
                                <div class="flex gap-3 pt-2">
                                    <button 
                                        type="button" 
                                        @click="
                                            if (configNode.settings.connection_id && configNode.settings.sql) {
                                                $wire.previewSqlQuery(configNode.settings.connection_id, configNode.settings.sql).then(res => {
                                                    configNode.metadata.fieldsOut = res.columns.map(f => ({ name: f, type: 'String' }));
                                                    alert('Fields propagated successfully: ' + res.columns.join(', '));
                                                });
                                            } else {
                                                alert('Please select connection and write SQL first.');
                                            }
                                        "
                                        class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm"
                                    >
                                        Get Fields
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="
                                            isPreviewing = !isPreviewing;
                                            if (isPreviewing) {
                                                if (configNode.settings.connection_id && configNode.settings.sql) {
                                                    $wire.previewSqlQuery(configNode.settings.connection_id, configNode.settings.sql).then(res => {
                                                        previewRows = res.rows;
                                                    });
                                                } else {
                                                    previewRows = [];
                                                    alert('Please select connection and write SQL first.');
                                                }
                                            }
                                        "
                                        class="px-3.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold transition-colors"
                                    >
                                        Preview Query
                                    </button>
                                </div>

                                <!-- Preview Section -->
                                <div x-show="isPreviewing" class="space-y-2 border border-slate-200 dark:border-slate-800 rounded-xl p-4 bg-slate-50/50 dark:bg-[#12151E]/20">
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase font-mono">Mock Preview Data:</span>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-[10px] font-mono border border-slate-150 dark:border-slate-800">
                                            <thead class="bg-slate-100 dark:bg-slate-850">
                                                <tr>
                                                    <template x-for="(val, col) in previewRows[0]">
                                                        <th class="px-2.5 py-1.5 text-left border-b dark:border-slate-800 text-slate-650" x-text="col"></th>
                                                    </template>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y dark:divide-slate-800 bg-white dark:bg-[#12151E]">
                                                <template x-for="row in previewRows">
                                                    <tr>
                                                        <template x-for="(val, col) in row">
                                                            <td class="px-2.5 py-1.5 border-r dark:border-slate-800 text-slate-750 dark:text-slate-350" x-text="val"></td>
                                                        </template>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 2. SELECT VALUES / RENAME FIELDS EDITOR -->
                        <template x-if="configNode.name === 'Select Values' || configNode.name === 'Rename Fields'">
                            <div class="space-y-4">
                                <div class="flex border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold">
                                    <button 
                                        type="button" 
                                        @click="activeTab = 'select_alter'"
                                        class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider font-semibold"
                                        :class="activeTab === 'select_alter' ? 'border-indigo-500 text-indigo-550' : 'border-transparent text-slate-500 hover:text-slate-750'"
                                    >
                                        Select & Alter
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="activeTab = 'remove'"
                                        class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider font-semibold"
                                        :class="activeTab === 'remove' ? 'border-indigo-500 text-indigo-550' : 'border-transparent text-slate-500 hover:text-slate-750'"
                                    >
                                        Remove
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="activeTab = 'metadata'"
                                        class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider font-semibold"
                                        :class="activeTab === 'metadata' ? 'border-indigo-500 text-indigo-550' : 'border-transparent text-slate-500 hover:text-slate-750'"
                                    >
                                        Metadata
                                    </button>
                                </div>

                                <!-- Tab 1: Select & Alter -->
                                <div x-show="activeTab === 'select_alter'" class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Fields to select and alter</span>
                                        <div class="flex gap-2">
                                            <button 
                                                type="button" 
                                                @click="getFieldsSelectAlter()"
                                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded text-[10px] font-bold transition-all"
                                            >
                                                Get Fields
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="addSelectAlterRow()"
                                                class="px-2.5 py-1 bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-350 border border-slate-200 dark:border-slate-750 rounded text-[10px] font-bold transition-all"
                                            >
                                                Add Row
                                            </button>
                                        </div>
                                    </div>

                                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                                            <thead class="bg-slate-50 dark:bg-[#1C212E]">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Field Name</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Rename To</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Length</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Precision</th>
                                                    <th class="px-3 py-2 text-center text-slate-500 dark:text-slate-400 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800 bg-white dark:bg-[#12151E] font-mono text-[11px]">
                                                <template x-for="(row, index) in configNode.settings.select_alter" :key="index">
                                                    <tr>
                                                        <td class="px-3 py-1.5">
                                                            <select x-model="row.field" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white">
                                                                <option value="">-- Choose Field --</option>
                                                                <template x-for="f in getIncomingFields()">
                                                                    <option :value="f.name || f" x-text="f.name || f" :selected="(f.name || f) === row.field"></option>
                                                                </template>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="text" x-model="row.rename" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="number" x-model="row.length" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="number" x-model="row.precision" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5 text-center">
                                                            <button type="button" @click="configNode.settings.select_alter.splice(index, 1)" class="text-red-500 font-bold hover:text-red-755 text-base">&times;</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab 2: Remove -->
                                <div x-show="activeTab === 'remove'" class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Fields to remove</span>
                                        <div class="flex gap-2">
                                            <button 
                                                type="button" 
                                                @click="getFieldsRemove()"
                                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded text-[10px] font-bold transition-all"
                                            >
                                                Get Fields
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="addRemoveRow()"
                                                class="px-2.5 py-1 bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-350 border border-slate-200 dark:border-slate-750 rounded text-[10px] font-bold transition-all"
                                            >
                                                Add Row
                                            </button>
                                        </div>
                                    </div>

                                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden max-w-lg">
                                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                                            <thead class="bg-slate-50 dark:bg-[#1C212E]">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Field Name</th>
                                                    <th class="px-3 py-2 text-center text-slate-500 dark:text-slate-400 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800 bg-white dark:bg-[#12151E] font-mono text-[11px]">
                                                <template x-for="(row, index) in configNode.settings.remove" :key="index">
                                                    <tr>
                                                        <td class="px-3 py-1.5">
                                                            <select x-model="row.field" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white">
                                                                <option value="">-- Choose Field --</option>
                                                                <template x-for="f in getIncomingFields()">
                                                                    <option :value="f.name || f" x-text="f.name || f" :selected="(f.name || f) === row.field"></option>
                                                                </template>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-1.5 text-center">
                                                            <button type="button" @click="configNode.settings.remove.splice(index, 1)" class="text-red-500 font-bold hover:text-red-755 text-base">&times;</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tab 3: Metadata -->
                                <div x-show="activeTab === 'metadata'" class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Metadata modifications</span>
                                        <div class="flex gap-2">
                                            <button 
                                                type="button" 
                                                @click="getFieldsMetadata()"
                                                class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded text-[10px] font-bold transition-all"
                                            >
                                                Get Fields
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="addMetadataRow()"
                                                class="px-2.5 py-1 bg-slate-50 hover:bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-350 border border-slate-200 dark:border-slate-750 rounded text-[10px] font-bold transition-all"
                                            >
                                                Add Row
                                            </button>
                                        </div>
                                    </div>

                                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                                            <thead class="bg-slate-50 dark:bg-[#1C212E]">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Field</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Type</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Length</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Precision</th>
                                                    <th class="px-3 py-2 text-center text-slate-500 dark:text-slate-400 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800 bg-white dark:bg-[#12151E] font-mono text-[11px]">
                                                <template x-for="(row, index) in configNode.settings.metadata" :key="index">
                                                    <tr>
                                                        <td class="px-3 py-1.5">
                                                            <select x-model="row.field" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white">
                                                                <option value="">-- Choose Field --</option>
                                                                <template x-for="f in getIncomingFields()">
                                                                    <option :value="f.name || f" x-text="f.name || f" :selected="(f.name || f) === row.field"></option>
                                                                </template>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <select x-model="row.type" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white">
                                                                <option value="String">String</option>
                                                                <option value="Integer">Integer</option>
                                                                <option value="Number">Number</option>
                                                                <option value="Date">Date</option>
                                                                <option value="Timestamp">Timestamp</option>
                                                                <option value="Boolean">Boolean</option>
                                                                <option value="BigNumber">BigNumber</option>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="number" x-model="row.length" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="number" x-model="row.precision" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5 text-center">
                                                            <button type="button" @click="configNode.settings.metadata.splice(index, 1)" class="text-red-500 font-bold hover:text-red-755 text-base">&times;</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 3. FORMULA STEP EDITOR -->
                        <template x-if="configNode.name === 'Formula'">
                            <div class="space-y-4" x-data="{ 
                                activeFormulaIdx: 0,
                                addFormula() {
                                    if (!configNode.settings.formulas) configNode.settings.formulas = [];
                                    configNode.settings.formulas.push({ field_name: 'new_field', type: 'String', formula: '', length: '', precision: '' });
                                    this.activeFormulaIdx = configNode.settings.formulas.length - 1;
                                }
                            }">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Formula Definitions</span>
                                    <button 
                                        type="button" 
                                        @click="addFormula()"
                                        class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[10px] font-bold transition-colors"
                                    >
                                        + Add Formula
                                    </button>
                                </div>

                                <div class="grid grid-cols-3 gap-6">
                                    <!-- List of defined formulas -->
                                    <div class="col-span-1 border border-slate-200 dark:border-slate-800 rounded-xl p-3 space-y-2 max-h-80 overflow-y-auto bg-slate-50/40 dark:bg-slate-900/10">
                                        <span class="text-[9px] font-bold text-slate-400 block uppercase font-mono">Formula List:</span>
                                        <template x-for="(f, idx) in configNode.settings.formulas" :key="idx">
                                            <div 
                                                @click="activeFormulaIdx = idx"
                                                class="p-2 rounded-lg cursor-pointer flex justify-between items-center text-xs font-semibold border"
                                                :class="activeFormulaIdx === idx ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-900/30 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:bg-slate-100'"
                                            >
                                                <span class="truncate" x-text="f.field_name || ('Formula ' + (idx + 1))"></span>
                                                <button 
                                                    type="button" 
                                                    @click.stop="configNode.settings.formulas.splice(idx, 1); activeFormulaIdx = 0;"
                                                    class="text-red-500 hover:text-red-750 font-bold ml-2 text-xs"
                                                >
                                                    &times;
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Formula Builder Editor (Center/Right) -->
                                    <div class="col-span-2 space-y-3" x-show="configNode.settings.formulas && configNode.settings.formulas[activeFormulaIdx]">
                                        <template x-if="configNode.settings.formulas[activeFormulaIdx]">
                                            <div class="space-y-3 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                                                <!-- Field Details -->
                                                <div class="grid grid-cols-3 gap-3">
                                                    <div class="space-y-1">
                                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">New Field Name</label>
                                                        <input type="text" x-model="configNode.settings.formulas[activeFormulaIdx].field_name" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1.5 text-xs dark:text-white font-mono" />
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Value Type</label>
                                                        <select x-model="configNode.settings.formulas[activeFormulaIdx].type" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1.5 text-xs dark:text-white font-mono">
                                                            <option value="String">String</option>
                                                            <option value="Integer">Integer</option>
                                                            <option value="Number">Number</option>
                                                            <option value="Date">Date</option>
                                                            <option value="Boolean">Boolean</option>
                                                        </select>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Len / Prec</label>
                                                        <div class="flex gap-1.5">
                                                            <input type="number" x-model="configNode.settings.formulas[activeFormulaIdx].length" placeholder="L" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1.5 text-xs dark:text-white" />
                                                            <input type="number" x-model="configNode.settings.formulas[activeFormulaIdx].precision" placeholder="P" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1.5 text-xs dark:text-white" />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Formula Editor Layout -->
                                                <div class="grid grid-cols-3 gap-4 border border-slate-200 dark:border-slate-800 rounded-xl p-3 bg-slate-50/30 dark:bg-[#161A25]/30">
                                                    <!-- Left: Fields list -->
                                                    <div class="col-span-1 space-y-1">
                                                        <span class="text-[8px] font-bold text-slate-400 block uppercase font-mono">Fields</span>
                                                        <div class="space-y-1 max-h-40 overflow-y-auto">
                                                            <template x-for="f in getIncomingFields()">
                                                                <button 
                                                                    type="button"
                                                                    @click="configNode.settings.formulas[activeFormulaIdx].formula += '[' + (f.name || f) + ']'"
                                                                    class="w-full text-left px-2 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-indigo-500 rounded text-[10px] font-mono truncate"
                                                                    x-text="f.name || f"
                                                                ></button>
                                                            </template>
                                                        </div>
                                                    </div>

                                                    <!-- Center/Right: Textarea -->
                                                    <div class="col-span-2 space-y-2">
                                                        <span class="text-[8px] font-bold text-slate-400 block uppercase font-mono">Formula Text</span>
                                                        <textarea 
                                                            x-model="configNode.settings.formulas[activeFormulaIdx].formula"
                                                            rows="4"
                                                            class="w-full bg-[#0D1117] text-amber-400 font-mono text-xs p-2.5 rounded-lg border border-slate-750 focus:outline-none"
                                                            placeholder="IF([sales_amount] > 1000, [sales_amount] * 0.1, 0)"
                                                        ></textarea>
                                                        
                                                        <!-- Short Functions helper list -->
                                                        <div class="space-y-1">
                                                            <span class="text-[8px] font-bold text-slate-400 block uppercase font-mono">Helper Functions:</span>
                                                            <div class="flex flex-wrap gap-1">
                                                                <template x-for="func in ['IF( , , )', 'ABS()', 'ROUND()', 'CONCAT()', 'UPPER()', 'LOWER()', 'DATE_DIFF()']">
                                                                    <button 
                                                                        type="button"
                                                                        @click="configNode.settings.formulas[activeFormulaIdx].formula += func"
                                                                        class="px-1.5 py-0.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded text-[9px] font-mono font-bold"
                                                                        x-text="func.split('(')[0]"
                                                                    ></button>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 4. DATA GRID EDITOR -->
                        <template x-if="configNode.name === 'Data Grid'">
                            <div class="space-y-4" x-data="{ 
                                gridTab: 'meta',
                                addGridColumn() {
                                    if (!configNode.settings.fields) configNode.settings.fields = [];
                                    configNode.settings.fields.push({ name: 'column_new', type: 'String', length: '', precision: '' });
                                },
                                addGridRow() {
                                    if (!configNode.settings.data) configNode.settings.data = [];
                                    let newRow = {};
                                    (configNode.settings.fields || []).forEach(f => {
                                        newRow[f.name] = '';
                                    });
                                    configNode.settings.data.push(newRow);
                                }
                            }">
                                <!-- Grid Tabs -->
                                <div class="flex border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold">
                                    <button 
                                        type="button" 
                                        @click="gridTab = 'meta'"
                                        class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider"
                                        :class="gridTab === 'meta' ? 'border-indigo-500 text-indigo-550' : 'border-transparent text-slate-500 hover:text-slate-750'"
                                    >
                                        Meta (Columns)
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="gridTab = 'data'"
                                        class="px-4 py-2 border-b-2 transition-all uppercase tracking-wider"
                                        :class="gridTab === 'data' ? 'border-indigo-500 text-indigo-550' : 'border-transparent text-slate-500 hover:text-slate-750'"
                                    >
                                        Data (Rows)
                                    </button>
                                </div>

                                <!-- Grid Meta Tab Content -->
                                <div x-show="gridTab === 'meta'" class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Define Grid Columns</span>
                                        <button 
                                            type="button" 
                                            @click="addGridColumn()"
                                            class="px-2.5 py-1 bg-slate-550 hover:bg-slate-650 text-slate-700 dark:text-slate-300 border border-slate-250 dark:border-slate-750 rounded text-[10px] font-bold"
                                        >
                                            Add Column
                                        </button>
                                    </div>

                                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                                            <thead class="bg-slate-50 dark:bg-[#1C212E]">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Column Name</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Type</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Length</th>
                                                    <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400">Precision</th>
                                                    <th class="px-3 py-2 text-center text-slate-500 dark:text-slate-400 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800 bg-white dark:bg-[#12151E] font-mono text-[11px]">
                                                <template x-for="(col, index) in configNode.settings.fields" :key="index">
                                                    <tr>
                                                        <td class="px-3 py-1.5">
                                                            <input type="text" x-model="col.name" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <select x-model="col.type" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white">
                                                                <option value="String">String</option>
                                                                <option value="Integer">Integer</option>
                                                                <option value="Number">Number</option>
                                                                <option value="Date">Date</option>
                                                                <option value="Boolean">Boolean</option>
                                                            </select>
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="number" x-model="col.length" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5">
                                                            <input type="number" x-model="col.precision" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                        </td>
                                                        <td class="px-3 py-1.5 text-center">
                                                            <button type="button" @click="configNode.settings.fields.splice(index, 1)" class="text-red-500 font-bold hover:text-red-755 text-base">&times;</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Grid Data Tab Content -->
                                <div x-show="gridTab === 'data'" class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Manual Grid Rows</span>
                                        <button 
                                            type="button" 
                                            @click="addGridRow()"
                                            class="px-2.5 py-1 bg-slate-550 hover:bg-slate-650 text-slate-700 dark:text-slate-350 border border-slate-250 dark:border-slate-750 rounded text-[10px] font-bold"
                                        >
                                            Add Row
                                        </button>
                                    </div>

                                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                                            <thead class="bg-slate-50 dark:bg-[#1C212E]">
                                                <tr>
                                                    <template x-for="col in configNode.settings.fields">
                                                        <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400" x-text="col.name"></th>
                                                    </template>
                                                    <th class="px-3 py-2 text-center text-slate-500 dark:text-slate-400 w-12"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-150 dark:divide-slate-800 bg-white dark:bg-[#12151E] font-mono text-[11px]">
                                                <template x-for="(row, rIndex) in configNode.settings.data" :key="rIndex">
                                                    <tr>
                                                        <template x-for="col in configNode.settings.fields">
                                                            <td class="px-3 py-1.5">
                                                                <input type="text" x-model="row[col.name]" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white" />
                                                            </td>
                                                        </template>
                                                        <td class="px-3 py-1.5 text-center">
                                                            <button type="button" @click="configNode.settings.data.splice(rIndex, 1)" class="text-red-500 font-bold hover:text-red-755 text-base">&times;</button>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 5. DEFAULT/GENERIC EDITOR FOR OTHER STEPS -->
                        <template x-if="configNode.type !== 'input' && configNode.type !== 'output' && !['Table Input', 'Database Input', 'Select Values', 'Rename Fields', 'Formula', 'Data Grid'].includes(configNode.name)">
                            <div class="space-y-4">
                                <!-- Sort Rows configuration -->
                                <template x-if="configNode.name === 'Sort Rows'">
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider font-mono">Fields to Sort</span>
                                            <button 
                                                type="button" 
                                                @click="if (!configNode.settings.fields) configNode.settings.fields = []; configNode.settings.fields.push({ field: '', ascending: 'Y' })"
                                                class="px-2.5 py-1 bg-slate-550 hover:bg-slate-650 text-slate-700 dark:text-slate-350 border border-slate-250 dark:border-slate-750 rounded text-[10px] font-bold"
                                            >
                                                Add Sort Field
                                            </button>
                                        </div>
                                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-xs">
                                                <thead class="bg-slate-50 dark:bg-[#1C212E]">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400 font-mono">Field Name</th>
                                                        <th class="px-3 py-2 text-left font-bold text-slate-500 dark:text-slate-400 font-mono">Ascending</th>
                                                        <th class="px-3 py-2 text-center text-slate-500 dark:text-slate-400 w-12"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-150 dark:divide-slate-800 bg-white dark:bg-[#12151E]">
                                                    <template x-for="(sf, idx) in configNode.settings.fields" :key="idx">
                                                        <tr>
                                                            <td class="px-3 py-1.5">
                                                                <select x-model="sf.field" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white font-mono">
                                                                    <option value="">-- Choose Field --</option>
                                                                    <template x-for="f in getIncomingFields()">
                                                                        <option :value="f.name || f" x-text="f.name || f" :selected="(f.name || f) === sf.field"></option>
                                                                    </template>
                                                                </select>
                                                            </td>
                                                            <td class="px-3 py-1.5">
                                                                <select x-model="sf.ascending" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1 dark:text-white">
                                                                    <option value="Y">Yes</option>
                                                                    <option value="N">No</option>
                                                                </select>
                                                            </td>
                                                            <td class="px-3 py-1.5 text-center">
                                                                <button type="button" @click="configNode.settings.fields.splice(idx, 1)" class="text-red-500 font-bold hover:text-red-755 text-base">&times;</button>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>

                                <!-- Table Output / Insert Update Load Target settings -->
                                <template x-if="configNode.type === 'output' || configNode.name === 'Table Output' || configNode.name === 'Insert Update'">
                                    <div class="space-y-4">
                                        <h5 class="text-xs font-bold text-slate-700 dark:text-slate-350 font-mono border-b dark:border-slate-800 pb-2">Target Data Warehouse Table Load</h5>
                                        <div class="grid grid-cols-2 gap-4 text-xs">
                                            <div class="space-y-1">
                                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Target Connection</label>
                                                <select class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1.5 dark:text-white font-mono" x-model="configNode.settings.connection_id">
                                                    <option value="">-- Choose Connection --</option>
                                                    @foreach($connections as $c)
                                                        <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Target Table Name</label>
                                                <input type="text" x-model="configNode.settings.target_table" class="w-full bg-slate-50 dark:bg-[#1C212E] border border-slate-250 dark:border-slate-850 rounded p-1.5 dark:text-white font-mono font-bold" placeholder="dim_customer" />
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Code scripting tab (Modified JavaScript Value) -->
                                <template x-if="configNode.name === 'Modified JavaScript Value'">
                                    <div class="space-y-2">
                                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block font-mono">Modified JavaScript Value Editor</label>
                                        <textarea 
                                            x-model="configNode.settings.js"
                                            rows="8"
                                            class="w-full bg-[#0D1117] text-purple-355 font-mono text-xs p-3 rounded-xl border border-slate-750 focus:outline-none"
                                            placeholder="// Write JavaScript. e.g. var domain = email.split('@')[1];"
                                        ></textarea>
                                    </div>
                                </template>

                                <!-- Fallback standard parameters placeholder -->
                                <template x-if="!['Sort Rows', 'Table Output', 'Insert Update', 'Modified JavaScript Value'].includes(configNode.name)">
                                    <div class="space-y-3">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block font-mono">Step Details & Trace</span>
                                        <div class="bg-slate-50 dark:bg-[#161A25]/50 border border-slate-200 dark:border-slate-850 rounded-xl p-4 text-xs space-y-2 text-slate-500">
                                            <p>This component utilizes standard visual metadata mapper definitions.</p>
                                            <p>Incoming fields: <strong class="font-mono text-indigo-600 dark:text-indigo-400" x-text="getIncomingFields().map(f => f.name || f).join(', ') || 'None'"></strong></p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-150 dark:border-[#222735] flex justify-end gap-3 shrink-0">
                    <button 
                        type="button" 
                        @click="isConfigModalOpen = false" 
                        class="px-4 py-2 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-[#1C212E] rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-350 transition-colors"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="saveConfig()"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm"
                    >
                        Simpan Konfigurasi
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
