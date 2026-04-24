const Sidebar = {
    props: ['projects', 'tables', 'selectedProject', 'selectedTable'],
    emits: ['add-project', 'select-project', 'add-table', 'select-table', 'generate'],
    template: `
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="mdi mdi-laravel"></i>
                    <span>Laragen v2</span>
                </div>
            </div>
            <div class="sidebar-nav">
                <!-- Projects Section -->
                <div class="nav-section">
                    <div class="nav-section-title">
                        <span>Projects</span>
                        <i class="mdi mdi-plus-circle-outline nav-action" title="New Project" @click="$emit('add-project')"></i>
                    </div>
                    <div 
                        v-for="project in projects" 
                        :key="project.id"
                        class="nav-item"
                        :class="{ active: selectedProject && selectedProject.id === project.id }"
                        @click="$emit('select-project', project)"
                    >
                        <i class="mdi mdi-folder-outline"></i>
                        <span>{{ project.name }}</span>
                    </div>
                </div>

                <!-- Tables Section -->
                <div v-if="selectedProject" class="nav-section">
                    <div class="nav-section-title">
                        <span>Tables</span>
                        <i class="mdi mdi-plus-circle-outline nav-action" title="New Table" @click="$emit('add-table')"></i>
                    </div>
                    <div 
                        v-for="table in tables" 
                        :key="table.id"
                        class="nav-item"
                        :class="{ active: selectedTable && selectedTable.id === table.id }"
                        @click="$emit('select-table', table)"
                    >
                        <i class="mdi mdi-table"></i>
                        <span>{{ table.name }}</span>
                    </div>
                    <div v-if="tables.length === 0" style="padding: 0 1rem; color: var(--text-secondary); font-size: 0.8rem; font-style: italic;">
                        No tables in this project.
                    </div>
                </div>
            </div>

            <div class="sidebar-footer" style="padding: 1.5rem; border-top: 1px solid var(--border);">
                <button 
                    class="btn btn-primary" 
                    style="width: 100%; justify-content: center;" 
                    :disabled="!selectedProject"
                    @click="$emit('generate')"
                >
                    <i class="mdi mdi-rocket"></i>
                    Generate Project
                </button>
            </div>
        </aside>
    `
};
