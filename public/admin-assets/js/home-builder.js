(function () {
    'use strict';

    const root = document.getElementById('home-builder-root');
    if (!root) return;

    const elements = {
        workspace: root.querySelector('.builder-workspace'),
        list: document.getElementById('builder-section-list'),
        inspector: document.getElementById('builder-inspector'),
        inspectorTitle: document.getElementById('builder-inspector-title'),
        inspectorType: document.getElementById('builder-inspector-type'),
        frame: document.getElementById('builder-preview-frame'),
        stage: document.getElementById('builder-preview-stage'),
        toggleSections: document.getElementById('builder-toggle-sections'),
        toggleInspector: document.getElementById('builder-toggle-inspector'),
        status: document.getElementById('builder-status'),
        alert: document.getElementById('builder-alert'),
        save: document.getElementById('builder-save'),
        publish: document.getElementById('builder-publish'),
        history: document.getElementById('builder-history'),
        historyList: document.getElementById('builder-history-list'),
        historyModal: document.getElementById('builderHistoryModal'),
        mediaModal: document.getElementById('builderMediaModal'),
        mediaGrid: document.getElementById('builder-media-grid'),
        mediaSearch: document.getElementById('builder-media-search'),
        mediaStorage: document.getElementById('builder-media-storage'),
        mediaUpload: document.getElementById('builder-media-upload'),
        mediaFile: document.getElementById('builder-media-file'),
        mediaUse: document.getElementById('builder-media-use'),
        mediaSelection: document.getElementById('builder-media-selection'),
        mediaAlert: document.getElementById('builder-media-alert'),
    };

    const urls = {
        draft: root.dataset.draftUrl,
        save: root.dataset.saveUrl,
        publish: root.dataset.publishUrl,
        versions: root.dataset.versionsUrl,
        rollback: root.dataset.rollbackBase,
        media: root.dataset.mediaUrl,
        upload: root.dataset.uploadUrl,
    };

    const csrf = root.dataset.csrf;
    const labels = {
        eyebrow: 'Tiêu đề nhỏ',
        title: 'Tiêu đề chính',
        description: 'Mô tả',
        desktop_image: 'Ảnh desktop',
        mobile_image: 'Ảnh mobile',
        image_url: 'Hình ảnh',
        image_alt: 'Mô tả ảnh (SEO)',
        avatar_url: 'Ảnh đại diện',
        primary_label: 'Tên nút chính',
        primary_href: 'Liên kết nút chính',
        secondary_label: 'Tên nút phụ',
        secondary_href: 'Liên kết nút phụ',
        button_label: 'Tên nút',
        button_href: 'Liên kết nút',
        caption_eyebrow: 'Dòng nhỏ trên ảnh',
        caption_title: 'Chú thích ảnh',
        media_type: 'Loại nội dung',
        video_url: 'Liên kết video YouTube',
        metrics: 'Chỉ số nổi bật',
        cards: 'Danh sách thẻ',
        items: 'Danh sách nội dung',
        value: 'Giá trị',
        label: 'Nhãn',
        href: 'Liên kết',
        name: 'Tên khách hàng',
        content: 'Nội dung đánh giá',
        icon: 'Biểu tượng',
        overlay_enabled: 'Bật phủ mờ (Overlay)',
        hide_text: 'Ẩn chữ (Chỉ hiển thị banner)',
        show_arrows: 'Hiển thị nút mũi tên chuyển slide',
        show_dots: 'Hiển thị chấm tròn chuyển slide',
        autoplay: 'Tự động chạy slideshow',
        autoplay_interval: 'Thời gian chuyển slide (ms)',
        transition_duration: 'Thời gian hiệu ứng chuyển (ms)',
        pause_on_hover: 'Tạm dừng slideshow khi di chuột qua',
    };

    const arrayTemplates = {
        metrics: { value: '', label: '' },
        cards: { label: '', image_url: '', image_alt: '', href: '/collection' },
        items: { label: '', icon: 'fa-gem' },
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
        categories: [],
        collectionProducts: [],
        collapsedPanels: {
            sections: false,
            inspector: false,
        },
        media: {
            items: [],
            activeSectionId: null,
            activePath: null,
            selectedUrl: null,
            preferredStorage: 'local',
            cloudinaryConfigured: false,
        },
    };

    const panelStorageKey = 'sly.home-builder.collapsed-panels';

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getProductName(p) {
        if (!p) return '';
        if (typeof p.name === 'object' && p.name) {
            return p.name.vi || p.name.en || '';
        }
        return String(p.name || '');
    }

    function flattenCategories(list, depth = 0) {
        let options = [];
        (list || []).forEach((cat) => {
            const name = typeof cat.name === 'object' ? (cat.name.vi || cat.name.en || '') : cat.name;
            options.push({ id: cat.id, label: '—'.repeat(depth) + ' ' + name });
            if (Array.isArray(cat.children) && cat.children.length > 0) {
                options = options.concat(flattenCategories(cat.children, depth + 1));
            }
        });
        return options;
    }

    async function loadCollectionProducts(ids) {
        if (!ids || ids.length === 0) {
            state.collectionProducts = [];
            return;
        }
        try {
            const data = await request(`/api/products?ids=${ids.join(',')}&include_inactive=1`);
            state.collectionProducts = data || [];
        } catch (err) {
            console.warn('Failed to load collection products details', err);
        }
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

    function selectedSection() {
        return state.layout?.sections?.find((section) => section.id === state.selectedId) || null;
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

    function isEditablePath(section, path, imageOnly = false) {
        if (!section || !/^props(?:\.[a-z][a-z0-9_]*|\.\d+)+$/i.test(String(path || ''))) return false;
        const current = getPath(section, path);
        if (!['string', 'number'].includes(typeof current)) return false;
        if (!imageOnly) return true;
        const field = String(path).split('.').pop();
        return isImageField(field);
    }

    function syncInspectorField(path, value) {
        const field = [...elements.inspector.querySelectorAll('[data-field-path]')]
            .find((item) => item.dataset.fieldPath === path);
        if (field && document.activeElement !== field) field.value = value;
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

    function hideMediaAlert() {
        elements.mediaAlert?.classList.add('d-none');
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
        } else if (state.payload.has_unpublished_changes) {
            elements.status.textContent = `Bản nháp #${state.payload.draft_revision} chưa xuất bản`;
            elements.status.classList.add('is-dirty');
        } else {
            elements.status.textContent = `Đã xuất bản #${state.payload.published_revision}`;
            elements.status.classList.add('is-published');
        }
        elements.save.disabled = !state.dirty;
        elements.publish.disabled = state.dirty ? false : !state.payload.has_unpublished_changes;
    }

    function markDirty({ syncPreview = true, fullPreview = false, previewSectionId = state.selectedId } = {}) {
        state.dirty = true;
        window.adminFormIsDirty = true;
        updateStatus();
        if (syncPreview) sendPreview({ sectionId: fullPreview ? null : previewSectionId, debounce: true });
        window.clearTimeout(state.saveTimer);
        state.saveTimer = window.setTimeout(() => saveDraft(true), 1800);
    }

    function sendPreview({ scrollToSelected = false, sectionId = null, debounce = false } = {}) {
        if (!state.layout || !elements.frame.contentWindow) return;
        const dispatch = () => {
            state.previewTimer = null;
            elements.frame.contentWindow.postMessage({
                type: 'sly_home_builder_preview',
                layout: state.layout,
                selectedId: state.selectedId,
                sectionId,
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

    function updateCollapsedPanels() {
        elements.workspace.classList.toggle('is-sections-collapsed', state.collapsedPanels.sections);
        elements.workspace.classList.toggle('is-inspector-collapsed', state.collapsedPanels.inspector);

        const controls = [
            {
                button: elements.toggleSections,
                collapsed: state.collapsedPanels.sections,
                hiddenLabel: 'Hiện danh sách section',
                visibleLabel: 'Ẩn danh sách section',
                hiddenIcon: 'solar:alt-arrow-right-line-duotone',
                visibleIcon: 'solar:alt-arrow-left-line-duotone',
            },
            {
                button: elements.toggleInspector,
                collapsed: state.collapsedPanels.inspector,
                hiddenLabel: 'Hiện bảng thuộc tính',
                visibleLabel: 'Ẩn bảng thuộc tính',
                hiddenIcon: 'solar:alt-arrow-left-line-duotone',
                visibleIcon: 'solar:alt-arrow-right-line-duotone',
            },
        ];

        controls.forEach((control) => {
            if (!control.button) return;
            const label = control.collapsed ? control.hiddenLabel : control.visibleLabel;
            control.button.setAttribute('aria-expanded', String(!control.collapsed));
            control.button.setAttribute('aria-label', label);
            control.button.title = label;
            control.button.querySelector('iconify-icon')?.setAttribute(
                'icon',
                control.collapsed ? control.hiddenIcon : control.visibleIcon,
            );
        });

        try {
            window.localStorage.setItem(panelStorageKey, JSON.stringify(state.collapsedPanels));
        } catch {
            // Trình duyệt có thể chặn localStorage trong chế độ riêng tư.
        }
    }

    function restoreCollapsedPanels() {
        try {
            const saved = JSON.parse(window.localStorage.getItem(panelStorageKey) || '{}');
            state.collapsedPanels.sections = saved.sections === true;
            state.collapsedPanels.inspector = saved.inspector === true;
        } catch {
            state.collapsedPanels = { sections: false, inspector: false };
        }
        updateCollapsedPanels();
    }

    function renderSections() {
        if (!state.layout?.sections?.length) {
            elements.list.innerHTML = '<div class="builder-empty">Chưa có section nào.</div>';
            return;
        }

        elements.list.innerHTML = state.layout.sections.map((section, index) => `
            <div
                class="builder-section-item ${section.id === state.selectedId ? 'is-selected' : ''} ${section.enabled ? '' : 'is-disabled'}"
                data-section-id="${escapeHtml(section.id)}"
                draggable="true"
                role="button"
                tabindex="0"
                aria-pressed="${section.id === state.selectedId ? 'true' : 'false'}"
            >
                <span class="builder-drag-handle" title="Kéo để sắp xếp">
                    <iconify-icon icon="solar:hamburger-menu-line-duotone"></iconify-icon>
                </span>
                <span class="builder-section-copy">
                    <strong>${index + 1}. ${escapeHtml(section.name)}</strong>
                    <small>${escapeHtml(section.type)}</small>
                </span>
                <button type="button" class="builder-visibility" data-toggle-section="${escapeHtml(section.id)}" title="${section.enabled ? 'Ẩn section' : 'Hiện section'}" aria-label="${section.enabled ? 'Ẩn section' : 'Hiện section'}">
                    <iconify-icon icon="${section.enabled ? 'solar:eye-line-duotone' : 'solar:eye-closed-line-duotone'}"></iconify-icon>
                </button>
            </div>
        `).join('');

        bindSectionEvents();
    }

    function bindSectionEvents() {
        elements.list.querySelectorAll('.builder-section-item').forEach((item) => {
            const select = () => {
                state.selectedId = item.dataset.sectionId;
                renderSections();
                renderInspector();
                sendPreview({ scrollToSelected: true });
            };

            item.addEventListener('click', (event) => {
                if (event.target.closest('[data-toggle-section]')) return;
                select();
            });
            item.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    select();
                }
            });
            item.addEventListener('dragstart', (event) => {
                state.draggedId = item.dataset.sectionId;
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
                const ids = [...elements.list.querySelectorAll('.builder-section-item')].map((row) => row.dataset.sectionId);
                state.layout.sections.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
                state.draggedId = null;
                renderSections();
                markDirty({ fullPreview: true });
            });
        });

        elements.list.querySelectorAll('[data-toggle-section]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const section = state.layout.sections.find((item) => item.id === button.dataset.toggleSection);
                if (!section) return;
                section.enabled = !section.enabled;
                renderSections();
                if (section.id === state.selectedId) renderInspector();
                markDirty({ previewSectionId: section.id });
            });
        });
    }

    function fieldLabel(key) {
        return labels[key] || key.replace(/_/g, ' ').replace(/^./, (letter) => letter.toUpperCase());
    }

    function isImageField(key) {
        const field = String(key || '').toLowerCase();
        return (field.includes('image') && !field.includes('alt')) || field.includes('avatar');
    }

    function isLongField(key, value) {
        return ['description', 'content'].includes(key) || String(value || '').length > 120;
    }

    function renderScalarField(path, key, value) {
        const safePath = escapeHtml(path);
        const safeValue = escapeHtml(value);
        if (key === 'media_type') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${fieldLabel(key)}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="image" ${value === 'image' ? 'selected' : ''}>Hình ảnh</option>
                        <option value="video" ${value === 'video' ? 'selected' : ''}>Video YouTube</option>
                    </select>
                </div>
            `;
        }

        if (key === 'product_source') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${fieldLabel(key)}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="all" ${value === 'all' ? 'selected' : ''}>Tất cả sản phẩm</option>
                        <option value="category" ${value === 'category' ? 'selected' : ''}>Theo danh mục</option>
                        <option value="manual" ${value === 'manual' ? 'selected' : ''}>Chọn thủ công</option>
                    </select>
                </div>
            `;
        }

        if (key === 'category_id') {
            const catOptions = flattenCategories(state.categories || []);
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${fieldLabel(key)}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="">-- Chọn danh mục --</option>
                        ${catOptions.map(cat => `
                            <option value="${cat.id}" ${String(cat.id) === String(value) ? 'selected' : ''}>${escapeHtml(cat.label)}</option>
                        `).join('')}
                    </select>
                </div>
            `;
        }

        if (key === 'align') {
            return `
                <div class="builder-field">
                    <label for="field-${safePath}">${fieldLabel(key)}</label>
                    <select id="field-${safePath}" class="form-select" data-field-path="${safePath}">
                        <option value="left" ${value === 'left' ? 'selected' : ''}>Căn lề trái</option>
                        <option value="center" ${value === 'center' ? 'selected' : ''}>Căn giữa</option>
                        <option value="right" ${value === 'right' ? 'selected' : ''}>Căn lề phải</option>
                    </select>
                </div>
            `;
        }

        if (key === 'overlay_enabled' || key === 'hide_text' || key === 'show_arrows' || key === 'show_dots' || key === 'autoplay' || key === 'pause_on_hover') {
            return `
                <div class="builder-field" style="display: flex; align-items: center; gap: 0.5rem; flex-direction: row; margin: 1rem 0;">
                    <input id="field-${safePath}" type="checkbox" class="form-check-input" ${value ? 'checked' : ''} data-field-path="${safePath}">
                    <label for="field-${safePath}" class="form-check-label" style="margin: 0; cursor: pointer;">${fieldLabel(key)}</label>
                </div>
            `;
        }

        const input = isLongField(key, value)
            ? `<textarea id="field-${safePath}" class="form-control" data-field-path="${safePath}">${safeValue}</textarea>`
            : `<input id="field-${safePath}" type="text" class="form-control" value="${safeValue}" data-field-path="${safePath}">`;

        return `
            <div class="builder-field">
                <label for="field-${safePath}">${fieldLabel(key)}${key === 'image_alt' ? '<span>SEO</span>' : ''}</label>
                ${isImageField(key) ? `
                    <div class="builder-upload-row">
                        ${input}
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-media-path="${safePath}" title="Chọn từ thư viện ảnh" aria-label="Chọn từ thư viện ảnh">
                            <iconify-icon icon="solar:gallery-wide-line-duotone"></iconify-icon>
                        </button>
                    </div>
                ` : input}
            </div>
        `;
    }

    function itemTemplate(key, items, section) {
        if (key === 'items' && section?.type === 'testimonials') {
            return { name: '', content: '', avatar_url: '' };
        }
        if (items[0] && typeof items[0] === 'object') {
            return Object.fromEntries(Object.keys(items[0]).map((field) => [field, field === 'href' ? '/collection' : '']));
        }
        return clone(arrayTemplates[key] || { label: '' });
    }

    function renderArrayField(key, items) {
        return `
            <section class="builder-array" data-array-key="${escapeHtml(key)}">
                <div class="builder-array-heading">
                    <strong>${escapeHtml(fieldLabel(key))}</strong>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-add-item="${escapeHtml(key)}">
                        <iconify-icon icon="solar:add-circle-line-duotone"></iconify-icon>
                        Thêm
                    </button>
                </div>
                ${items.length ? items.map((item, index) => `
                    <div class="builder-array-item">
                        <div class="builder-array-item-header">
                            <span>Mục ${index + 1}</span>
                            <button type="button" class="btn btn-link btn-sm text-danger p-0" data-remove-item="${escapeHtml(key)}" data-item-index="${index}">Xóa</button>
                        </div>
                        ${Object.entries(item).map(([itemKey, itemValue]) => renderScalarField(`props.${key}.${index}.${itemKey}`, itemKey, itemValue)).join('')}
                    </div>
                `).join('') : '<div class="builder-empty">Chưa có mục nào.</div>'}
            </section>
        `;
    }

    function renderCollectionsInspector(section) {
        elements.inspectorTitle.textContent = section.name;
        elements.inspectorType.textContent = section.type;

        const props = section.props || {};
        const source = props.product_source || 'all';
        const limit = props.product_limit || 8;
        const categoryId = props.category_id || '';
        const ids = props.product_ids || [];

        const catOptions = flattenCategories(state.categories || []);

        let collectionsUi = '';

        if (source === 'category') {
            collectionsUi = `
                <div class="builder-field">
                    <label for="field-props-category_id">Danh mục sản phẩm</label>
                    <select id="field-props-category_id" class="form-select" data-field-path="props.category_id">
                        <option value="">-- Chọn danh mục --</option>
                        ${catOptions.map(cat => `
                            <option value="${cat.id}" ${String(cat.id) === String(categoryId) ? 'selected' : ''}>${escapeHtml(cat.label)}</option>
                        `).join('')}
                    </select>
                </div>
            `;
        } else if (source === 'manual') {
            const productsListHtml = (state.collectionProducts || []).map((product, index) => {
                const isActive = product.is_active !== false && product.is_active !== 0;
                return `
                    <div class="builder-product-item" draggable="true" data-product-id="${product.id}" data-item-index="${index}" style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.5rem; background: #fff; cursor: move;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span class="builder-drag-handle" style="color: #94a3b8;"><iconify-icon icon="solar:hamburger-menu-line-duotone"></iconify-icon></span>
                            <img src="${escapeHtml(product.image_url || product.imageUrl || '')}" alt="${escapeHtml(getProductName(product))}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                            <div style="display: flex; flex-direction: column;">
                                <strong style="font-size: 0.85rem; color: #1e293b;">${escapeHtml(getProductName(product))}</strong>
                                ${!isActive ? `<span style="font-size: 0.75rem; font-weight: 600; color: #ef4444; display: inline-flex; align-items: center; gap: 0.25rem;"><iconify-icon icon="solar:danger-triangle-bold-duotone"></iconify-icon> Ngừng hoạt động (Ẩn)</span>` : ''}
                            </div>
                        </div>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0" data-remove-product="${product.id}" style="text-decoration: none;">Gỡ</button>
                    </div>
                `;
            }).join('');

            collectionsUi = `
                <div class="builder-field">
                    <label>Chọn sản phẩm thủ công (Tối đa 20)</label>
                    <div style="position: relative; margin-bottom: 1rem;">
                        <input id="product-search-input" type="text" class="form-control" placeholder="Tìm sản phẩm theo tên..." autocomplete="off">
                        <div id="product-search-results" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
                    </div>
                    <div id="selected-products-list" class="builder-selected-products-list">
                        ${productsListHtml || '<div class="builder-empty">Chưa có sản phẩm nào được chọn.</div>'}
                    </div>
                </div>
            `;
        }

        elements.inspector.innerHTML = `
            <div class="builder-field">
                <label for="section-enabled">Trạng thái hiển thị</label>
                <select id="section-enabled" class="form-select">
                    <option value="1" ${section.enabled ? 'selected' : ''}>Đang hiển thị</option>
                    <option value="0" ${section.enabled ? '' : 'selected'}>Đang ẩn</option>
                </select>
            </div>
            
            ${renderScalarField('props.eyebrow', 'eyebrow', props.eyebrow)}
            ${renderScalarField('props.title', 'title', props.title)}
            ${renderScalarField('props.button_label', 'button_label', props.button_label)}
            ${renderScalarField('props.button_href', 'button_href', props.button_href)}

            <div class="builder-field">
                <label for="field-props-product_source">Nguồn sản phẩm</label>
                <select id="field-props-product_source" class="form-select" data-field-path="props.product_source">
                    <option value="all" ${source === 'all' ? 'selected' : ''}>Tất cả sản phẩm</option>
                    <option value="category" ${source === 'category' ? 'selected' : ''}>Theo danh mục</option>
                    <option value="manual" ${source === 'manual' ? 'selected' : ''}>Chọn thủ công</option>
                </select>
            </div>

            <div class="builder-field">
                <label for="field-props-product_limit">Số lượng hiển thị</label>
                <input id="field-props-product_limit" type="number" class="form-control" value="${limit}" data-field-path="props.product_limit" min="1" max="50">
            </div>

            ${collectionsUi}
        `;

        bindCollectionsInspectorEvents(section);
    }

    function bindCollectionsInspectorEvents(section) {
        elements.inspector.querySelector('#section-enabled')?.addEventListener('change', (event) => {
            section.enabled = event.target.value === '1';
            renderSections();
            markDirty({ previewSectionId: section.id });
        });

        elements.inspector.querySelectorAll('[data-field-path]').forEach((field) => {
            const update = () => {
                const val = field.type === 'number' ? Number(field.value) : field.value;
                setPath(section, field.dataset.fieldPath, val);
                markDirty({ previewSectionId: section.id });
                if (field.id === 'field-props-product_source') {
                    renderInspector();
                }
            };
            field.addEventListener('change', update);
        });

        elements.inspector.querySelectorAll('[data-remove-product]').forEach((button) => {
            button.addEventListener('click', () => {
                const removeId = Number(button.dataset.removeProduct);
                section.props.product_ids = (section.props.product_ids || []).filter(id => id !== removeId);
                state.collectionProducts = (state.collectionProducts || []).filter(p => p.id !== removeId);
                renderInspector();
                markDirty({ previewSectionId: section.id });
            });
        });

        const productRows = elements.inspector.querySelectorAll('.builder-product-item');
        productRows.forEach((row) => {
            row.addEventListener('dragstart', (event) => {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', row.dataset.productId);
                row.classList.add('is-dragging');
            });
            row.addEventListener('dragover', (event) => {
                event.preventDefault();
                const container = elements.inspector.querySelector('#selected-products-list');
                const dragging = container.querySelector('.is-dragging');
                if (!dragging || dragging === row) return;
                const rect = row.getBoundingClientRect();
                container.insertBefore(dragging, event.clientY < rect.top + rect.height / 2 ? row : row.nextSibling);
            });
            row.addEventListener('dragend', () => {
                row.classList.remove('is-dragging');
                const container = elements.inspector.querySelector('#selected-products-list');
                const newIds = [...container.querySelectorAll('.builder-product-item')].map(el => Number(el.dataset.productId));
                section.props.product_ids = newIds;
                state.collectionProducts.sort((a, b) => newIds.indexOf(a.id) - newIds.indexOf(b.id));
                renderInspector();
                markDirty({ previewSectionId: section.id });
            });
        });

        const searchInput = elements.inspector.querySelector('#product-search-input');
        const resultsBox = elements.inspector.querySelector('#product-search-results');
        if (searchInput && resultsBox) {
            let debounceTimer = null;
            searchInput.addEventListener('input', () => {
                window.clearTimeout(debounceTimer);
                const q = searchInput.value.trim();
                if (q.length < 2) {
                    resultsBox.style.display = 'none';
                    return;
                }
                debounceTimer = window.setTimeout(async () => {
                    try {
                        const data = await request(`/api/products?q=${encodeURIComponent(q)}&include_inactive=1&limit=10`);
                        const products = data || [];
                        if (products.length === 0) {
                            resultsBox.innerHTML = '<div style="padding: 0.5rem; color: #94a3b8; font-size: 0.85rem;">Không tìm thấy sản phẩm nào.</div>';
                        } else {
                            resultsBox.innerHTML = products.map(product => `
                                <div class="search-result-item" data-id="${product.id}" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-bottom: 1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                                    <img src="${escapeHtml(product.image_url || product.imageUrl || '')}" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px;">
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-size: 0.85rem; font-weight: 500;">${escapeHtml(getProductName(product))}</span>
                                        ${product.is_active === false || product.is_active === 0 ? '<span style="font-size: 0.7rem; color: #ef4444; font-weight:600;">Ngừng hoạt động</span>' : ''}
                                    </div>
                                </div>
                            `).join('');

                            resultsBox.querySelectorAll('.search-result-item').forEach(item => {
                                item.addEventListener('click', () => {
                                    const selectId = Number(item.dataset.id);
                                    if (!(section.props.product_ids || []).includes(selectId)) {
                                        section.props.product_ids = section.props.product_ids || [];
                                        section.props.product_ids.push(selectId);
                                        const foundProduct = products.find(p => p.id === selectId);
                                        if (foundProduct) {
                                            state.collectionProducts = state.collectionProducts || [];
                                            state.collectionProducts.push(foundProduct);
                                        }
                                        renderInspector();
                                        markDirty({ previewSectionId: section.id });
                                    }
                                    searchInput.value = '';
                                    resultsBox.style.display = 'none';
                                });
                            });
                        }
                        resultsBox.style.display = 'block';
                    } catch (err) {
                        console.error('Failed to search products', err);
                    }
                }, 250);
            });

            document.addEventListener('click', (event) => {
                if (!searchInput.contains(event.target) && !resultsBox.contains(event.target)) {
                    resultsBox.style.display = 'none';
                }
            });
        }
    }

    function renderInspector() {
        const section = selectedSection();
        if (!section) {
            elements.inspectorTitle.textContent = 'Thuộc tính';
            elements.inspectorType.textContent = 'Chọn một section để chỉnh sửa';
            elements.inspector.innerHTML = '<div class="builder-empty">Chọn section ở danh sách hoặc trong preview.</div>';
            return;
        }

        if (section.id === 'collections') {
            const ids = section.props.product_ids || [];
            const currentCachedIds = (state.collectionProducts || []).map(p => p.id);
            if (JSON.stringify(currentCachedIds) !== JSON.stringify(ids)) {
                elements.inspector.innerHTML = `<div class="builder-loading" style="padding: 2rem; text-align: center; color: #64748b;"><iconify-icon icon="solar:spinner-bold-duotone" class="spin-animation" style="font-size: 2rem; margin-bottom: 0.5rem;"></iconify-icon><div>Đang tải chi tiết sản phẩm...</div></div>`;
                loadCollectionProducts(ids).then(() => {
                    renderInspector();
                });
                return;
            }
            renderCollectionsInspector(section);
            return;
        }

        elements.inspectorTitle.textContent = section.name;
        elements.inspectorType.textContent = section.type;
        elements.inspector.innerHTML = `
            <div class="builder-field">
                <label for="section-enabled">Trạng thái hiển thị</label>
                <select id="section-enabled" class="form-select">
                    <option value="1" ${section.enabled ? 'selected' : ''}>Đang hiển thị</option>
                    <option value="0" ${section.enabled ? '' : 'selected'}>Đang ẩn</option>
                </select>
            </div>
            ${Object.entries(section.props || {}).map(([key, value]) => Array.isArray(value)
                ? renderArrayField(key, value)
                : renderScalarField(`props.${key}`, key, value)
            ).join('')}
        `;

        bindInspectorEvents();
    }

    function mediaFileName(url, fallback = 'Ảnh') {
        try {
            const path = new URL(String(url || ''), root.dataset.previewUrl).pathname;
            return decodeURIComponent(path.split('/').filter(Boolean).pop() || fallback);
        } catch {
            return fallback;
        }
    }

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);
        if (!size) return '';
        if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
    }

    function displayMediaUrl(value) {
        const source = String(value || '').trim();
        if (!source || !/^(https?:\/\/|\/|\.\/|\.\.\/|public\/)/i.test(source)) return '';
        try {
            const url = new URL(source, root.dataset.previewUrl);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch {
            return '';
        }
    }

    function collectLayoutMedia() {
        const items = [];
        const walk = (value, key = '') => {
            if (Array.isArray(value)) {
                value.forEach((item) => walk(item, key));
                return;
            }
            if (value && typeof value === 'object') {
                Object.entries(value).forEach(([childKey, childValue]) => walk(childValue, childKey));
                return;
            }
            if (typeof value !== 'string' || !isImageField(key) || !displayMediaUrl(value)) return;
            items.push({
                url: value,
                name: mediaFileName(value),
                storage: 'layout',
                size: 0,
            });
        };
        state.layout?.sections?.forEach((section) => walk(section.props || {}));
        return items;
    }

    function mergeMediaItems(...groups) {
        const unique = new Map();
        groups.flat().forEach((item) => {
            const url = String(item?.url || '').trim();
            if (!url || !displayMediaUrl(url) || unique.has(url)) return;
            unique.set(url, {
                ...item,
                url,
                name: String(item.name || mediaFileName(url)),
                storage: String(item.storage || 'layout'),
            });
        });
        return [...unique.values()];
    }

    function updateMediaStorage() {
        if (!elements.mediaStorage) return;
        const cloudinary = state.media.cloudinaryConfigured;
        elements.mediaStorage.classList.toggle('is-cloudinary', cloudinary);
        elements.mediaStorage.classList.toggle('is-local', !cloudinary);
        elements.mediaStorage.innerHTML = cloudinary
            ? '<iconify-icon icon="solar:cloud-check-line-duotone"></iconify-icon> Ưu tiên Cloudinary'
            : '<iconify-icon icon="solar:server-square-cloud-line-duotone"></iconify-icon> Lưu trữ cục bộ';
    }

    function renderMediaLibrary() {
        if (!elements.mediaGrid) return;
        const query = String(elements.mediaSearch?.value || '').trim().toLocaleLowerCase('vi');
        const visible = state.media.items
            .map((item, index) => ({ item, index }))
            .filter(({ item }) => !query || `${item.name} ${item.public_id || ''}`.toLocaleLowerCase('vi').includes(query));

        if (!visible.length) {
            elements.mediaGrid.innerHTML = `<div class="builder-media-empty">${query ? 'Không tìm thấy ảnh phù hợp.' : 'Thư viện chưa có ảnh. Bạn có thể tải ảnh từ máy lên.'}</div>`;
        } else {
            elements.mediaGrid.innerHTML = visible.map(({ item, index }) => {
                const selected = item.url === state.media.selectedUrl;
                const previewUrl = displayMediaUrl(item.url);
                const storageLabel = item.storage === 'cloudinary' ? 'Cloudinary' : (item.storage === 'local' ? 'Cục bộ' : 'Đang dùng');
                const size = formatFileSize(item.size);
                return `
                    <button type="button" class="builder-media-item ${selected ? 'is-selected' : ''}" data-media-index="${index}" aria-pressed="${selected ? 'true' : 'false'}" title="${escapeHtml(item.name)}">
                        <span class="builder-media-thumb">
                            <img src="${escapeHtml(previewUrl)}" alt="${escapeHtml(item.name)}" loading="lazy">
                            <span class="builder-media-check"><iconify-icon icon="solar:check-circle-bold"></iconify-icon></span>
                        </span>
                        <span class="builder-media-copy">
                            <strong>${escapeHtml(item.name)}</strong>
                            <small>${escapeHtml([storageLabel, size].filter(Boolean).join(' · '))}</small>
                        </span>
                    </button>
                `;
            }).join('');
        }

        const selected = state.media.items.find((item) => item.url === state.media.selectedUrl);
        elements.mediaUse.disabled = !selected;
        elements.mediaSelection.textContent = selected ? `Đã chọn: ${selected.name}` : 'Chưa chọn ảnh';

        elements.mediaGrid.querySelectorAll('[data-media-index]').forEach((button) => {
            button.addEventListener('click', () => {
                const item = state.media.items[Number(button.dataset.mediaIndex)];
                if (!item) return;
                state.media.selectedUrl = item.url;
                renderMediaLibrary();
            });
        });
        elements.mediaGrid.querySelectorAll('img').forEach((image) => {
            image.addEventListener('error', () => image.closest('.builder-media-thumb')?.classList.add('is-broken'), { once: true });
        });
    }

    async function loadMediaLibrary() {
        elements.mediaGrid.innerHTML = '<div class="builder-loading">Đang tải thư viện ảnh...</div>';
        try {
            const payload = await request(urls.media);
            state.media.cloudinaryConfigured = Boolean(payload.cloudinary_configured);
            state.media.preferredStorage = payload.preferred_storage || 'local';
            state.media.items = mergeMediaItems(payload.items || [], collectLayoutMedia());
            updateMediaStorage();
            renderMediaLibrary();
        } catch (error) {
            state.media.items = mergeMediaItems(collectLayoutMedia());
            renderMediaLibrary();
            showMediaAlert(`Không tải được toàn bộ thư viện: ${error.message}`);
        }
    }

    function mediaModalInstance() {
        return elements.mediaModal ? bootstrap.Modal.getOrCreateInstance(elements.mediaModal) : null;
    }

    function openMediaPicker(section, path) {
        if (!section || !isEditablePath(section, path, true)) return;
        state.media.activeSectionId = section.id;
        state.media.activePath = path;
        state.media.selectedUrl = String(getPath(section, path) || '');
        state.media.items = mergeMediaItems(collectLayoutMedia());
        if (elements.mediaSearch) elements.mediaSearch.value = '';
        if (elements.mediaStorage) elements.mediaStorage.textContent = 'Đang kiểm tra lưu trữ...';
        hideMediaAlert();
        renderMediaLibrary();
        mediaModalInstance()?.show();
        loadMediaLibrary();
    }

    async function uploadMediaFile(file) {
        if (!file) return;
        if (file.size > 8 * 1024 * 1024) {
            showMediaAlert('Hình ảnh không được vượt quá 8 MB.');
            return;
        }
        if (file.type && !file.type.startsWith('image/')) {
            showMediaAlert('Vui lòng chọn đúng định dạng hình ảnh.');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        const originalButton = elements.mediaUpload.innerHTML;
        elements.mediaUpload.disabled = true;
        elements.mediaUpload.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Đang tải ảnh...';
        hideMediaAlert();
        try {
            const uploaded = await request(urls.upload, { method: 'POST', body: formData });
            const item = {
                url: uploaded.url,
                name: uploaded.name || file.name,
                size: uploaded.size || file.size,
                storage: uploaded.storage || state.media.preferredStorage,
            };
            state.media.items = mergeMediaItems([item], state.media.items);
            state.media.selectedUrl = item.url;
            renderMediaLibrary();
            const destination = item.storage === 'cloudinary' ? 'Cloudinary' : 'lưu trữ cục bộ';
            showMediaAlert(`Đã tải ảnh lên ${destination} và chọn sẵn. Nhấn “Dùng ảnh này” để áp dụng.`, 'success');
        } catch (error) {
            showMediaAlert(error.message);
        } finally {
            elements.mediaUpload.disabled = false;
            elements.mediaUpload.innerHTML = originalButton;
            if (elements.mediaFile) elements.mediaFile.value = '';
        }
    }

    function applySelectedMedia() {
        const section = state.layout?.sections?.find((item) => item.id === state.media.activeSectionId);
        const path = state.media.activePath;
        const url = state.media.selectedUrl;
        if (!section || !url || !isEditablePath(section, path, true)) return;

        const changed = getPath(section, path) !== url;
        setPath(section, path, url);
        state.selectedId = section.id;
        renderSections();
        renderInspector();
        if (changed) markDirty();
        mediaModalInstance()?.hide();
        showAlert(changed ? 'Đã dùng ảnh đã chọn và cập nhật bản xem trước.' : 'Ảnh này đang được sử dụng.', 'success');
    }

    function handleMediaModalKeydown(event) {
        if (event.key !== 'F4') return;
        event.preventDefault();
        mediaModalInstance()?.hide();
    }

    function bindInspectorEvents() {
        if (!selectedSection()) return;

        elements.inspector.querySelector('#section-enabled')?.addEventListener('change', (event) => {
            const section = selectedSection();
            if (!section) return;
            section.enabled = event.target.value === '1';
            renderSections();
            markDirty();
        });

        elements.inspector.querySelectorAll('[data-field-path]').forEach((field) => {
            const update = () => {
                const section = selectedSection();
                if (!section) return;
                const val = field.type === 'checkbox' ? field.checked : field.value;
                setPath(section, field.dataset.fieldPath, val);
                markDirty();
            };
            field.addEventListener('input', update);
            field.addEventListener('change', update);
        });

        elements.inspector.querySelectorAll('[data-add-item]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = selectedSection();
                if (!section) return;
                const key = button.dataset.addItem;
                const items = section.props[key];
                if (!Array.isArray(items) || items.length >= 20) return;
                items.push(itemTemplate(key, items, section));
                renderInspector();
                markDirty();
            });
        });

        elements.inspector.querySelectorAll('[data-remove-item]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = selectedSection();
                if (!section) return;
                const items = section.props[button.dataset.removeItem];
                if (!Array.isArray(items)) return;
                items.splice(Number(button.dataset.itemIndex), 1);
                renderInspector();
                markDirty();
            });
        });

        elements.inspector.querySelectorAll('[data-media-path]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = selectedSection();
                if (!section) return;
                openMediaPicker(section, button.dataset.mediaPath);
            });
        });
    }

    async function saveDraft(silent = false) {
        window.clearTimeout(state.saveTimer);
        if (!state.dirty || !state.payload) return state.payload;
        if (state.savePromise) {
            await state.savePromise;
            return state.dirty ? saveDraft(silent) : state.payload;
        }

        const submittedLayout = clone(state.layout);
        const signature = JSON.stringify(submittedLayout);
        elements.save.disabled = true;
        elements.save.textContent = 'Đang lưu...';

        state.savePromise = request(urls.save, {
            method: 'PUT',
            body: JSON.stringify({
                layout: submittedLayout,
                revision: state.payload.draft_revision,
                note: silent ? 'Tự động lưu từ Home Builder' : 'Lưu từ Home Builder',
            }),
        });

        try {
            const payload = await state.savePromise;
            state.payload = payload;
            if (JSON.stringify(state.layout) === signature) {
                const normalizedSignature = JSON.stringify(payload.draft);
                if (normalizedSignature !== signature) {
                    state.layout = clone(payload.draft);
                    renderSections();
                    renderInspector();
                    sendPreview();
                }
                state.dirty = false;
                window.adminFormIsDirty = false;
            }
            if (!silent) showAlert('Đã lưu bản nháp. Khách hàng chưa thấy thay đổi này.', 'success');
        } catch (error) {
            if (error.status === 409) {
                showAlert('Bản nháp đã được thay đổi ở nơi khác. Trang sẽ tải lại dữ liệu mới nhất.');
                await load();
            } else {
                showAlert(error.message);
            }
        } finally {
            state.savePromise = null;
            elements.save.innerHTML = '<iconify-icon icon="solar:diskette-line-duotone"></iconify-icon> Lưu bản nháp';
            updateStatus();
            if (state.dirty) {
                state.saveTimer = window.setTimeout(() => saveDraft(true), 1800);
            }
        }

        return state.payload;
    }

    async function publish() {
        hideAlert();
        if (state.dirty) await saveDraft(false);
        if (state.dirty || !state.payload) return;

        const confirmed = await Swal.fire({
            title: 'Xuất bản trang chủ?',
            text: 'Khách hàng sẽ nhìn thấy toàn bộ thay đổi trong bản nháp.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xuất bản',
            cancelButtonText: 'Hủy',
        });
        if (!confirmed.isConfirmed) return;

        elements.publish.disabled = true;
        elements.publish.textContent = 'Đang xuất bản...';
        try {
            const payload = await request(urls.publish, {
                method: 'POST',
                body: JSON.stringify({ revision: state.payload.draft_revision }),
            });
            state.payload = payload;
            state.layout = clone(payload.draft);
            state.dirty = false;
            sendPreview();
            updateStatus();
            showAlert('Đã xuất bản trang chủ thành công.', 'success');
        } catch (error) {
            showAlert(error.message);
        } finally {
            elements.publish.innerHTML = '<iconify-icon icon="solar:upload-minimalistic-line-duotone"></iconify-icon> Xuất bản';
            updateStatus();
        }
    }

    async function openHistory() {
        elements.historyList.innerHTML = '<div class="builder-loading">Đang tải lịch sử...</div>';
        bootstrap.Modal.getOrCreateInstance(elements.historyModal).show();
        try {
            const versions = await request(urls.versions);
            elements.historyList.innerHTML = versions.length ? versions.map((version) => `
                <div class="builder-history-row">
                    <div>
                        <strong>Phiên bản #${version.revision} · ${version.event === 'published' ? 'Đã xuất bản' : version.event === 'rollback' ? 'Khôi phục' : 'Bản nháp'}</strong>
                        <div>${escapeHtml(version.note || '')}</div>
                        <div class="builder-history-meta">${escapeHtml(version.author)} · ${new Date(version.created_at).toLocaleString('vi-VN')}</div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-rollback-id="${version.id}">Khôi phục</button>
                </div>
            `).join('') : '<div class="builder-empty">Chưa có lịch sử thay đổi.</div>';

            elements.historyList.querySelectorAll('[data-rollback-id]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const confirmed = await Swal.fire({
                        title: 'Khôi phục phiên bản này?',
                        text: 'Phiên bản được đưa vào bản nháp và chưa tự động xuất bản.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Khôi phục',
                        cancelButtonText: 'Hủy',
                    });
                    if (!confirmed.isConfirmed) return;
                    button.disabled = true;
                    try {
                        const payload = await request(`${urls.rollback}/${button.dataset.rollbackId}`, { method: 'POST' });
                        state.payload = payload;
                        state.layout = clone(payload.draft);
                        state.selectedId = state.layout.sections[0]?.id || null;
                        state.dirty = false;
                        renderSections();
                        renderInspector();
                        sendPreview();
                        updateStatus();
                        bootstrap.Modal.getInstance(elements.historyModal)?.hide();
                        showAlert('Đã khôi phục vào bản nháp. Hãy kiểm tra rồi bấm Xuất bản.', 'success');
                    } catch (error) {
                        showAlert(error.message);
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        } catch (error) {
            elements.historyList.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        }
    }

    async function load() {
        hideAlert();
        elements.workspace.setAttribute('aria-busy', 'true');
        try {
            try {
                state.categories = await request('/api/categories');
            } catch (err) {
                console.warn('Failed to pre-fetch categories list', err);
            }
            const payload = await request(urls.draft);
            state.payload = payload;
            state.layout = clone(payload.draft);
            state.selectedId = state.layout.sections[0]?.id || null;
            state.dirty = false;
            window.adminFormIsDirty = false;
            renderSections();
            renderInspector();
            updateStatus();
            sendPreview();
        } catch (error) {
            showAlert(error.message);
            elements.list.innerHTML = '<div class="builder-empty">Không tải được dữ liệu builder.</div>';
        } finally {
            elements.workspace.setAttribute('aria-busy', 'false');
        }
    }

    elements.frame.addEventListener('load', () => window.setTimeout(sendPreview, 150));
    elements.save.addEventListener('click', () => saveDraft(false));
    elements.publish.addEventListener('click', publish);
    elements.history.addEventListener('click', openHistory);
    elements.toggleSections?.addEventListener('click', () => {
        state.collapsedPanels.sections = !state.collapsedPanels.sections;
        updateCollapsedPanels();
    });
    elements.toggleInspector?.addEventListener('click', () => {
        state.collapsedPanels.inspector = !state.collapsedPanels.inspector;
        updateCollapsedPanels();
    });
    root.querySelectorAll('[data-viewport]').forEach((button) => {
        button.addEventListener('click', () => {
            root.querySelectorAll('[data-viewport]').forEach((item) => item.classList.remove('is-active'));
            button.classList.add('is-active');
            elements.stage.dataset.viewport = button.dataset.viewport;
        });
    });
    elements.mediaSearch?.addEventListener('input', renderMediaLibrary);
    elements.mediaUpload?.addEventListener('click', () => elements.mediaFile?.click());
    elements.mediaFile?.addEventListener('change', () => uploadMediaFile(elements.mediaFile.files?.[0]));
    elements.mediaUse?.addEventListener('click', applySelectedMedia);
    elements.mediaModal?.addEventListener('shown.bs.modal', () => {
        document.addEventListener('keydown', handleMediaModalKeydown);
        elements.mediaSearch?.focus();
    });
    elements.mediaModal?.addEventListener('hidden.bs.modal', () => {
        document.removeEventListener('keydown', handleMediaModalKeydown);
        state.media.activeSectionId = null;
        state.media.activePath = null;
    });

    window.addEventListener('message', (event) => {
        if (event.source !== elements.frame.contentWindow) return;
        const message = event.data || {};
        const section = state.layout?.sections?.find((item) => item.id === message.sectionId);
        if (!section) return;

        if (message.type === 'sly_home_builder_select') {
            state.selectedId = section.id;
            renderSections();
            renderInspector();
            return;
        }

        if (message.type === 'sly_home_builder_inline_change') {
            if (!isEditablePath(section, message.path)) return;
            const selectionChanged = state.selectedId !== section.id;
            state.selectedId = section.id;
            setPath(section, message.path, String(message.value ?? ''));
            if (selectionChanged) {
                renderSections();
                renderInspector();
            } else {
                syncInspectorField(message.path, message.value);
            }
            markDirty({ syncPreview: false });
            return;
        }

        if (message.type === 'sly_home_builder_inline_media' && isEditablePath(section, message.path, true)) {
            state.selectedId = section.id;
            renderSections();
            renderInspector();
            openMediaPicker(section, message.path);
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (!state.dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    restoreCollapsedPanels();
    load();
})();
