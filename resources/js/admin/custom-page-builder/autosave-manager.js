// autosave-manager.js
import { store } from './builder-store.js';

export class AutosaveManager {
    constructor() {
        this.saveUrl = '';
        this.csrf = '';
        this.saving = false;
        this.pendingSave = false;
        this.saveTimer = null;
        this.onConflictCallback = null;
        this.onStatusChangeCallback = null;
        this.currentPromise = null;
    }

    init(saveUrl, csrf, { onConflict, onStatusChange } = {}) {
        this.saveUrl = saveUrl;
        this.csrf = csrf;
        this.onConflictCallback = onConflict;
        this.onStatusChangeCallback = onStatusChange;
    }

    schedule() {
        if (this.saveTimer) {
            window.clearTimeout(this.saveTimer);
        }
        this.saveTimer = window.setTimeout(() => {
            this.flush();
        }, 1000);
    }

    async flush() {
        if (this.saveTimer) {
            window.clearTimeout(this.saveTimer);
            this.saveTimer = null;
        }

        if (!store.isDirty()) {
            return;
        }

        if (this.saving) {
            this.pendingSave = true;
            return this.currentPromise;
        }

        this.saving = true;
        this.pendingSave = false;
        this.notifyStatus('Đang lưu...');

        const savedLayoutString = JSON.stringify(store.getLayout());

        this.currentPromise = (async () => {
            try {
                const response = await fetch(this.saveUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        layout: store.getLayout(),
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
                    const error = new Error(json.message || 'Không thể lưu bản nháp.');
                    error.status = response.status;
                    error.payload = json;
                    throw error;
                }

                const data = json.data ?? json;
                store.setLockVersion(data.lock_version);
                
                if (JSON.stringify(store.getLayout()) === savedLayoutString) {
                    store.markClean();
                    this.notifyStatus('Đã lưu nháp');
                } else {
                    this.pendingSave = true;
                    this.notifyStatus('Đang chờ lưu thay đổi mới...');
                }
            } catch (err) {
                console.error('Save draft error', err);
                if (err.status === 409) {
                    if (this.onConflictCallback) {
                        this.onConflictCallback();
                    }
                    this.notifyStatus('Xung đột phiên bản');
                    // Stop any further autosaves
                    this.pendingSave = false;
                } else {
                    this.notifyStatus('Lỗi khi lưu');
                    throw err;
                }
            } finally {
                this.saving = false;
                this.currentPromise = null;
                
                if (this.pendingSave) {
                    await this.flush();
                }
            }
        })();

        return this.currentPromise;
    }

    notifyStatus(msg) {
        if (this.onStatusChangeCallback) {
            this.onStatusChangeCallback(msg);
        }
    }
}

export const autosaveManager = new AutosaveManager();
