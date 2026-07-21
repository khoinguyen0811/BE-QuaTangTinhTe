// index.js
import '../../../css/admin/custom-page-builder.css';
import { store } from './builder-store.js';
import { historyManager } from './history-manager.js';
import { autosaveManager } from './autosave-manager.js';
import { mediaManager } from './media-manager.js';
import { tiptapManager } from './tiptap-manager.js';
import { blockRenderer } from './block-renderer.js';

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
    
    const labels = {
        title: 'Tiêu đề',
        description: 'Mô tả ngắn',
        content: 'Nội dung văn bản',
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

    const collapsedPanels = {
        sections: false,
        inspector: false,
    };

    let addBlockModal = null;

    function showAlert(message, type = 'danger') {
        elements.alert.className = `alert alert-${type}`;
        elements.alert.textContent = message;
        elements.alert.classList.remove('d-none');
    }

    function hideAlert() {
        elements.alert.classList.add('d-none');
    }

    function initAddBlock() {
        if (!elements.addBlockModalEl || !window.bootstrap?.Modal) return;
        addBlockModal = new window.bootstrap.Modal(elements.addBlockModalEl);

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

                store.addBlock(newBlock);
                historyManager.pushSnapshot();
                autosaveManager.schedule();
                addBlockModal.hide();
            });
        });
    }

    // Save page layout manually
    async function saveDraftManual() {
        try {
            await autosaveManager.flush();
            showAlert('Bản nháp đã được lưu thành công!', 'success');
            setTimeout(hideAlert, 2000);
        } catch (err) {
            showAlert(err.message || 'Lỗi khi lưu bản nháp.', 'danger');
        }
    }

    // Publish page
    async function publishPage() {
        // First flush any pending autosaves
        await autosaveManager.flush();

        elements.status.textContent = 'Đang xuất bản...';
        elements.publish.disabled = true;

        try {
            const response = await fetch(urls.publish, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    lock_version: store.getLockVersion(),
                }),
            });

            const text = await response.text();
            let json = {};
            try {
                json = text ? JSON.parse(text) : {};
            } catch {
                json = { message: text || 'Phản hồi không hợp lệ.' };
            }

            if (!response.ok || json.success === false) {
                throw new Error(json.message || 'Lỗi khi xuất bản trang.');
            }

            const data = json.data ?? json;
            store.setLockVersion(data.lock_version);
            store.getPayload().published_at = data.published_at;
            store.getPayload().updated_at = data.updated_at;
            store.markClean();

            blockRenderer.updateStatus();
            showAlert('Trang tĩnh đã được xuất bản thành công!', 'success');
            setTimeout(hideAlert, 4000);
        } catch (err) {
            showAlert(err.message || 'Lỗi khi xuất bản trang.', 'danger');
            blockRenderer.updateStatus();
        }
    }

    function bindEvents() {
        // Manual Save & Publish
        elements.save.addEventListener('click', saveDraftManual);
        elements.publish.addEventListener('click', publishPage);

        // Close inspector panel
        document.getElementById('builder-inspector-close')?.addEventListener('click', () => {
            store.setSelectedId(null);
        });

        // Sidebar Collapse
        elements.toggleSections?.addEventListener('click', () => {
            collapsedPanels.sections = !collapsedPanels.sections;
            updateCollapsedPanels();
        });
        elements.toggleInspector?.addEventListener('click', () => {
            collapsedPanels.inspector = !collapsedPanels.inspector;
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

        // Warn before leaving unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (store.isDirty()) {
                e.preventDefault();
                e.returnValue = 'Bạn có thay đổi chưa lưu trên giao diện thiết kế.';
            }
        });

        // Preview messaging listener
        window.addEventListener('message', (event) => {
            if (event.data?.type === 'sly_custom_page_builder_select_block') {
                const id = event.data.blockId;
                const layout = store.getLayout();
                if (layout?.blocks?.some(b => b.id === id)) {
                    store.setSelectedId(id);
                    
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
        elements.workspace.classList.toggle('is-sections-collapsed', collapsedPanels.sections);
        elements.workspace.classList.toggle('is-inspector-collapsed', collapsedPanels.inspector);

        if (elements.toggleSections) {
            elements.toggleSections.querySelector('iconify-icon')?.setAttribute(
                'icon',
                collapsedPanels.sections ? 'solar:alt-arrow-right-line-duotone' : 'solar:alt-arrow-left-line-duotone'
            );
        }
        if (elements.toggleInspector) {
            elements.toggleInspector.querySelector('iconify-icon')?.setAttribute(
                'icon',
                collapsedPanels.inspector ? 'solar:alt-arrow-left-line-duotone' : 'solar:alt-arrow-right-line-duotone'
            );
        }
    }

    // Load initial layout data
    async function loadLayout() {
        try {
            const response = await fetch(urls.draft, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                }
            });
            const json = await response.json();
            const data = json.data ?? json;

            // Initialize Store
            store.init(data);

            // Initialize History Manager
            historyManager.init();

            // Initialize Autosave Manager
            autosaveManager.init(urls.save, csrf, {
                onConflict: () => {
                    showAlert('Phiên chỉnh sửa bị xung đột. Vui lòng làm mới trang (F5) để tải phiên bản mới nhất.', 'danger');
                    elements.save.disabled = true;
                    elements.publish.disabled = true;
                },
                onStatusChange: (statusMsg) => {
                    elements.status.textContent = statusMsg;
                }
            });

            // Initialize Media Manager
            mediaManager.init({
                mediaUrl: urls.media,
                uploadUrl: urls.upload,
                csrf: csrf,
            }, elements);

            // Initialize Block Renderer
            blockRenderer.init(elements, labels, blockTemplates, arrayTemplates);

            // Remove busy state and draw view
            elements.workspace.removeAttribute('aria-busy');
            blockRenderer.renderBlocks();
            blockRenderer.renderInspector();
            blockRenderer.updateStatus();

            // Preview initial post message
            setTimeout(() => blockRenderer.sendPreview({ scrollToSelected: false }), 600);
        } catch (err) {
            showAlert(err.message || 'Không thể tải cấu trúc trang.');
        }
    }

    // Initialize Builder
    initAddBlock();
    bindEvents();
    loadLayout();
})();
