// media-manager.js
import { store } from './builder-store.js';

export class MediaManager {
    constructor() {
        this.mediaUrl = '';
        this.uploadUrl = '';
        this.csrf = '';
        this.mediaModal = null;
        this.items = [];
        this.activeCallback = null;
        this.elements = {};
    }

    init(config, elements) {
        this.mediaUrl = config.mediaUrl;
        this.uploadUrl = config.uploadUrl;
        this.csrf = config.csrf;
        this.elements = elements;
        this.bindEvents();
    }

    bindEvents() {
        const { mediaSearch, mediaUpload, mediaFile, mediaUse, mediaGrid } = this.elements;

        if (mediaSearch) {
            mediaSearch.addEventListener('input', () => this.renderGrid());
        }

        if (mediaUpload) {
            mediaUpload.addEventListener('click', () => mediaFile?.click());
        }

        if (mediaFile) {
            mediaFile.addEventListener('change', async () => {
                const file = mediaFile.files[0];
                if (!file) return;

                const fd = new FormData();
                fd.append('file', file);
                fd.append('folder', 'general');

                if (mediaUpload) mediaUpload.disabled = true;
                this.showAlert('Đang tải ảnh lên...', 'info');

                try {
                    const response = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: fd,
                    });

                    const json = await response.json();
                    if (!response.ok || json.success === false) {
                        throw new Error(json.message || 'Lỗi khi tải ảnh lên.');
                    }

                    const uploaded = json.data ?? json;
                    this.showAlert('Tải ảnh lên thành công!', 'success');
                    setTimeout(() => this.elements.mediaAlert?.classList.add('d-none'), 2000);

                    await this.loadLibrary();

                    // Auto-select uploaded file
                    const found = [...mediaGrid.querySelectorAll('.builder-media-item')]
                        .find(box => box.dataset.mediaUrl === uploaded.url);
                    if (found) found.click();
                } catch (err) {
                    this.showAlert(err.message || 'Lỗi khi tải ảnh lên.', 'danger');
                } finally {
                    if (mediaUpload) mediaUpload.disabled = false;
                    mediaFile.value = '';
                }
            });
        }

        if (mediaUse) {
            mediaUse.addEventListener('click', () => {
                const selected = mediaGrid?.querySelector('.builder-media-item.is-selected');
                if (!selected) return;

                const mediaUrl = selected.dataset.mediaUrl;
                const mediaId = selected.dataset.mediaId;
                const mediaAlt = selected.dataset.mediaAlt || '';

                if (this.activeCallback) {
                    this.activeCallback({ url: mediaUrl, id: mediaId, alt: mediaAlt });
                }

                this.mediaModal?.hide();
            });
        }
    }

    launch(callback) {
        this.activeCallback = callback;
        if (!this.mediaModal && window.bootstrap?.Modal) {
            this.mediaModal = new window.bootstrap.Modal(this.elements.mediaModalEl);
        }
        this.mediaModal?.show();
        this.loadLibrary();
    }

    async loadLibrary() {
        const { mediaGrid, mediaStorage } = this.elements;
        if (mediaGrid) {
            mediaGrid.innerHTML = '<div class="builder-loading">Đang tải thư viện ảnh...</div>';
        }

        try {
            const response = await fetch(this.mediaUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                }
            });
            const json = await response.json();
            const data = json.data ?? json;

            this.items = data.items || [];
            if (mediaStorage) {
                mediaStorage.textContent = data.cloudinary_configured ? 'Ưu tiên Cloudinary' : 'Lưu trữ cục bộ';
            }
            this.renderGrid();
        } catch (err) {
            this.showAlert(err.message || 'Không thể tải thư viện ảnh.', 'danger');
        }
    }

    renderGrid() {
        const { mediaGrid, mediaSearch, mediaUse, mediaSelection } = this.elements;
        if (!mediaGrid) return;

        const query = mediaSearch?.value.trim().toLowerCase() || '';
        const filtered = this.items.filter((item) =>
            item.name.toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            mediaGrid.innerHTML = '<div class="builder-empty">Không tìm thấy ảnh phù hợp.</div>';
            return;
        }

        mediaGrid.innerHTML = filtered.map((item) => `
            <div class="builder-media-item" data-media-url="${this.escapeHtml(item.url)}" data-media-id="${this.escapeHtml(item.public_id)}" data-media-alt="${this.escapeHtml(item.name)}" role="button" tabindex="0">
                <div class="builder-media-thumb">
                    <img src="${this.escapeHtml(item.url)}" alt="${this.escapeHtml(item.name)}" loading="lazy">
                </div>
                <div class="builder-media-name" title="${this.escapeHtml(item.name)}">${this.escapeHtml(item.name)}</div>
            </div>
        `).join('');

        mediaGrid.querySelectorAll('.builder-media-item').forEach((box) => {
            box.addEventListener('click', () => {
                mediaGrid.querySelectorAll('.builder-media-item').forEach(b => b.classList.remove('is-selected'));
                box.classList.add('is-selected');
                if (mediaUse) mediaUse.disabled = false;
                if (mediaSelection) mediaSelection.textContent = box.dataset.mediaUrl.split('/').pop() || 'Ảnh';
            });
        });
    }

    showAlert(message, type = 'danger') {
        const alertEl = this.elements.mediaAlert;
        if (alertEl) {
            alertEl.className = `alert alert-${type}`;
            alertEl.textContent = message;
            alertEl.classList.remove('d-none');
        }
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

export const mediaManager = new MediaManager();
