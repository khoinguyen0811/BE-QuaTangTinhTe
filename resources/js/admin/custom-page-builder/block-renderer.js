// block-renderer.js
import { store } from './builder-store.js';
import { historyManager } from './history-manager.js';
import { autosaveManager } from './autosave-manager.js';
import { tiptapManager } from './tiptap-manager.js';
import { mediaManager } from './media-manager.js';
import { sortableManager } from './sortable-manager.js';

export class BlockRenderer {
    constructor() {
        this.elements = {};
        this.labels = {};
        this.blockTemplates = {};
        this.arrayTemplates = {};
    }

    init(elements, labels, blockTemplates, arrayTemplates) {
        this.elements = elements;
        this.labels = labels;
        this.blockTemplates = blockTemplates;
        this.arrayTemplates = arrayTemplates;

        // Subscribe to store events
        store.subscribe('structure:changed', () => {
            this.renderBlocks();
        });

        store.subscribe('selection:changed', () => {
            this.renderBlocks();
            this.renderInspector();
        });

        store.subscribe('block-settings:changed', ({ blockId, settings }) => {
            // Update non-rich text fields without redrawing the whole inspector
            Object.entries(settings).forEach(([key, val]) => {
                if (key !== 'content') {
                    this.syncField(`settings.${key}`, val);
                }
            });
        });

        store.subscribe('save-status:changed', () => {
            this.updateStatus();
        });
    }

    renderBlocks() {
        const blocks = store.getLayout().blocks || [];
        const selectedId = store.getSelectedId();

        if (blocks.length === 0) {
            this.elements.list.innerHTML = '<div class="builder-empty">Chưa có block nào. Bấm nút Thêm để bắt đầu.</div>';
            return;
        }

        this.elements.list.innerHTML = blocks.map((block, index) => `
            <div
                class="builder-section-item ${block.id === selectedId ? 'is-selected' : ''} ${block.enabled ? '' : 'is-disabled'}"
                data-block-id="${this.escapeHtml(block.id)}"
                role="button"
                tabindex="0"
                aria-pressed="${block.id === selectedId ? 'true' : 'false'}"
            >
                <span class="builder-drag-handle" title="Kéo để sắp xếp">
                    <iconify-icon icon="solar:hamburger-menu-line-duotone"></iconify-icon>
                </span>
                <span class="builder-section-copy">
                    <strong>${index + 1}. ${this.escapeHtml(block.settings?.title || block.type.toUpperCase())}</strong>
                    <small>${this.escapeHtml(block.type)}</small>
                </span>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="builder-action-btn" data-duplicate-block="${this.escapeHtml(block.id)}" title="Nhân bản block">
                        <iconify-icon icon="solar:copy-line-duotone"></iconify-icon>
                    </button>
                    <button type="button" class="builder-action-btn text-primary" data-edit-block="${this.escapeHtml(block.id)}" title="Chỉnh sửa block">
                        <iconify-icon icon="solar:pen-new-square-line-duotone"></iconify-icon>
                    </button>
                    <button type="button" class="builder-action-btn text-danger" data-delete-block="${this.escapeHtml(block.id)}" title="Xóa block">
                        <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                    </button>
                </div>
            </div>
        `).join('');

        this.bindBlockListEvents();
    }

    bindBlockListEvents() {
        // Initialize SortableJS on sidebar blocks list
        sortableManager.init(this.elements.list);

        // Bind click & key events to select blocks
        this.elements.list.querySelectorAll('.builder-section-item').forEach((item) => {
            const id = item.dataset.blockId;
            item.addEventListener('click', (e) => {
                // Prevent selection if clicking button/handle
                if (e.target.closest('button') || e.target.closest('.builder-drag-handle')) {
                    return;
                }
                store.setSelectedId(id);
                this.sendPreview({ scrollToSelected: true });
            });
        });

        // Duplicate, Visibility, Delete buttons
        this.elements.list.querySelectorAll('[data-duplicate-block]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const newId = crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).substring(2, 9);
                store.duplicateBlock(btn.dataset.duplicateBlock, newId);
                historyManager.pushSnapshot();
                autosaveManager.schedule();
            });
        });

        this.elements.list.querySelectorAll('[data-edit-block]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                store.setSelectedId(btn.dataset.editBlock);
                this.sendPreview({ scrollToSelected: true });
            });
        });

        this.elements.list.querySelectorAll('[data-delete-block]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (confirm('Bạn có chắc muốn xóa block này?')) {
                    // Destroy editor before deleting block to avoid memory leak
                    if (store.getSelectedId() === btn.dataset.deleteBlock) {
                        tiptapManager.destroy();
                    }
                    store.deleteBlock(btn.dataset.deleteBlock);
                    historyManager.pushSnapshot();
                    autosaveManager.schedule();
                }
            });
        });
    }

    renderInspector() {
        const block = store.selectedBlock();
        if (!block) {
            this.elements.inspectorTitle.textContent = 'Thuộc tính block';
            this.elements.inspectorType.textContent = 'Chọn một block để chỉnh sửa';
            this.elements.inspector.innerHTML = '<div class="builder-empty">Chọn block ở danh sách hoặc trong preview để cấu hình chi tiết.</div>';
            tiptapManager.destroy();
            this.closeInspector();
            return;
        }

        this.openInspector();
        this.elements.inspectorTitle.textContent = block.settings?.title || block.type.toUpperCase();
        this.elements.inspectorType.textContent = block.type;

        // Construct Inspector fields
        this.elements.inspector.innerHTML = `
            <div class="builder-field">
                <label for="block-enabled">Trạng thái block</label>
                <select id="block-enabled" class="form-select">
                    <option value="1" ${block.enabled ? 'selected' : ''}>Kích hoạt hiển thị</option>
                    <option value="0" ${block.enabled ? '' : 'selected'}>Tạm ẩn block</option>
                </select>
            </div>
            ${Object.entries(block.settings || {}).map(([key, value]) => Array.isArray(value)
                ? this.renderArrayField(key, value, block)
                : this.renderScalarField(`settings.${key}`, key, value, block.type)
            ).join('')}
        `;

        this.bindInspectorEvents(block);
    }

    renderScalarField(path, key, value, blockType) {
        const safePath = this.escapeHtml(path);
        const safeValue = this.escapeHtml(value !== null && value !== undefined ? value : '');

        if (key === 'align') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${this.labels[key] || key}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="left" ${value === 'left' ? 'selected' : ''}>Căn lề trái</option>
                        <option value="center" ${value === 'center' ? 'selected' : ''}>Căn giữa</option>
                        <option value="right" ${value === 'right' ? 'selected' : ''}>Căn lề phải</option>
                        <option value="justify" ${value === 'justify' ? 'selected' : ''}>Căn đều hai bên</option>
                    </select>
                </div>
            `;
        }

        if (key === 'width') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${this.labels[key] || key}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="normal" ${value === 'normal' ? 'selected' : ''}>Vừa phải (Normal)</option>
                        <option value="wide" ${value === 'wide' ? 'selected' : ''}>Rộng (Wide)</option>
                        <option value="full" ${value === 'full' ? 'selected' : ''}>Toàn màn hình (Full width)</option>
                    </select>
                </div>
            `;
        }

        if (key === 'image_position') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${this.labels[key] || key}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="left" ${value === 'left' ? 'selected' : ''}>Ảnh bên trái</option>
                        <option value="right" ${value === 'right' ? 'selected' : ''}>Ảnh bên phải</option>
                    </select>
                </div>
            `;
        }

        if (key === 'form_type') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${this.labels[key] || key}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="general" ${value === 'general' ? 'selected' : ''}>Liên hệ chung</option>
                        <option value="consultation" ${value === 'consultation' ? 'selected' : ''}>Đăng ký tư vấn mẫu</option>
                        <option value="feedback" ${value === 'feedback' ? 'selected' : ''}>Gửi phản hồi</option>
                    </select>
                </div>
            `;
        }

        if (key === 'show_phone' || key === 'show_email' || key === 'first_open' || key === 'show_line') {
            return `
                <div class="builder-field" style="display: flex; align-items: center; gap: 0.5rem; flex-direction: row; margin: 1rem 0;">
                    <input id="field-${safePath}" type="checkbox" class="form-check-input" ${value ? 'checked' : ''} data-field-path="${safePath}">
                    <label for="field-${safePath}" class="form-check-label" style="margin: 0; cursor: pointer;">${this.labels[key] || key}</label>
                </div>
            `;
        }

        // Tiptap Rich Text editor container instead of textarea
        if (key === 'content' && (blockType === 'rich_text' || blockType === 'image_text')) {
            return `
                <div class="builder-field">
                    <label>${this.labels[key] || key}</label>
                    <div class="tiptap-editor-wrapper">
                        <div class="tiptap-toolbar">
                            <div class="tiptap-toolbar-group">
                                <button type="button" class="tiptap-btn" data-tiptap-command="bold" title="Bôi đậm"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 4h8a4 4 0 0 1 4 4a4 4 0 0 1-4 4H6zm0 8h9a4 4 0 0 1 4 4a4 4 0 0 1-4 4H6z"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="italic" title="In nghiêng"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 4h-9m4 0l-4 16m6 0h-9"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="underline" title="Gạch chân"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 3v7a6 6 0 0 0 6 6a6 6 0 0 0 6-6V3M4 21h16"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="strike" title="Gạch ngang"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M16 6.5A3.5 3.5 0 0 0 12.5 3h-2A3.5 3.5 0 0 0 7 6.5a3.5 3.5 0 0 0 3.5 3.5h2M12 14h1.5a3.5 3.5 0 0 1 3.5 3.5a3.5 3.5 0 0 1-3.5 3.5h-2a3.5 3.5 0 0 1-3.5-3.5"/></svg></button>
                            </div>
                            <div class="tiptap-toolbar-group">
                                <button type="button" class="tiptap-btn" data-tiptap-command="heading" data-tiptap-level="2" title="Tiêu đề H2">H2</button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="heading" data-tiptap-level="3" title="Tiêu đề H3">H3</button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="paragraph" title="Đoạn văn"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 4h6m-6 4h6m-12 8h12m-12-4h12M5 4h4v8H5z"/></svg></button>
                            </div>
                            <div class="tiptap-toolbar-group">
                                <button type="button" class="tiptap-btn" data-tiptap-command="align" data-tiptap-value="left" title="Căn lề trái"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 6H3m12 6H3m18 6H3"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="align" data-tiptap-value="center" title="Căn giữa"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 6H5m14 6H5m14 6H5"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="align" data-tiptap-value="right" title="Căn lề phải"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 6H3m21 6H9m12 6H3"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="align" data-tiptap-value="justify" title="Căn đều"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h18M3 18h18"/></svg></button>
                            </div>
                            <div class="tiptap-toolbar-group">
                                <button type="button" class="tiptap-btn" data-tiptap-command="bulletList" title="Danh sách chấm tròn"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6h12M9 12h12M9 18h12M5 6v.01M5 12v.01M5 18v.01"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="orderedList" title="Danh sách số"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6h11m-11 6h11m-11 6h11M4 6h2v4M4 10h3M4 18h3m-3-4h3M4 14v2h3v2"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="blockquote" title="Trích dẫn"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M10 7L8 11H10V17H4V11L6 7H10M18 7L16 11H18V17H12V11L14 7H18Z"/></svg></button>
                            </div>
                            <div class="tiptap-toolbar-group">
                                <button type="button" class="tiptap-btn" data-tiptap-command="link" title="Chèn liên kết"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71m-2.2 4.41L7.83 11.3a5 5 0 0 0-7.07 7.07l3 3a5 5 0 0 0 7.54-.54"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="image" title="Chèn hình ảnh"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 8h.01M3 6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3Zm0 12l5-5l6 6m-3-7l4-4l3 3"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="table" title="Chèn bảng"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zm0 7h18M3 16h18M3 8h18M8 3v18M16 3v18"/></svg></button>
                            </div>
                            <div class="tiptap-toolbar-group">
                                <button type="button" class="tiptap-btn" data-tiptap-command="undo" title="Hoàn tác"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v6h6M21 17a9 9 0 0 0-9-9a9 9 0 0 0-6 2.3L3 13"/></svg></button>
                                <button type="button" class="tiptap-btn" data-tiptap-command="redo" title="Làm lại"><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 7v6h-6M3 17a9 9 0 0 1 9-9a9 9 0 0 1 6 2.3l3 2.7"/></svg></button>
                            </div>
                        </div>
                        <div class="tiptap-content" id="tiptap-editor-node"></div>
                        <div class="tiptap-char-count" id="tiptap-char-counter">0 / 50.000 ký tự</div>
                    </div>
                </div>
            `;
        }

        const isLong = ['content', 'description', 'address', 'map_embed_url'].includes(key) || String(value || '').length > 120;
        const input = isLong
            ? `<textarea id="field-${safePath}" class="form-control" data-field-path="${safePath}">${safeValue}</textarea>`
            : `<input id="field-${safePath}" type="text" class="form-control" value="${safeValue}" data-field-path="${safePath}">`;

        const isImage = key.includes('image_url') || key === 'image';
        return `
            <div class="builder-field">
                <label for="field-${safePath}">${this.labels[key] || key}</label>
                ${isImage ? `
                    <div class="builder-upload-row">
                        ${input}
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-media-path="${safePath}" title="Chọn từ thư viện ảnh">
                            <iconify-icon icon="solar:gallery-wide-line-duotone"></iconify-icon>
                        </button>
                    </div>
                ` : input}
            </div>
        `;
    }

    renderArrayField(key, items, block) {
        return `
            <div class="builder-array-container border rounded p-3 mb-3 bg-light">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong class="text-dark small">${this.labels[key] || key}</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-item="${this.escapeHtml(key)}" title="Thêm mục mới">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="align-middle me-1"></iconify-icon> Thêm
                    </button>
                </div>
                <div class="builder-array-list">
                    ${items.map((item, index) => `
                        <div class="builder-array-item border rounded p-2 mb-2 bg-white" data-array-index="${index}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-secondary">Mục #${index + 1}</span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-item="${this.escapeHtml(key)}" data-item-index="${index}" title="Xóa mục này">
                                    <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                                </button>
                            </div>
                            ${Object.entries(item).map(([subKey, subVal]) =>
                                this.renderScalarField(`settings.${key}.${index}.${subKey}`, subKey, subVal, block.type)
                            ).join('')}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    bindInspectorEvents(block) {
        const enabledSelect = this.elements.inspector.querySelector('#block-enabled');
        if (enabledSelect) {
            enabledSelect.addEventListener('change', (e) => {
                store.toggleBlockEnabled(block.id);
                historyManager.pushSnapshot();
                autosaveManager.schedule();
            });
        }

        // Initialize Tiptap if the block has rich text 'content'
        const editorNode = this.elements.inspector.querySelector('#tiptap-editor-node');
        const charCounter = this.elements.inspector.querySelector('#tiptap-char-counter');
        if (editorNode) {
            const initialContent = block.settings.content || '';
            const editor = tiptapManager.mount(editorNode, initialContent, (html) => {
                store.updateBlockSettings(block.id, { content: html }, { isRichText: true });
                
                // Update character counter
                const length = html.length;
                if (charCounter) {
                    charCounter.textContent = `${length.toLocaleString()} / 50.000 ký tự`;
                    charCounter.classList.toggle('warning', length > 40000);
                    charCounter.classList.toggle('error', length > 50000);
                }
                
                // Autosave schedule is handled in store update / autosave manager
                autosaveManager.schedule();
            });

            // Update initial character count
            if (charCounter) {
                const length = initialContent.length;
                charCounter.textContent = `${length.toLocaleString()} / 50.000 ký tự`;
            }

            // Bind Toolbar events
            this.bindTiptapToolbarEvents(editor);
        }

        // Scalar fields
        this.elements.inspector.querySelectorAll('[data-field-path]').forEach((field) => {
            const path = field.dataset.fieldPath;
            // Skip setting events for content if Tiptap handles it
            if (path === 'settings.content' && (block.type === 'rich_text' || block.type === 'image_text')) {
                return;
            }

            const update = () => {
                const val = field.type === 'checkbox' ? field.checked : field.value;
                const pathParts = path.split('.');
                const settingsKey = pathParts[1];
                
                // Check if path goes deeper (like settings.items.0.title)
                if (pathParts.length > 2) {
                    const clonedSettings = JSON.parse(JSON.stringify(block.settings));
                    let current = clonedSettings;
                    for (let i = 1; i < pathParts.length - 1; i++) {
                        current = current[pathParts[i]];
                    }
                    current[pathParts[pathParts.length - 1]] = val;
                    store.updateBlockSettings(block.id, clonedSettings);
                } else {
                    store.updateBlockSettings(block.id, { [settingsKey]: val });
                }
                
                autosaveManager.schedule();
            };
            field.addEventListener('input', update);
            field.addEventListener('change', update);
        });

        // Add item to array
        this.elements.inspector.querySelectorAll('[data-add-item]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.addItem;
                const items = block.settings[key];
                if (!Array.isArray(items) || items.length >= 15) return;
                items.push(this.arrayTemplates.items(block.type));
                this.renderInspector();
                historyManager.pushSnapshot();
                autosaveManager.schedule();
            });
        });

        // Remove item from array
        this.elements.inspector.querySelectorAll('[data-remove-item]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.removeItem;
                const index = parseInt(btn.dataset.itemIndex, 10);
                const items = block.settings[key];
                if (!Array.isArray(items)) return;
                items.splice(index, 1);
                this.renderInspector();
                historyManager.pushSnapshot();
                autosaveManager.schedule();
            });
        });

        // Image selector launch button for scalar fields
        this.elements.inspector.querySelectorAll('[data-media-path]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const path = btn.dataset.mediaPath;
                mediaManager.launch((media) => {
                    const pathParts = path.split('.');
                    const settingsKey = pathParts[1];
                    
                    if (pathParts.length > 2) {
                        const clonedSettings = JSON.parse(JSON.stringify(block.settings));
                        let current = clonedSettings;
                        for (let i = 1; i < pathParts.length - 1; i++) {
                            current = current[pathParts[i]];
                        }
                        current[pathParts[pathParts.length - 1]] = media.url;
                        store.updateBlockSettings(block.id, clonedSettings);
                    } else {
                        store.updateBlockSettings(block.id, { [settingsKey]: media.url });
                    }
                    this.syncField(path, media.url);
                    historyManager.pushSnapshot();
                    autosaveManager.schedule();
                });
            });
        });
    }

    bindTiptapToolbarEvents(editor) {
        const toolbar = this.elements.inspector.querySelector('.tiptap-toolbar');
        if (!toolbar || !editor) return;

        // Bold, Italic, Underline, Strike
        const commandButtons = toolbar.querySelectorAll('[data-tiptap-command]');
        commandButtons.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const command = btn.dataset.tiptapCommand;
                const level = parseInt(btn.dataset.tiptapLevel, 10);
                const value = btn.dataset.tiptapValue;

                if (command === 'bold') {
                    editor.chain().focus().toggleBold().run();
                } else if (command === 'italic') {
                    editor.chain().focus().toggleItalic().run();
                } else if (command === 'underline') {
                    editor.chain().focus().toggleUnderline().run();
                } else if (command === 'strike') {
                    editor.chain().focus().toggleStrike().run();
                } else if (command === 'heading') {
                    editor.chain().focus().toggleHeading({ level }).run();
                } else if (command === 'paragraph') {
                    editor.chain().focus().setParagraph().run();
                } else if (command === 'align') {
                    editor.chain().focus().setTextAlign(value).run();
                } else if (command === 'bulletList') {
                    editor.chain().focus().toggleBulletList().run();
                } else if (command === 'orderedList') {
                    editor.chain().focus().toggleOrderedList().run();
                } else if (command === 'blockquote') {
                    editor.chain().focus().toggleBlockquote().run();
                } else if (command === 'link') {
                    const currentUrl = editor.getAttributes('link').href || '';
                    const url = prompt('Nhập đường dẫn liên kết:', currentUrl);
                    if (url === null) return;
                    if (url.trim() === '') {
                        editor.chain().focus().unsetLink().run();
                    } else {
                        editor.chain().focus().setLink({ href: url.trim() }).run();
                    }
                } else if (command === 'image') {
                    mediaManager.launch((media) => {
                        editor.chain().focus().setImage({
                            src: media.url,
                            alt: media.alt || '',
                            mediaId: media.id,
                        }).run();
                    });
                } else if (command === 'table') {
                    editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
                } else if (command === 'undo') {
                    editor.chain().focus().undo().run();
                } else if (command === 'redo') {
                    editor.chain().focus().redo().run();
                }
            });
        });

        // Dynamic update of active state on toolbar buttons
        const updateActiveState = () => {
            commandButtons.forEach((btn) => {
                const command = btn.dataset.tiptapCommand;
                const level = parseInt(btn.dataset.tiptapLevel, 10);
                const value = btn.dataset.tiptapValue;
                
                let active = false;
                if (command === 'bold') active = editor.isActive('bold');
                else if (command === 'italic') active = editor.isActive('italic');
                else if (command === 'underline') active = editor.isActive('underline');
                else if (command === 'strike') active = editor.isActive('strike');
                else if (command === 'heading') active = editor.isActive('heading', { level });
                else if (command === 'paragraph') active = editor.isActive('paragraph');
                else if (command === 'align') active = editor.isActive({ textAlign: value });
                else if (command === 'bulletList') active = editor.isActive('bulletList');
                else if (command === 'orderedList') active = editor.isActive('orderedList');
                else if (command === 'blockquote') active = editor.isActive('blockquote');
                else if (command === 'link') active = editor.isActive('link');

                btn.classList.toggle('is-active', active);
            });
        };

        editor.on('selectionUpdate', updateActiveState);
        editor.on('transaction', updateActiveState);
        updateActiveState(); // Run once
    }

    syncField(path, value) {
        const field = [...this.elements.inspector.querySelectorAll('[data-field-path]')]
            .find((item) => item.dataset.fieldPath === path);
        if (field && document.activeElement !== field) {
            if (field.type === 'checkbox') {
                field.checked = !!value;
            } else {
                field.value = value;
            }
        }
    }

    updateStatus() {
        const payload = store.getPayload();
        this.elements.status.classList.remove('is-dirty', 'is-published');
        if (!payload) {
            this.elements.status.textContent = 'Đang tải dữ liệu...';
            return;
        }

        if (store.isDirty()) {
            this.elements.status.textContent = 'Có thay đổi chưa lưu';
            this.elements.status.classList.add('is-dirty');
        } else if (payload.published_at === null) {
            this.elements.status.textContent = 'Bản nháp chưa xuất bản';
            this.elements.status.classList.add('is-dirty');
        } else {
            this.elements.status.textContent = `Đã xuất bản (Phiên bản #${store.getLockVersion()})`;
            this.elements.status.classList.add('is-published');
        }
        this.elements.save.disabled = !store.isDirty();
        this.elements.publish.disabled = store.isDirty() ? false : (payload.published_at === null);
    }

    sendPreview({ scrollToSelected = false } = {}) {
        const layout = store.getLayout();
        const selectedId = store.getSelectedId();
        if (!layout || !this.elements.frame.contentWindow) return;
        
        this.elements.frame.contentWindow.postMessage({
            type: 'sly_custom_page_builder_preview',
            layout: layout,
            selectedId: selectedId,
            scrollToSelected,
        }, '*');
    }

    openInspector() {
        const panel = document.getElementById('builder-inspector-panel');
        if (!panel) return;

        // Ensure overlay exists
        let overlay = document.getElementById('builder-inspector-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'builder-inspector-overlay';
            overlay.className = 'builder-inspector-overlay';
            panel.parentNode.appendChild(overlay);

            // Close on clicking overlay by resetting selection
            overlay.addEventListener('click', () => {
                store.setSelectedId(null);
            });
        }

        panel.classList.add('is-active');
        overlay.classList.add('is-active');
    }

    closeInspector() {
        const panel = document.getElementById('builder-inspector-panel');
        const overlay = document.getElementById('builder-inspector-overlay');
        if (panel) panel.classList.remove('is-active');
        if (overlay) overlay.classList.remove('is-active');
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

export const blockRenderer = new BlockRenderer();
