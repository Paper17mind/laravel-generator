const TableView = {
    props: ['selectedTable', 'columns', 'previews', 'activePreviewIndex', 'loading'],
    emits: ['delete-table', 'save-table', 'add-column', 'update-column', 'delete-column', 'set-relation', 'fetch-preview', 'copy-code', 'select-preview'],
    template: `
        <div v-if="selectedTable">
            <div class="top-bar">
                <div class="page-title">
                    <h1>{{ selectedTable.name }}</h1>
                    <p>Manage columns and relationships for this table</p>
                </div>
                <div class="actions">
                    <button class="btn btn-danger" @click="$emit('delete-table', selectedTable.id)">
                        <i class="mdi mdi-delete"></i>
                        Delete Table
                    </button>
                </div>
            </div>

            <!-- Table Metadata Card -->
            <div class="glass-card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h2 style="font-size: 1.25rem;">Table Settings</h2>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="input-group">
                        <label>Table Name</label>
                        <input type="text" v-model="selectedTable.name" class="form-control" placeholder="e.g. users">
                    </div>
                    <div class="input-group">
                        <label>Display Name</label>
                        <input type="text" v-model="selectedTable.comments" class="form-control" placeholder="e.g. User Management">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button class="btn btn-secondary" @click="$emit('save-table')">Save Changes</button>
                </div>
            </div>

            <!-- Columns Card -->
            <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem;">
                <div class="glass-card">
                    <div class="card-header">
                        <h2 style="font-size: 1.25rem;">Columns</h2>
                        <button class="btn btn-primary" @click="$emit('add-column')">
                            <i class="mdi mdi-plus"></i>
                            Add Column
                        </button>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Relation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="col in columns" :key="col.id">
                                    <td>
                                        <input type="text" v-model="col.name" class="form-control" style="padding: 0.4rem 0.75rem;">
                                    </td>
                                    <td>
                                        <select v-model="col.typeData" class="form-control" style="padding: 0.4rem 0.75rem;">
                                            <option value="string">String</option>
                                            <option value="integer">Integer</option>
                                            <option value="bigInteger">Big Integer</option>
                                            <option value="text">Text</option>
                                            <option value="date">Date</option>
                                            <option value="boolean">Boolean</option>
                                            <option value="enum">Enum</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" v-model="col.size" class="form-control" style="padding: 0.4rem 0.75rem; width: 80px;">
                                    </td>
                                    <td>
                                        <div v-if="col.relasi" class="badge badge-info text-ellipsis" style="max-width: 100px;">
                                            {{ col.relasi }}
                                        </div>
                                        <button v-else class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" @click="$emit('set-relation', col)">
                                            Set Relation
                                        </button>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-secondary" style="padding: 0.4rem;" @click="$emit('update-column', col)">
                                                <i class="mdi mdi-check"></i>
                                            </button>
                                            <button class="btn btn-danger" style="padding: 0.4rem;" @click="$emit('delete-column', col.id)">
                                                <i class="mdi mdi-trash-can-outline"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="columns.length === 0">
                                    <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 3rem;">
                                        No columns found. Add one to get started.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Code Preview Section -->
                <div class="glass-card" style="padding: 1.5rem; display: flex; flex-direction: column;">
                    <div class="card-header" style="margin-bottom: 1rem;">
                        <h2 style="font-size: 1.1rem;">Code Preview</h2>
                        <button class="btn btn-secondary" style="padding: 0.4rem;" @click="$emit('fetch-preview')" title="Refresh Preview">
                            <i class="mdi mdi-refresh"></i>
                        </button>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 0.5rem;">
                        <button 
                            v-for="(p, index) in previews" 
                            :key="index"
                            class="btn" 
                            :class="activePreviewIndex === index ? 'btn-primary' : 'btn-secondary'"
                            style="padding: 0.4rem 0.8rem; font-size: 0.8rem; white-space: nowrap;"
                            @click="$emit('select-preview', index)"
                        >
                            {{ p.name }}
                        </button>
                    </div>

                    <div v-if="previews.length > 0" style="flex: 1; position: relative; background: #000; border-radius: 0.75rem; overflow: hidden;">
                        <pre style="margin: 0; padding: 1rem; color: #a5d6ff; font-size: 0.8rem; height: 500px; overflow: auto; font-family: 'Fira Code', monospace;">{{ previews[activePreviewIndex].content }}</pre>
                        <button 
                            class="btn btn-secondary" 
                            style="position: absolute; top: 0.5rem; right: 0.5rem; padding: 0.3rem 0.6rem; font-size: 0.7rem;"
                            @click="$emit('copy-code', previews[activePreviewIndex].content)"
                        >
                            <i class="mdi mdi-content-copy"></i>
                        </button>
                    </div>
                    <div v-else style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-style: italic; font-size: 0.9rem;">
                        Click refresh to load preview
                    </div>
                </div>
            </div>
        </div>
    `
};
