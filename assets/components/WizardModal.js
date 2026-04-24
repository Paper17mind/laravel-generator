const WizardModal = {
    props: ['showWizard', 'selectedProject', 'backendComponents'],
    emits: ['close', 'confirm-generate'],
    template: `
        <div v-if="showWizard" class="modal-overlay" @click.self="$emit('close')">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--text-primary);">Generation Wizard</h2>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">Configure what you want to generate</p>
                    </div>
                    <i class="mdi mdi-close" style="font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);" @click="$emit('close')"></i>
                </div>
                
                <div class="modal-body">
                    <!-- Backend Settings -->
                    <div class="wizard-section">
                        <div class="wizard-section-title">
                            <i class="mdi mdi-server-network"></i>
                            <span>Backend Configuration</span>
                        </div>
                        <div class="input-group" style="margin-bottom: 1.5rem;">
                            <label>Framework</label>
                            <select v-model="selectedProject.backend_framework" class="form-control">
                                <option value="laravel">Laravel (PHP)</option>
                                <option value="adonis">Adonis (Node.js)</option>
                                <option value="express">Express (Node.js)</option>
                                <option value="go">Go (Fiber/Gin)</option>
                            </select>
                        </div>
                        <div class="options-grid">
                            <div 
                                v-for="comp in backendComponents" 
                                :key="comp.key"
                                class="option-checkbox"
                                :class="{ active: comp.enabled }"
                                @click="comp.enabled = !comp.enabled"
                            >
                                <i :class="comp.icon"></i>
                                <span>{{ comp.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Frontend Settings -->
                    <div class="wizard-section">
                        <div class="wizard-section-title">
                            <i class="mdi mdi-application-braces"></i>
                            <span>Frontend Configuration</span>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="input-group">
                                <label>Framework</label>
                                <select v-model="selectedProject.frontend_framework" class="form-control">
                                    <option value="vue3">Vue 3 (Composition API)</option>
                                    <option value="react">React (Hooks)</option>
                                    <option value="svelte">Svelte</option>
                                    <option value="html">HTML Vanilla</option>
                                </select>
                            </div>
                            <div class="input-group">
                                <label>UI Framework</label>
                                <select class="form-control" disabled>
                                    <option>Tailwind CSS (Standard)</option>
                                    <option>Bootstrap 5</option>
                                    <option>Vanilla CSS</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="$emit('close')">Cancel</button>
                    <button class="btn btn-primary" @click="$emit('confirm-generate')">
                        <i class="mdi mdi-rocket"></i>
                        Run Generation
                    </button>
                </div>
            </div>
        </div>
    `
};
