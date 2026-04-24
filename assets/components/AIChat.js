const AIChat = {
    props: ['selectedProject', 'loading'],
    emits: ['add-table', 'import-project'],
    setup(props, { emit }) {
        const isOpen = Vue.ref(false);
        const userInput = Vue.ref('');
        const messages = Vue.ref([
            { role: 'assistant', content: 'Halo! Saya AI Assistant. Kamu bisa tanya kebutuhan tabel untuk project kamu, misalnya: "Saya mau buat sistem rental mobil, butuh tabel apa saja?"' }
        ]);
        const isTyping = Vue.ref(false);
        const chatContainer = Vue.ref(null);

        // --- IndexedDB Logic ---
        const DB_NAME = 'LaragenAI';
        const STORE_NAME = 'ChatHistory';
        
        const initDB = () => {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, 1);
                request.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME);
                    }
                };
                request.onsuccess = (e) => resolve(e.target.result);
                request.onerror = (e) => reject(e.target.error);
            });
        };

        const saveChat = async () => {
            if (!props.selectedProject) return;
            const db = await initDB();
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            store.put(JSON.parse(JSON.stringify(messages.value)), props.selectedProject.id);
        };

        const loadChat = async () => {
            if (!props.selectedProject) return;
            const db = await initDB();
            const tx = db.transaction(STORE_NAME, 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const request = store.get(props.selectedProject.id);
            request.onsuccess = () => {
                if (request.result) {
                    messages.value = request.result;
                    scrollToBottom();
                } else {
                    // Reset to default if no history
                    messages.value = [{ role: 'assistant', content: 'Halo! Saya AI Assistant. Kamu bisa tanya kebutuhan tabel untuk project kamu, misalnya: "Saya mau buat sistem rental mobil, butuh tabel apa saja?"' }];
                }
            };
        };

        const clearChat = async () => {
            if (!props.selectedProject) return;
            const db = await initDB();
            const tx = db.transaction(STORE_NAME, 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            store.delete(props.selectedProject.id);
            messages.value = [{ role: 'assistant', content: 'Halo! Saya AI Assistant. Kamu bisa tanya kebutuhan tabel untuk project kamu, misalnya: "Saya mau buat sistem rental mobil, butuh tabel apa saja?"' }];
        };

        // Watch for project changes to load specific history
        Vue.watch(() => props.selectedProject?.id, (newId) => {
            if (newId) loadChat();
        }, { immediate: true });

        // --- Chat Logic ---
        const scrollToBottom = () => {
            Vue.nextTick(() => {
                if (chatContainer.value) {
                    chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
                }
            });
        };

        const extractJSON = (text) => {
            const regex = /```json\n([\s\S]*?)\n```/;
            const match = text.match(regex);
            if (match && match[1]) {
                try { return JSON.parse(match[1]); } catch (e) { return null; }
            }
            return null;
        };

        const applySchema = (schema) => {
            if (!props.selectedProject) {
                alert("Silakan pilih project terlebih dahulu di sidebar.");
                return;
            }
            if (confirm("Apakah kamu ingin menerapkan skema tabel ini?")) {
                emit('import-project', { 
                    type: 'json', 
                    content: JSON.stringify(schema),
                    skipProjectCreate: true
                });
                // const msg = { role: 'assistant', content: 'Selesai! Tabel telah ditambahkan ke project kamu.' };
                // messages.value.push(msg);
                // saveChat();
                scrollToBottom();
            }
        };

        const sendMessage = async () => {
            if (!userInput.value.trim() || isTyping.value) return;

            const text = userInput.value;
            messages.value.push({ role: 'user', content: text });
            userInput.value = '';
            isTyping.value = true;
            scrollToBottom();
            saveChat();

            const history = messages.value.slice(-7, -1).map(m => ({
                role: m.role,
                content: m.content.replace(/```json[\s\S]*?```/g, '[Skema JSON disembunyikan untuk menghemat token]')
            }));

            try {
                const response = await fetch('./dev/index.php?chat=true', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        prompt: text,
                        framework: props.selectedProject?.backend_framework || 'Laravel',
                        history: history
                    })
                });
                
                const result = await response.json();
                
                let reply = "";
                if (result.choices && result.choices[0]) {
                    reply = result.choices[0].message.content;
                } else if (result.error) {
                    reply = "Error: " + result.error;
                } else {
                    reply = "Maaf, saya tidak mendapatkan respon yang valid.";
                }

                const schema = extractJSON(reply);
                messages.value.push({ 
                    role: 'assistant', 
                    content: reply,
                    schema: schema 
                });
                saveChat();
            } catch (err) {
                console.error("Chat Error:", err);
                messages.value.push({ role: 'assistant', content: 'Maaf, terjadi kesalahan koneksi ke server.' });
            } finally {
                isTyping.value = false;
                scrollToBottom();
            }
        };

        return {
            isOpen, userInput, messages, isTyping, chatContainer,
            sendMessage, applySchema, clearChat, toggleChat: () => { isOpen.value = !isOpen.value; if(isOpen.value) scrollToBottom(); }
        };
    },
    template: `
        <div class="ai-chat-wrapper" :class="{ open: isOpen }">
            <div class="ai-chat-trigger" @click="toggleChat">
                <i class="mdi" :class="isOpen ? 'mdi-close' : 'mdi-robot-outline'"></i>
                <span v-if="!isOpen">Tanya AI</span>
            </div>
            
            <div class="ai-chat-window" v-if="isOpen">
                <div class="ai-chat-header">
                    <i class="mdi mdi-robot-outline"></i>
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem;">Architect AI</div>
                        <div style="font-size: 0.7rem; color: var(--success);">{{ selectedProject ? selectedProject.name : 'Ready' }}</div>
                    </div>
                    <button @click="clearChat" class="btn btn-secondary" style="margin-left: auto; padding: 0.2rem 0.5rem; font-size: 0.7rem;" title="Clear History">
                        <i class="mdi mdi-delete-sweep"></i>
                    </button>
                </div>
                
                <div class="ai-chat-messages" ref="chatContainer">
                    <div v-for="(msg, i) in messages" :key="i" class="message" :class="msg.role">
                        <div class="message-bubble">
                            <div style="white-space: pre-wrap;">{{ msg.content }}</div>
                            <div v-if="msg.schema" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                                <button class="btn btn-primary" style="width: 100%; font-size: 0.8rem;" @click="applySchema(msg.schema)">
                                    <i class="mdi mdi-table-plus"></i>
                                    Terapkan Skema
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="isTyping" class="message assistant">
                        <div class="message-bubble typing">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
                
                <div class="ai-chat-input">
                    <input 
                        type="text" 
                        v-model="userInput" 
                        @keyup.enter="sendMessage"
                        placeholder="Tanya kebutuhan tabel..."
                        class="form-control"
                        :disabled="!selectedProject"
                    >
                    <button class="btn btn-primary" @click="sendMessage" :disabled="isTyping || !selectedProject">
                        <i class="mdi mdi-send"></i>
                    </button>
                </div>
            </div>
        </div>
    `
};
