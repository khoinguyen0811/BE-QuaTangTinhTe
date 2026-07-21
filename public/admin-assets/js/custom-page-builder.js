(function () {
    'use strict';

    const root = document.getElementById('custom-page-builder-root');
    if (!root) return;

    const elements = {
        root,
        list: document.getElementById('builder-section-list'),
        inspector: document.getElementById('builder-inspector'),
        inspectorTitle: document.getElementById('builder-inspector-title'),
        inspectorType: document.getElementById('builder-inspector-type'),
        save: document.getElementById('builder-save'),
        publish: document.getElementById('builder-publish'),
        frame: document.getElementById('builder-preview-frame'),
        alert: document.getElementById('builder-alert'),
        status: document.getElementById('builder-status'),
        toggleSections: document.getElementById('builder-toggle-sections'),
        toggleInspector: document.getElementById('builder-toggle-inspector'),
        workspace: document.querySelector('.builder-workspace'),
        viewportButtons: document.querySelectorAll('.builder-device-switch button'),
        stage: document.getElementById('builder-preview-stage'),
        openLivePage: document.getElementById('builder-open-live-page'),
        
        // Media Modal Elements
        mediaModalEl: document.getElementById('builderMediaModal'),
        mediaGrid: document.getElementById('builder-media-grid'),
        mediaSearch: document.getElementById('builder-media-search'),
        mediaStorage: document.getElementById('builder-media-storage'),
        mediaUpload: document.getElementById('builder-media-upload'),
        mediaFile: document.getElementById('builder-media-file'),
        mediaUse: document.getElementById('builder-media-use'),
        mediaSelection: document.getElementById('builder-media-selection'),
        mediaAlert: document.getElementById('builder-media-alert'),

        // Add Block Modal
        addBlockModalEl: document.getElementById('addBlockModal'),
    };

    const urls = {
        draft: root.dataset.draftUrl,
        save: root.dataset.saveUrl,
        publish: root.dataset.publishUrl,
        media: root.dataset.mediaUrl,
        upload: root.dataset.uploadUrl,
        preview: root.dataset.previewUrl,
    };

    const csrf = root.dataset.csrf;
    const pageId = root.dataset.pageId;
    let lockVersion = parseInt(root.dataset.lockVersion, 10) || 1;

    const labels = {
        title: 'Tiêu đề',
        description: 'Mô tả ngắn',
        content: 'Nội dung văn bản (HTML)',
        align: 'Căn lề chữ',
        width: 'Chiều rộng khối',
        first_open: 'Tự động mở câu đầu tiên',
        form_type: 'Loại biểu mẫu liên hệ',
        recipient_group_id: 'Nhóm nhận tin nhắn (ID)',
        address: 'Địa chỉ liên hệ',
        phone: 'Số điện thoại',
        email: 'Địa chỉ Email',
        map_embed_url: 'Đường dẫn nhúng bản đồ Google Maps',
        show_phone: 'Hiển thị số điện thoại',
        show_email: 'Hiển thị địa chỉ Email',
        columns_count: 'Số cột hiển thị',
        items: 'Danh sách đối tượng',
        question: 'Câu hỏi',
        answer: 'Câu trả lời',
        icon: 'Biểu tượng (Icon)',
        image_url: 'Hình ảnh',
        image_alt: 'Mô tả hình ảnh (SEO)',
        image_position: 'Vị trí hình ảnh',
        button_label: 'Nhãn nút bấm (CTA)',
        button_url: 'Đường dẫn liên kết (CTA)',
        bg_image_url: 'Hình nền khối',
        bg_color: 'Màu nền khối',
        height: 'Khoảng cách trống (độ cao)',
        show_line: 'Hiển thị đường kẻ ngang',
        line_color: 'Màu sắc đường kẻ',
        link_label: 'Nhãn liên kết bổ sung',
        link_url: 'Đường dẫn liên kết bổ sung',
    };

    const blockTemplates = {
        rich_text: () => ({
            title: 'Khối soạn thảo văn bản',
            content: '<p>Nhập văn bản nội dung của bạn vào đây...</p>',
            align: 'left',
            width: 'normal',
        }),
        faq: () => ({
            title: 'Câu hỏi thường gặp (FAQ)',
            description: 'Giải đáp các thắc mắc phổ biến của khách hàng.',
            items: [
                { question: 'Câu hỏi của khách hàng?', answer: '<p>Nội dung câu trả lời chi tiết.</p>' }
            ],
            first_open: false,
        }),
        contact_form: () => ({
            title: 'Liên hệ với chúng tôi',
            description: 'Gửi tin nhắn phản hồi, chúng tôi sẽ sớm liên lạc lại.',
            form_type: 'general',
            recipient_group_id: null,
            address: '123 Đường Pha Lê, Quận 1, TP. Hồ Chí Minh',
            phone: '0901234567',
            email: 'contact@mcrystal.vn',
            map_embed_url: '',
            show_phone: true,
            show_email: true,
        }),
        feature_columns: () => ({
            title: 'Tính năng & Dịch vụ',
            description: 'Những ưu điểm vượt trội chỉ có tại Mcrystal.',
            columns_count: 3,
            items: [
                { title: 'Chất lượng K9', description: 'Pha lê cao cấp trong suốt vượt trội.', icon: 'solar:gem-line-duotone', image_url: '', link_label: '', link_url: '' },
                { title: 'Khắc 3D tinh xảo', description: 'Công nghệ khắc laser bên trong khối pha lê.', icon: 'solar:layers-line-duotone', image_url: '', link_label: '', link_url: '' },
                { title: 'Giao hàng nhanh', description: 'Hỗ trợ giao hàng hỏa tốc toàn quốc.', icon: 'solar:delivery-line-duotone', image_url: '', link_label: '', link_url: '' },
            ],
        }),
        image_text: () => ({
            title: 'Khối giới thiệu dịch vụ',
            content: '<p>Nội dung giới thiệu chi tiết kèm hình ảnh minh họa sinh động ở bên cạnh.</p>',
            image_url: '',
            image_alt: 'Hình ảnh mô tả',
            image_position: 'left',
            button_label: 'Tìm hiểu thêm',
            button_url: '/collection',
        }),
        cta: () => ({
            title: 'Bắt đầu thiết kế món quà của bạn ngay hôm nay',
            description: 'Liên hệ với đội ngũ thiết kế Mcrystal để tạo mẫu 3D miễn phí.',
            button_label: 'Khám phá sản phẩm',
            button_url: '/collection',
            bg_image_url: '',
            bg_color: '#143944',
        }),
        spacer_divider: () => ({
            height: '40px',
            show_line: true,
            line_color: '#e2e8f0',
        }),
    };

    const arrayTemplates = {
        items: (blockType) => {
            if (blockType === 'faq') {
                return { question: 'Câu hỏi mới?', answer: '<p>Câu trả lời mới.</p>' };
            }
            if (blockType === 'feature_columns') {
                return { title: 'Dịch vụ mới', description: 'Mô tả dịch vụ mới.', icon: 'solar:star-line-duotone', image_url: '', link_label: '', link_url: '' };
            }
            return {};
        }
    };

    const state = {
        payload: null,
        layout: null,
        selectedId: null,
        dirty: false,
        saveTimer: null,
        savePromise: null,
        previewTimer: null,
        draggedId: null,
        media: {
            items: [],
            activeBlockId: null,
            activePath: null,
        },
        collapsedPanels: {
            sections: false,
            inspector: false,
        },
    };

    const panelStorageKey = 'custom_page_builder_panels';
    let mediaModal = null;
    let addBlockModal = null;

    // Helper functions
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    async function request(url, options = {}) {
        const isFormData = options.body instanceof FormData;
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                ...(options.body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });

        const text = await response.text();
        let json = {};
        try {
            json = text ? JSON.parse(text) : {};
        } catch {
            json = { message: text || 'Phản hồi máy chủ không hợp lệ.' };
        }

        if (!response.ok || json.success === false) {
            const error = new Error(json.message || 'Không thể xử lý yêu cầu.');
            error.status = response.status;
            error.payload = json;
            throw error;
        }

        return json.data ?? json;
    }

    function selectedBlock() {
        return state.layout?.blocks?.find((b) => b.id === state.selectedId) || null;
    }

    function getPath(target, path) {
        return path.split('.').reduce((value, key) => value?.[key], target);
    }

    function setPath(target, path, value) {
        const keys = path.split('.');
        const last = keys.pop();
        const parent = keys.reduce((current, key) => current[key], target);
        parent[last] = value;
    }

    function syncInspectorField(path, value) {
        const field = [...elements.inspector.querySelectorAll('[data-field-path]')]
            .find((item) => item.dataset.fieldPath === path);
        if (field && document.activeElement !== field) {
            if (field.type === 'checkbox') {
                field.checked = !!value;
            } else {
                field.value = value;
            }
        }
    }

    function showAlert(message, type = 'danger') {
        elements.alert.className = `alert alert-${type}`;
        elements.alert.textContent = message;
        elements.alert.classList.remove('d-none');
    }

    function hideAlert() {
        elements.alert.classList.add('d-none');
    }

    function showMediaAlert(message, type = 'danger') {
        if (!elements.mediaAlert) return;
        elements.mediaAlert.className = `alert alert-${type}`;
        elements.mediaAlert.textContent = message;
        elements.mediaAlert.classList.remove('d-none');
    }

    function updateStatus() {
        elements.status.classList.remove('is-dirty', 'is-published');
        if (!state.payload) {
            elements.status.textContent = 'Đang tải dữ liệu...';
            return;
        }

        if (state.dirty) {
            elements.status.textContent = 'Có thay đổi chưa lưu';
            elements.status.classList.add('is-dirty');
        } else if (state.payload.published_at === null) {
            elements.status.textContent = 'Bản nháp chưa xuất bản';
            elements.status.classList.add('is-dirty');
        } else {
            elements.status.textContent = `Đã xuất bản (Phiên bản #${lockVersion})`;
            elements.status.classList.add('is-published');
        }
        elements.save.disabled = !state.dirty;
        elements.publish.disabled = state.dirty ? false : (state.payload.published_at === null);
    }

    function markDirty({ syncPreview = true } = {}) {
        state.dirty = true;
        window.adminFormIsDirty = true;
        updateStatus();
        if (syncPreview) sendPreview({ debounce: true });
        window.clearTimeout(state.saveTimer);
        state.saveTimer = window.setTimeout(() => saveDraft(true), 1800);
    }

    function sendPreview({ scrollToSelected = false, debounce = false } = {}) {
        if (!state.layout || !elements.frame.contentWindow) return;
        const dispatch = () => {
            state.previewTimer = null;
            elements.frame.contentWindow.postMessage({
                type: 'sly_custom_page_builder_preview',
                layout: state.layout,
                selectedId: state.selectedId,
                scrollToSelected,
            }, '*');
        };
        window.clearTimeout(state.previewTimer);
        if (debounce) {
            state.previewTimer = window.setTimeout(dispatch, 90);
            return;
        }
        dispatch();
    }

    function renderBlocks() {
        if (!state.layout?.blocks?.length) {
            elements.list.innerHTML = '<div class="builder-empty">Chưa có block nào. Bấm nút Thêm để bắt đầu.</div>';
            return;
        }

        elements.list.innerHTML = state.layout.blocks.map((block, index) => `
            <div
                class="builder-section-item ${block.id === state.selectedId ? 'is-selected' : ''} ${block.enabled ? '' : 'is-disabled'}"
                data-block-id="${escapeHtml(block.id)}"
                draggable="true"
                role="button"
                tabindex="0"
                aria-pressed="${block.id === state.selectedId ? 'true' : 'false'}"
            >
                <span class="builder-drag-handle" title="Kéo để sắp xếp">
                    <iconify-icon icon="solar:hamburger-menu-line-duotone"></iconify-icon>
                </span>
                <span class="builder-section-copy">
                    <strong>${index + 1}. ${escapeHtml(block.settings?.title || block.type.toUpperCase())}</strong>
                    <small>${escapeHtml(block.type)}</small>
                </span>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="builder-action-btn" data-duplicate-block="${escapeHtml(block.id)}" title="Nhân bản block">
                        <iconify-icon icon="solar:copy-line-duotone"></iconify-icon>
                    </button>
                    <button type="button" class="builder-visibility" data-toggle-block="${escapeHtml(block.id)}" title="${block.enabled ? 'Ẩn block' : 'Hiện block'}">
                        <iconify-icon icon="${block.enabled ? 'solar:eye-line-duotone' : 'solar:eye-closed-line-duotone'}"></iconify-icon>
                    </button>
                    <button type="button" class="builder-action-btn text-danger" data-delete-block="${escapeHtml(block.id)}" title="Xóa block">
                        <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                    </button>
                </div>
            </div>
        `).join('');

        bindBlockListEvents();
    }

    function bindBlockListEvents() {
        elements.list.querySelectorAll('.builder-section-item').forEach((item) => {
            const select = () => {
                state.selectedId = item.dataset.blockId;
                renderBlocks();
                renderInspector();
                sendPreview({ scrollToSelected: true });
            };
            item.addEventListener('click', select);
            item.addEventListener('dragstart', (event) => {
                state.draggedId = item.dataset.blockId;
                item.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', state.draggedId);
            });
            item.addEventListener('dragover', (event) => {
                event.preventDefault();
                const dragging = elements.list.querySelector('.is-dragging');
                if (!dragging || dragging === item) return;
                const rect = item.getBoundingClientRect();
                elements.list.insertBefore(dragging, event.clientY < rect.top + rect.height / 2 ? item : item.nextSibling);
            });
            item.addEventListener('dragend', () => {
                item.classList.remove('is-dragging');
                const ids = [...elements.list.querySelectorAll('.builder-section-item')].map((row) => row.dataset.blockId);
                state.layout.blocks.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
                state.draggedId = null;
                renderBlocks();
                markDirty();
            });
        });

        // Duplicate, Visibility, Delete buttons
        elements.list.querySelectorAll('[data-duplicate-block]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                duplicateBlock(btn.dataset.duplicateBlock);
            });
        });

        elements.list.querySelectorAll('[data-toggle-block]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const block = state.layout.blocks.find(b => b.id === btn.dataset.toggleBlock);
                if (block) {
                    block.enabled = !block.enabled;
                    renderBlocks();
                    markDirty();
                }
            });
        });

        elements.list.querySelectorAll('[data-delete-block]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (confirm('Bạn có chắc muốn xóa block này?')) {
                    deleteBlock(btn.dataset.deleteBlock);
                }
            });
        });
    }

    function duplicateBlock(id) {
        const index = state.layout.blocks.findIndex(b => b.id === id);
        if (index === -1) return;

        const original = state.layout.blocks[index];
        const newBlock = clone(original);
        newBlock.id = crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).substring(2, 9);
        newBlock.settings.title = original.settings.title + ' (Bản sao)';

        state.layout.blocks.splice(index + 1, 0, newBlock);
        state.selectedId = newBlock.id;
        renderBlocks();
        renderInspector();
        markDirty();
    }

    function deleteBlock(id) {
        state.layout.blocks = state.layout.blocks.filter(b => b.id !== id);
        if (state.selectedId === id) {
            state.selectedId = null;
        }
        renderBlocks();
        renderInspector();
        markDirty();
    }

    function renderInspector() {
        const block = selectedBlock();
        if (!block) {
            elements.inspectorTitle.textContent = 'Thuộc tính block';
            elements.inspectorType.textContent = 'Chọn một block để chỉnh sửa';
            elements.inspector.innerHTML = '<div class="builder-empty">Chọn block ở danh sách hoặc trong preview để cấu hình chi tiết.</div>';
            return;
        }

        elements.inspectorTitle.textContent = block.settings?.title || block.type.toUpperCase();
        elements.inspectorType.textContent = block.type;

        elements.inspector.innerHTML = `
            <div class="builder-field">
                <label for="block-enabled">Trạng thái block</label>
                <select id="block-enabled" class="form-select">
                    <option value="1" ${block.enabled ? 'selected' : ''}>Kích hoạt hiển thị</option>
                    <option value="0" ${block.enabled ? '' : 'selected'}>Tạm ẩn block</option>
                </select>
            </div>
            ${Object.entries(block.settings || {}).map(([key, value]) => Array.isArray(value)
                ? renderArrayField(key, value, block)
                : renderScalarField(`settings.${key}`, key, value)
            ).join('')}
        `;

        bindInspectorEvents();
    }

    function renderScalarField(path, key, value) {
        const safePath = escapeHtml(path);
        const safeValue = escapeHtml(value !== null && value !== undefined ? value : '');

        if (key === 'align') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${labels[key] || key}</label>
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
                    <label for="field-${safePath}">${labels[key] || key}</label>
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
                    <label for="field-${safePath}">${labels[key] || key}</label>
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
                    <label for="field-${safePath}">${labels[key] || key}</label>
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
                    <label for="field-${safePath}" class="form-check-label" style="margin: 0; cursor: pointer;">${labels[key] || key}</label>
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
                <label for="field-${safePath}">${labels[key] || key}</label>
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

    function renderArrayField(key, items, block) {
        return `
            <div class="builder-array-container border rounded p-3 mb-3 bg-light">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong class="text-dark small">${labels[key] || key}</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-item="${escapeHtml(key)}" title="Thêm mục mới">
                        <iconify-icon icon="solar:add-circle-line-duotone" class="align-middle me-1"></iconify-icon> Thêm
                    </button>
                </div>
                <div class="builder-array-list">
                    ${items.map((item, index) => `
                        <div class="builder-array-item border rounded p-2 mb-2 bg-white" data-array-index="${index}">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-secondary">Mục #${index + 1}</span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" data-remove-item="${escapeHtml(key)}" data-item-index="${index}" title="Xóa mục này">
                                    <iconify-icon icon="solar:trash-bin-trash-line-duotone"></iconify-icon>
                                </button>
                            </div>
                            ${Object.entries(item).map(([subKey, subVal]) =>
                                renderScalarField(`settings.${key}.${index}.${subKey}`, subKey, subVal)
                            ).join('')}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function bindInspectorEvents() {
        const block = selectedBlock();
        if (!block) return;

        elements.inspector.querySelector('#block-enabled')?.addEventListener('change', (e) => {
            block.enabled = e.target.value === '1';
            renderBlocks();
            markDirty();
        });

        // Scalar fields
        elements.inspector.querySelectorAll('[data-field-path]').forEach((field) => {
            const update = () => {
                const val = field.type === 'checkbox' ? field.checked : field.value;
                setPath(block, field.dataset.fieldPath, val);
                markDirty();
            };
            field.addEventListener('input', update);
            field.addEventListener('change', update);
        });

        // Add item to array
        elements.inspector.querySelectorAll('[data-add-item]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.addItem;
                const items = block.settings[key];
                if (!Array.isArray(items) || items.length >= 15) return;
                items.push(arrayTemplates.items(block.type));
                renderInspector();
                markDirty();
            });
        });

        // Remove item from array
        elements.inspector.querySelectorAll('[data-remove-item]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.removeItem;
                const index = parseInt(btn.dataset.itemIndex, 10);
                const items = block.settings[key];
                if (!Array.isArray(items)) return;
                items.splice(index, 1);
                renderInspector();
                markDirty();
            });
        });

        // Image selector launch button
        elements.inspector.querySelectorAll('[data-media-path]').forEach((btn) => {
            btn.addEventListener('click', () => {
                state.media.activeBlockId = block.id;
                state.media.activePath = btn.dataset.mediaPath;
                launchMediaLibrary();
            });
        });
    }

    // Media Modal Functions
    function launchMediaLibrary() {
        if (!mediaModal) {
            mediaModal = new bootstrap.Modal(elements.mediaModalEl);
        }
        mediaModal.show();
        loadMediaLibrary();
    }

    async function loadMediaLibrary() {
        elements.mediaGrid.innerHTML = '<div class="builder-loading">Đang tải thư viện ảnh...</div>';
        try {
            const data = await request(urls.media);
            state.media.items = data.items || [];
            elements.mediaStorage.textContent = data.cloudinary_configured ? 'Ưu tiên Cloudinary' : 'Lưu trữ cục bộ';
            renderMediaGrid();
        } catch (err) {
            showMediaAlert(err.message || 'Không thể tải thư viện ảnh.');
        }
    }

    function renderMediaGrid() {
        const query = elements.mediaSearch.value.trim().toLowerCase();
        const filtered = state.media.items.filter((item) =>
            item.name.toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            elements.mediaGrid.innerHTML = '<div class="builder-empty">Không tìm thấy ảnh phù hợp.</div>';
            return;
        }

        elements.mediaGrid.innerHTML = filtered.map((item) => `
            <div class="builder-media-item" data-media-url="${escapeHtml(item.url)}" role="button" tabindex="0">
                <div class="builder-media-thumb">
                    <img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.name)}" loading="lazy">
                </div>
                <div class="builder-media-name" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
            </div>
        `).join('');

        elements.mediaGrid.querySelectorAll('.builder-media-item').forEach((box) => {
            box.addEventListener('click', () => {
                elements.mediaGrid.querySelectorAll('.builder-media-item').forEach(b => b.classList.remove('is-selected'));
                box.classList.add('is-selected');
                elements.mediaUse.disabled = false;
                elements.mediaSelection.textContent = basename(box.dataset.mediaUrl);
            });
        });
    }

    function basename(path) {
        return path.split('/').pop() || 'Ảnh';
    }

    // Add Block Dialog Logic
    function initAddBlock() {
        if (!elements.addBlockModalEl) return;
        addBlockModal = new bootstrap.Modal(elements.addBlockModalEl);

        elements.addBlockModalEl.querySelectorAll('[data-add-block-type]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.addBlockType;
                if (!blockTemplates[type]) return;

                const newBlock = {
                    id: crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).substring(2, 9),
                    type,
                    version: 1,
                    enabled: true,
                    settings: blockTemplates[type](),
                };

                if (!state.layout.blocks) {
                    state.layout.blocks = [];
                }

                state.layout.blocks.push(newBlock);
                state.selectedId = newBlock.id;

                addBlockModal.hide();
                renderBlocks();
                renderInspector();
                markDirty();
            });
        });
    }

    // Load Builder Initial Data
    async function loadLayout() {
        try {
            const data = await request(urls.draft);
            state.payload = data;
            state.layout = data.draft || { schema_version: 1, blocks: [] };
            state.selectedId = state.layout.blocks?.[0]?.id || null;
            lockVersion = data.lock_version || 1;

            elements.workspace.removeAttribute('aria-busy');
            renderBlocks();
            renderInspector();
            updateStatus();
            
            // Post initial layout preview
            setTimeout(() => sendPreview({ scrollToSelected: false }), 600);
        } catch (err) {
            showAlert(err.message || 'Không thể tải cấu trúc trang.');
        }
    }

    // Save draft
    async function saveDraft(silent = false) {
        if (!state.dirty) return;
        
        window.clearTimeout(state.saveTimer);
        if (state.savePromise) return;

        if (!silent) {
            elements.status.textContent = 'Đang lưu...';
        }

        try {
            const response = await request(urls.save, {
                method: 'PUT',
                body: JSON.stringify({
                    layout: state.layout,
                    lock_version: lockVersion,
                }),
            });

            state.dirty = false;
            window.adminFormIsDirty = false;
            lockVersion = response.lock_version;
            state.payload.updated_at = response.updated_at;
            
            updateStatus();
            hideAlert();
        } catch (err) {
            console.error('Save error', err);
            if (err.status === 409) {
                showAlert('Phiên chỉnh sửa bị xung đột. Vui lòng làm mới trang (F5) để tải phiên bản mới nhất.', 'danger');
                elements.save.disabled = true;
                elements.publish.disabled = true;
            } else {
                showAlert(err.message || 'Lỗi khi lưu bản nháp.', 'danger');
            }
        } finally {
            state.savePromise = null;
        }
    }

    // Publish Page
    async function publishPage() {
        if (state.dirty) {
            await saveDraft(false);
            if (state.dirty) return; // Save failed
        }

        elements.status.textContent = 'Đang xuất bản...';
        elements.publish.disabled = true;

        try {
            const response = await request(urls.publish, {
                method: 'POST',
                body: JSON.stringify({
                    lock_version: lockVersion,
                }),
            });

            lockVersion = response.lock_version;
            state.payload.published_at = response.published_at;
            state.payload.updated_at = response.updated_at;
            state.dirty = false;
            window.adminFormIsDirty = false;

            updateStatus();
            showAlert('Trang tĩnh đã được xuất bản thành công!', 'success');
            setTimeout(hideAlert, 4000);
        } catch (err) {
            showAlert(err.message || 'Lỗi khi xuất bản trang.', 'danger');
            updateStatus();
        }
    }

    // Event Bindings
    function bindEvents() {
        // Save & Publish
        elements.save.addEventListener('click', () => saveDraft(false));
        elements.publish.addEventListener('click', publishPage);

        // Sidebar Collapse
        elements.toggleSections?.addEventListener('click', () => {
            state.collapsedPanels.sections = !state.collapsedPanels.sections;
            updateCollapsedPanels();
        });
        elements.toggleInspector?.addEventListener('click', () => {
            state.collapsedPanels.inspector = !state.collapsedPanels.inspector;
            updateCollapsedPanels();
        });

        // Viewport Switcher
        elements.viewportButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                elements.viewportButtons.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                elements.stage.dataset.viewport = btn.dataset.viewport;
            });
        });

        // Image Search
        elements.mediaSearch.addEventListener('input', () => {
            renderMediaGrid();
        });

        // Upload Media File
        elements.mediaUpload.addEventListener('click', () => {
            elements.mediaFile.click();
        });

        elements.mediaFile.addEventListener('change', async () => {
            const file = elements.mediaFile.files[0];
            if (!file) return;

            const fd = new FormData();
            fd.append('file', file);
            fd.append('folder', 'general');

            elements.mediaUpload.disabled = true;
            showMediaAlert('Đang tải ảnh lên...', 'info');

            try {
                const uploaded = await request(urls.upload, {
                    method: 'POST',
                    body: fd,
                });
                
                showMediaAlert('Tải ảnh lên thành công!', 'success');
                setTimeout(() => elements.mediaAlert?.classList.add('d-none'), 2000);
                
                await loadMediaLibrary();
                
                // Auto-select uploaded file
                const found = [...elements.mediaGrid.querySelectorAll('.builder-media-item')]
                    .find(box => box.dataset.mediaUrl === uploaded.url);
                if (found) found.click();
            } catch (err) {
                showMediaAlert(err.message || 'Lỗi khi tải ảnh lên.');
            } finally {
                elements.mediaUpload.disabled = false;
                elements.mediaFile.value = '';
            }
        });

        // Select Image Use
        elements.mediaUse.addEventListener('click', () => {
            const selected = elements.mediaGrid.querySelector('.builder-media-item.is-selected');
            if (!selected || !state.media.activeBlockId || !state.media.activePath) return;

            const block = state.layout.blocks.find(b => b.id === state.media.activeBlockId);
            if (block) {
                setPath(block, state.media.activePath, selected.dataset.mediaUrl);
                syncInspectorField(state.media.activePath, selected.dataset.mediaUrl);
                markDirty();
            }
            mediaModal.hide();
        });

        // Warn before leaving unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (state.dirty) {
                e.preventDefault();
                e.returnValue = 'Bạn có thay đổi chưa lưu trên giao diện thiết kế.';
            }
        });

        // Preview messaging listener
        window.addEventListener('message', (event) => {
            if (event.data?.type === 'sly_custom_page_builder_select_block') {
                const id = event.data.blockId;
                if (state.layout.blocks.some(b => b.id === id)) {
                    state.selectedId = id;
                    renderBlocks();
                    renderInspector();
                    
                    // Scroll sidebar to item
                    const itemEl = elements.list.querySelector(`[data-block-id="${id}"]`);
                    if (itemEl) {
                        itemEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                }
            }
        });
    }

    function updateCollapsedPanels() {
        elements.workspace.classList.toggle('is-sections-collapsed', state.collapsedPanels.sections);
        elements.workspace.classList.toggle('is-inspector-collapsed', state.collapsedPanels.inspector);

        if (elements.toggleSections) {
            elements.toggleSections.querySelector('iconify-icon')?.setAttribute(
                'icon',
                state.collapsedPanels.sections ? 'solar:alt-arrow-right-line-duotone' : 'solar:alt-arrow-left-line-duotone'
            );
        }
        if (elements.toggleInspector) {
            elements.toggleInspector.querySelector('iconify-icon')?.setAttribute(
                'icon',
                state.collapsedPanels.inspector ? 'solar:alt-arrow-left-line-duotone' : 'solar:alt-arrow-right-line-duotone'
            );
        }
    }

    // Run Setup
    initAddBlock();
    bindEvents();
    loadLayout();

})();
