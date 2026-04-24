const WelcomeView = {
    emits: ['add-project', 'import-project'],
    setup(props, { emit }) {
        const handleFileImport = (event) => {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const content = e.target.result;
                const extension = file.name.split('.').pop().toLowerCase();
                
                if (extension === 'sql') {
                    parseSqlWithClassicUtils(content);
                } else {
                    emit('import-project', { type: extension, content });
                }
                event.target.value = '';
            };
            reader.readAsText(file);
        };

        const parseSqlWithClassicUtils = (sql) => {
            try {
                // Use our custom SQLParserUtils
                const result = SQLParserUtils.parse(sql);
                
                if (result.tables.length === 0) {
                    alert('No valid CREATE TABLE statements found.');
                    return;
                }

                emit('import-project', { type: 'json', content: JSON.stringify(result) });

            } catch (err) {
                console.error('SQL Utils Error:', err);
                alert('Failed to parse SQL. Please check the file format.');
            }
        };

        const triggerFileSelect = () => {
            document.getElementById('import-file-input').click();
        };

        return { handleFileImport, triggerFileSelect };
    },
    template: `
        <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
            <div style="width: 120px; height: 120px; background: rgba(99, 102, 241, 0.1); border-radius: 3rem; display: flex; justify-content: center; align-items: center; margin-bottom: 2rem;">
                <i class="mdi mdi-laravel" style="font-size: 4rem; color: var(--accent);"></i>
            </div>
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Welcome to CodeGen v2</h1>
            <p style="color: var(--text-secondary); max-width: 500px; line-height: 1.6;">
                Select or create a project to start building your application with ease.
            </p>
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button class="btn btn-primary" @click="$emit('add-project')" style="padding: 0.8rem 2rem;">
                    <i class="mdi mdi-plus"></i>
                    New Project
                </button>
                
                <input 
                    type="file" 
                    id="import-file-input" 
                    style="display: none;" 
                    accept=".sql,.json"
                    @change="handleFileImport"
                >
                
                <button class="btn btn-secondary" @click="triggerFileSelect" style="padding: 0.8rem 2rem;">
                    <i class="mdi mdi-file-import-outline"></i>
                    Open SQL/JSON File
                </button>
            </div>
            <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-secondary);">
                Supports .sql (DDL) and .json formats
            </div>
        </div>
    `
};
