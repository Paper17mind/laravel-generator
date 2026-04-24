const ProjectDetails = {
    props: ['selectedProject', 'projectViewMode', 'tables', 'relationships'],
    emits: ['delete-project', 'save-project', 'change-view-mode', 'select-table', 'add-table', 'start-drag'],
    data() {
        return {
            isFullscreen: false,
            zoom: 1,
            pan: { x: 0, y: 0 },
            isPanning: false,
            lastMouse: { x: 0, y: 0 },
            tablePositions: {},
            dragging: null,
        };
    },
    computed: {
        worldStyle() {
            // Trick: set actual layout size to match the scaled size
            // so the canvas scrollbar (if any) reflects real content size
            return {
                transform: `translate(${this.pan.x}px, ${this.pan.y}px) scale(${this.zoom})`,
                transformOrigin: '0 0',
                position: 'absolute',
                top: 0,
                left: 0,
            };
        }
    },
    methods: {
        getTableNodePosition(table) {
            if (!this.tablePositions[table.id]) {
                const index = this.tables.findIndex(t => t.id === table.id);
                const cols = 5;
                this.tablePositions[table.id] = {
                    x: 40 + (index % cols) * 240,
                    y: 40 + Math.floor(index / cols) * 220,
                };
            }
            const pos = this.tablePositions[table.id];
            return { left: `${pos.x}px`, top: `${pos.y}px`, position: 'absolute' };
        },
        drawRelationLine(rel) {
            const source = this.tablePositions[rel.sourceId];
            const target = this.tablePositions[rel.targetId];
            if (!source || !target) return '';
            const sx = source.x + 120;
            const sy = source.y + 40;
            const tx = target.x + 120;
            const ty = target.y + 40;
            return `M ${sx} ${sy} C ${sx} ${sy + 60}, ${tx} ${ty - 60}, ${tx} ${ty}`;
        },
        onWheel(event) {
            event.preventDefault();
            const delta = event.deltaY > 0 ? -0.08 : 0.08;
            const newZoom = Math.min(2.5, Math.max(0.2, +(this.zoom + delta).toFixed(2)));

            // Zoom toward cursor position
            const rect = this.$refs.erdCanvas.getBoundingClientRect();
            const mouseX = event.clientX - rect.left;
            const mouseY = event.clientY - rect.top;

            // Adjust pan so zoom centers on mouse
            const zoomRatio = newZoom / this.zoom;
            this.pan.x = mouseX - zoomRatio * (mouseX - this.pan.x);
            this.pan.y = mouseY - zoomRatio * (mouseY - this.pan.y);
            this.zoom = newZoom;
        },
        startPan(event) {
            // Only pan if clicking on canvas background (not a table)
            if (event.target === this.$refs.erdCanvas || event.target.classList.contains('erd-svg-layer')) {
                this.isPanning = true;
                this.lastMouse = { x: event.clientX, y: event.clientY };
            }
        },
        onPan(event) {
            // Auto-stop if mouse button was released outside the canvas
            if (event.buttons === 0) {
                this.isPanning = false;
                this.dragging = null;
                return;
            }
            if (this.isPanning) {
                this.pan.x += event.clientX - this.lastMouse.x;
                this.pan.y += event.clientY - this.lastMouse.y;
                this.lastMouse = { x: event.clientX, y: event.clientY };
            }
            if (this.dragging) {
                const dx = (event.clientX - this.lastMouse.x) / this.zoom;
                const dy = (event.clientY - this.lastMouse.y) / this.zoom;
                this.tablePositions[this.dragging] = {
                    x: this.tablePositions[this.dragging].x + dx,
                    y: this.tablePositions[this.dragging].y + dy,
                };
                this.lastMouse = { x: event.clientX, y: event.clientY };
            }
        },
        stopPan(e) {
            e.preventDefault();
            e.stopPropagation();
            this.isPanning = false;
            this.dragging = null;
        },
        startDrag(table, event) {
            event.stopPropagation();
            if (!this.tablePositions[table.id]) {
                this.getTableNodePosition(table);
            }
            this.dragging = table.id;
            this.lastMouse = { x: event.clientX, y: event.clientY };
        },
        resetZoom() { this.zoom = 1; this.pan = { x: 0, y: 0 }; },
        zoomIn()  { this.zoom = Math.min(2.5, +(this.zoom + 0.1).toFixed(2)); },
        zoomOut() { this.zoom = Math.max(0.2, +(this.zoom - 0.1).toFixed(2)); },
        fitAll() {
            if (!this.tables.length) return;
            // Ensure all positions are initialized
            this.tables.forEach(t => this.getTableNodePosition(t));

            // Find bounding box of all nodes
            const NODE_W = 200, NODE_H_APPROX = 30 + (8 * 22); // header + avg columns
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            this.tables.forEach(t => {
                const p = this.tablePositions[t.id];
                if (!p) return;
                const nodeH = 30 + Math.max(3, (t.child || []).length) * 22;
                minX = Math.min(minX, p.x);
                minY = Math.min(minY, p.y);
                maxX = Math.max(maxX, p.x + NODE_W);
                maxY = Math.max(maxY, p.y + nodeH);
            });

            const canvas = this.$refs.erdCanvas;
            if (!canvas) return;
            const cW = canvas.clientWidth;
            const cH = canvas.clientHeight;
            const contentW = maxX - minX + 80;
            const contentH = maxY - minY + 80;
            const newZoom = Math.min(2, Math.max(0.1, Math.min(cW / contentW, cH / contentH)));

            this.zoom = +newZoom.toFixed(2);
            this.pan.x = (cW - contentW * newZoom) / 2 - minX * newZoom;
            this.pan.y = (cH - contentH * newZoom) / 2 - minY * newZoom;
        },
        toggleFullscreen() {
            const el = this.$refs.erdWrapper;
            if (!this.isFullscreen) {
                (el.requestFullscreen || el.webkitRequestFullscreen).call(el);
                this.isFullscreen = true;
            } else {
                (document.exitFullscreen || document.webkitExitFullscreen).call(document);
                this.isFullscreen = false;
            }
        },
        onSelectTable(table) {
            if (!this.dragging) this.$emit('select-table', table);
        }
    },
    template: `
        <div v-if="selectedProject">
            <div class="top-bar">
                <div class="page-title">
                    <h1>{{ selectedProject.name }}</h1>
                    <p>Project Dashboard & Settings</p>
                </div>
                <div class="actions">
                    <button class="btn btn-danger" @click="$emit('delete-project', selectedProject.id)">
                        <i class="mdi mdi-delete"></i>
                        Delete Project
                    </button>
                </div>
            </div>

            <div class="glass-card">
                <div class="card-header">
                    <h2 style="font-size: 1.25rem;">Project Settings</h2>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                    <div class="input-group">
                        <label>Project Name</label>
                        <input type="text" v-model="selectedProject.name" class="form-control" placeholder="e.g. My Awesome Project">
                    </div>
                    <div class="input-group">
                        <label>Backend Framework</label>
                        <select v-model="selectedProject.backend_framework" class="form-control">
                            <option value="laravel">Laravel</option>
                            <option value="adonis">Adonis</option>
                            <option value="express">Express</option>
                            <option value="go">Go (Fiber/Gin)</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Frontend Framework</label>
                        <select v-model="selectedProject.frontend_framework" class="form-control">
                            <option value="vue3">Vue 3</option>
                            <option value="react">React</option>
                            <option value="svelte">Svelte</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button class="btn btn-secondary" @click="$emit('save-project')">Save Project</button>
                </div>
            </div>

            <div class="glass-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.25rem;">Project Data Schema</h2>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <div style="display: flex; gap: 0.5rem; background: rgba(0,0,0,0.2); padding: 0.25rem; border-radius: 0.5rem;">
                            <button class="btn" :class="projectViewMode === 'grid' ? 'btn-primary' : 'btn-secondary'" style="padding: 0.3rem 0.75rem; font-size: 0.8rem;" @click="$emit('change-view-mode', 'grid')">
                                <i class="mdi mdi-view-grid"></i> Grid
                            </button>
                            <button class="btn" :class="projectViewMode === 'visual' ? 'btn-primary' : 'btn-secondary'" style="padding: 0.3rem 0.75rem; font-size: 0.8rem;" @click="$emit('change-view-mode', 'visual')">
                                <i class="mdi mdi-vector-arrange-below"></i> Visual (ERD)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="projectViewMode === 'grid'" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    <div v-for="table in tables" :key="table.id" class="glass-card" style="padding: 1.5rem; text-align: center; cursor: pointer; border: 1px solid var(--border);" @click="$emit('select-table', table)">
                        <i class="mdi mdi-table" style="font-size: 2rem; color: var(--accent); margin-bottom: 0.5rem; display: block;"></i>
                        <span style="font-weight: 500;">{{ table.name }}</span>
                        <p style="font-size: 0.8rem; color: var(--text-secondary);">{{ (table.child || []).length }} Columns</p>
                    </div>
                    <div class="glass-card" style="padding: 1.5rem; text-align: center; cursor: pointer; border: 1px dashed var(--border); display: flex; flex-direction: column; justify-content: center; align-items: center;" @click="$emit('add-table')">
                        <i class="mdi mdi-plus" style="font-size: 2rem; color: var(--text-secondary); margin-bottom: 0.5rem;"></i>
                        <span style="color: var(--text-secondary);">Add New Table</span>
                    </div>
                </div>

                <!-- Visual ERD View -->
                <div v-else ref="erdWrapper">
                    <!-- Toolbar -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; margin-bottom: 0.5rem;">
                        <span style="color: var(--text-secondary); font-size: 0.78rem;">
                            <i class="mdi mdi-mouse"></i> Drag tables · Scroll to zoom · Drag background to pan
                        </span>
                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                            <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem;" @click="zoomOut"><i class="mdi mdi-minus"></i></button>
                            <span style="min-width: 46px; text-align: center; font-size: 0.85rem; color: var(--text-secondary);">{{ Math.round(zoom * 100) }}%</span>
                            <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem;" @click="zoomIn"><i class="mdi mdi-plus"></i></button>
                            <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem;" @click="fitAll" title="Fit All Tables"><i class="mdi mdi-fit-to-screen"></i></button>
                            <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem;" @click="resetZoom" title="Reset"><i class="mdi mdi-fit-to-page-outline"></i></button>
                            <button class="btn btn-secondary" style="padding: 0.3rem 0.6rem;" @click="toggleFullscreen" :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'">
                                <i :class="isFullscreen ? 'mdi mdi-fullscreen-exit' : 'mdi mdi-fullscreen'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Canvas Viewport: fixed size, overflow hidden, everything inside gets scaled -->
                    <div
                        ref="erdCanvas"
                        class="erd-canvas"
                        style="overflow: hidden; cursor: grab; user-select: none;"
                        @mousedown="startPan"
                        @mousemove="onPan"
                        @mouseup="stopPan"
                        @mouseleave="stopPan"
                        @wheel.prevent="onWheel"
                    >
                        <!-- World: this is what gets transformed (panned + zoomed) -->
                        <div :style="worldStyle">
                            <!-- SVG relation lines (inside world so they scale too) -->
                            <svg style="position: absolute; top: 0; left: 0; width: 6000px; height: 4000px; pointer-events: none; overflow: visible;">
                                <defs>
                                    <marker id="arrow" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto">
                                        <path d="M0,0 L0,6 L8,3 z" fill="rgba(99,102,241,0.6)" />
                                    </marker>
                                </defs>
                                <path
                                    v-for="rel in relationships"
                                    :key="rel.id"
                                    :d="drawRelationLine(rel)"
                                    class="erd-relation-line"
                                    marker-end="url(#arrow)"
                                />
                            </svg>

                            <!-- Table Nodes -->
                            <div
                                v-for="table in tables"
                                :key="table.id"
                                class="erd-table-node"
                                :style="getTableNodePosition(table)"
                                @mousedown.stop="startDrag(table, $event)"
                                @mouseup.stop="onSelectTable(table)"
                            >
                                <div class="erd-table-header">
                                    <i class="mdi mdi-table" style="margin-right: 4px; font-size: 0.85rem;"></i>
                                    {{ table.name }}
                                </div>
                                <div class="erd-table-body">
                                    <div v-for="col in (table.child || [])" :key="col.id" class="erd-column-item" :class="{ fk: col.relasi }">
                                        <span><i v-if="col.relasi" class="mdi mdi-link-variant" style="font-size: 0.7rem; opacity: 0.7;"></i> {{ col.name }}</span>
                                        <span style="opacity: 0.5; font-size: 0.75rem;">{{ col.typeData }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};
