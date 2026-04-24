const { createApp, ref, onMounted, computed } = Vue;

const app = createApp({
    setup() {
        const projects = ref([]);
        const selectedProject = ref(null);
        const tables = ref([]);
        const selectedTable = ref(null);
        const columns = ref([]);
        const loading = ref(false);
        const apiBase = './dev/index.php';

        const projectViewMode = ref('grid'); // or 'visual'
        const tablePositions = ref({}); // { tableId: { x, y } }
        const dragging = ref(null);
        const hasDragged = ref(false);
        const pan = ref({ x: 0, y: 0 });
        const isPanning = ref(false);
        const startPanPos = ref({ x: 0, y: 0 });

        const previews = ref([]);
        const activePreviewIndex = ref(0);

        const showWizard = ref(false);
        const backendComponents = ref([
            { key: 'controller', label: 'Controllers', icon: 'mdi mdi-api', enabled: true },
            { key: 'model', label: 'Models', icon: 'mdi mdi-database-outline', enabled: true },
            { key: 'migration', label: 'Migrations', icon: 'mdi mdi-database-export-outline', enabled: true },
            { key: 'routes', label: 'Routes', icon: 'mdi mdi-router-wireless', enabled: true },
            { key: 'middleware', label: 'Middleware', icon: 'mdi mdi-shield-check-outline', enabled: false },
            { key: 'unitTest', label: 'Unit Tests', icon: 'mdi mdi-test-tube', enabled: false },
        ]);

        const relationships = computed(() => {
            const rels = [];
            tables.value.forEach(table => {
                if (table.child && Array.isArray(table.child)) {
                    table.child.forEach(col => {
                        if (col.relasi) {
                            const targetTableName = col.relasi.split(':')[0];
                            const targetTable = tables.value.find(t => t.name === targetTableName);
                            if (targetTable) {
                                rels.push({
                                    id: `${table.id}-${targetTable.id}`,
                                    sourceId: table.id,
                                    targetId: targetTable.id
                                });
                            }
                        }
                    });
                }
            });
            return rels;
        });

        const getTableNodePosition = (table) => {
            if (!tablePositions.value[table.id]) {
                const index = tables.value.findIndex(t => t.id === table.id);
                tablePositions.value[table.id] = {
                    x: 50 + (index % 4) * 250,
                    y: 50 + Math.floor(index / 4) * 150
                };
            }
            const pos = tablePositions.value[table.id];
            return {
                left: `${pos.x + pan.value.x}px`,
                top: `${pos.y + pan.value.y}px`
            };
        };

        const startDrag = (table, event) => {
            hasDragged.value = false;
            dragging.value = {
                id: table.id,
                startX: event.clientX - tablePositions.value[table.id].x,
                startY: event.clientY - tablePositions.value[table.id].y
            };
            window.addEventListener('mousemove', onDrag);
            window.addEventListener('mouseup', stopDrag);
        };

        const onDrag = (event) => {
            if (!dragging.value) return;
            hasDragged.value = true;
            tablePositions.value[dragging.value.id] = {
                x: event.clientX - dragging.value.startX,
                y: event.clientY - dragging.value.startY
            };
        };

        const stopDrag = () => {
            setTimeout(() => {
                hasDragged.value = false;
            }, 100);
            dragging.value = null;
            window.removeEventListener('mousemove', onDrag);
            window.removeEventListener('mouseup', stopDrag);
        };

        const startPan = (event) => {
            if (event.target.classList.contains('erd-canvas')) {
                isPanning.value = true;
                startPanPos.value = {
                    x: event.clientX - pan.value.x,
                    y: event.clientY - pan.value.y
                };
            }
        };

        const onPan = (event) => {
            if (isPanning.value) {
                pan.value = {
                    x: event.clientX - startPanPos.value.x,
                    y: event.clientY - startPanPos.value.y
                };
            }
        };

        const stopPan = () => {
            isPanning.value = false;
        };

        const drawRelationLine = (rel) => {
            const source = tablePositions.value[rel.sourceId];
            const target = tablePositions.value[rel.targetId];
            if (!source || !target) return '';
            const sx = source.x + 100 + pan.value.x;
            const sy = source.y + 50 + pan.value.y;
            const tx = target.x + 100 + pan.value.x;
            const ty = target.y + 50 + pan.value.y;
            return `M ${sx} ${sy} C ${sx} ${sy + 50}, ${tx} ${ty - 50}, ${tx} ${ty}`;
        };

        const fetchProjects = async () => {
            loading.value = true;
            try {
                const response = await fetch(`${apiBase}?query=SELECT * FROM projects`);
                const result = await response.json();
                projects.value = result.data || [];
            } catch (error) {
                console.error('Error fetching projects:', error);
            } finally {
                loading.value = false;
            }
        };

        const fetchTables = async () => {
            if (!selectedProject.value) return;
            loading.value = true;
            try {
                const response = await fetch(`${apiBase}?view=join&parent=tables&child=kolom&key=table_id&where=project_id&param=${selectedProject.value.id}`);
                const result = await response.json();
                tables.value = result || [];
                if (selectedTable.value) {
                    const updated = tables.value.find(t => t.id === selectedTable.value.id);
                    if (updated) {
                        selectedTable.value = { ...updated };
                        columns.value = updated.child || [];
                    }
                }
            } catch (error) {
                console.error('Error fetching tables:', error);
            } finally {
                loading.value = false;
            }
        };

        const selectProject = (project) => {
            selectedProject.value = { ...project };
            selectedTable.value = null;
            columns.value = [];
            fetchTables();
        };

        const selectTable = (table) => {
            if (hasDragged.value) return;
            selectedTable.value = { ...table };
            columns.value = table.child || [];
            previews.value = [];
            activePreviewIndex.value = 0;
            fetchPreview();
        };

        const fetchPreview = async () => {
            if (!selectedTable.value || !selectedProject.value) return;
            loading.value = true;
            try {
                const response = await fetch(`${apiBase}?preview=true&project_id=${selectedProject.value.id}`, {
                    method: 'POST',
                    body: JSON.stringify({
                        name: selectedTable.value.name,
                        child: columns.value
                    })
                });
                const result = await response.json();
                previews.value = result.data || [];
            } catch (error) {
                console.error('Error fetching preview:', error);
            } finally {
                loading.value = false;
            }
        };

        const copyCode = (code) => {
            navigator.clipboard.writeText(code).then(() => {
                alert('Code copied to clipboard!');
            });
        };

        const addNewProject = async () => {
            const name = prompt('Enter project name:');
            if (!name) return;
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=INSERT INTO projects (name) VALUES ('${name}')`);
                await fetchProjects();
            } catch (error) {
                console.error('Error adding project:', error);
            } finally {
                loading.value = false;
            }
        };

        const saveProject = async () => {
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=UPDATE projects SET name='${selectedProject.value.name}', backend_framework='${selectedProject.value.backend_framework}', frontend_framework='${selectedProject.value.frontend_framework}' WHERE id=${selectedProject.value.id}`);
                await fetchProjects();
                alert('Project updated!');
            } catch (error) {
                console.error('Error saving project:', error);
            } finally {
                loading.value = false;
            }
        };

        const deleteProject = async (id) => {
            if (!confirm('Delete project? All tables and columns will be lost.')) return;
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=DELETE FROM projects WHERE id=${id}`);
                selectedProject.value = null;
                await fetchProjects();
            } catch (error) {
                console.error('Error deleting project:', error);
            } finally {
                loading.value = false;
            }
        };

        const addNewTable = async () => {
            const name = prompt('Enter table name:');
            if (!name) return;
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=INSERT INTO tables (name, project_id) VALUES ('${name}', ${selectedProject.value.id})`);
                await fetchTables();
            } catch (error) {
                console.error('Error adding table:', error);
            } finally {
                loading.value = false;
            }
        };

        const saveTable = async () => {
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=UPDATE tables SET name='${selectedTable.value.name}', comments='${selectedTable.value.comments || ''}' WHERE id=${selectedTable.value.id}`);
                await fetchTables();
                alert('Table saved successfully!');
            } catch (error) {
                console.error('Error saving table:', error);
            } finally {
                loading.value = false;
            }
        };

        const deleteTable = async (id) => {
            if (!confirm('Delete this table?')) return;
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=DELETE FROM tables WHERE id=${id}`);
                await fetch(`${apiBase}?exec=DELETE FROM kolom WHERE table_id=${id}`);
                selectedTable.value = null;
                await fetchTables();
            } catch (error) {
                console.error('Error deleting table:', error);
            } finally {
                loading.value = false;
            }
        };

        const addColumn = async () => {
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=INSERT INTO kolom (table_id, name, typeData) VALUES (${selectedTable.value.id}, 'new_column', 'string')`);
                await fetchTables();
            } catch (error) {
                console.error('Error adding column:', error);
            } finally {
                loading.value = false;
            }
        };

        const updateColumn = async (col) => {
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=UPDATE kolom SET name='${col.name}', typeData='${col.typeData}', size='${col.size || ''}' WHERE id=${col.id}`);
                await fetchTables();
                alert('Column updated!');
            } catch (error) {
                console.error('Error updating column:', error);
            } finally {
                loading.value = false;
            }
        };

        const deleteColumn = async (id) => {
            if (!confirm('Delete this column?')) return;
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=DELETE FROM kolom WHERE id=${id}`);
                await fetchTables();
            } catch (error) {
                console.error('Error deleting column:', error);
            } finally {
                loading.value = false;
            }
        };

        const setRelation = async (col) => {
            const relation = prompt('Enter relation (e.g. users:id):', col.relasi || '');
            if (relation === null) return;
            loading.value = true;
            try {
                await fetch(`${apiBase}?exec=UPDATE kolom SET relasi='${relation}' WHERE id=${col.id}`);
                await fetchTables();
            } catch (error) {
                console.error('Error setting relation:', error);
            } finally {
                loading.value = false;
            }
        };

        const generateCode = () => {
            if (!selectedProject.value) return;
            showWizard.value = true;
        };

        const confirmGenerate = async () => {
            showWizard.value = false;
            loading.value = true;
            try {
                const components = {};
                backendComponents.value.forEach(c => {
                    components[c.key] = c.enabled;
                });
                const payload = {
                    backend_framework: selectedProject.value.backend_framework,
                    frontend_framework: selectedProject.value.frontend_framework,
                    components: components
                };
                const res = await fetch(`${apiBase}?generate=${selectedProject.value.id}&type=project`, {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const result = await res.text();
                alert('Generation complete! Check your files folder.\n\n' + result);
            } catch (error) {
                console.error('Error generating:', error);
            } finally {
                loading.value = false;
            }
        };

        const importProject = async ({ type, content, skipProjectCreate = false }) => {
            loading.value = true;
            try {
                let projectName = null;
                if (!skipProjectCreate) {
                    projectName = prompt('Assign a name for the imported project:', 'Imported Project');
                    if (!projectName) return;
                } else {
                    projectName = selectedProject.value.name;
                }
                
                const response = await fetch(`${apiBase}?import=true`, {
                    method: 'POST',
                    body: JSON.stringify({
                        name: projectName,
                        type: type,
                        content: content,
                        project_id: skipProjectCreate ? selectedProject.value.id : null
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert('Import successful!');
                    await fetchProjects();
                    await fetchTables(); // <--- Refresh the tables for the current project
                } else {
                    alert('Import failed: ' + result.message);
                }
            } catch (error) {
                console.error('Error importing project:', error);
            } finally {
                loading.value = false;
            }
        };

        onMounted(fetchProjects);

        return {
            projects, selectedProject, tables, selectedTable, columns, loading,
            previews, activePreviewIndex, fetchPreview, copyCode,
            selectProject, selectTable, addNewProject, saveProject, deleteProject,
            addNewTable, saveTable, deleteTable, addColumn, updateColumn, deleteColumn,
            setRelation, generateCode, projectViewMode, relationships,
            startPan, onPan, stopPan, startDrag, getTableNodePosition, drawRelationLine,
            showWizard, backendComponents, confirmGenerate, importProject
        };
    }
});

// Register Components
app.component('sidebar', Sidebar);
app.component('table-view', TableView);
app.component('project-details', ProjectDetails);
app.component('welcome-view', WelcomeView);
app.component('wizard-modal', WizardModal);
app.component('ai-chat', AIChat);

app.mount('#app');
