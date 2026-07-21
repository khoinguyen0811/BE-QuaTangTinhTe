// builder-store.js
export class BuilderStore {
    constructor() {
        this.state = {
            payload: null,
            layout: null,
            selectedId: null,
            dirty: false,
            lockVersion: 1,
        };
        this.listeners = {
            'selection:changed': [],
            'structure:changed': [],
            'block-settings:changed': [],
            'rich-text:changed': [],
            'save-status:changed': [],
        };
    }

    init(payload) {
        this.state.payload = payload;
        this.state.layout = payload.draft || { schema_version: 1, blocks: [] };
        this.state.lockVersion = payload.lock_version || 1;
        this.state.selectedId = this.state.layout.blocks?.[0]?.id || null;
        this.state.dirty = false;
        this.trigger('structure:changed');
        this.trigger('selection:changed');
        this.trigger('save-status:changed');
    }

    getLayout() {
        return this.state.layout;
    }

    getLockVersion() {
        return this.state.lockVersion;
    }

    setLockVersion(version) {
        this.state.lockVersion = version;
        this.trigger('save-status:changed');
    }

    getPayload() {
        return this.state.payload;
    }

    getSelectedId() {
        return this.state.selectedId;
    }

    isDirty() {
        return this.state.dirty;
    }

    setSelectedId(id) {
        if (this.state.selectedId !== id) {
            this.state.selectedId = id;
            this.trigger('selection:changed');
        }
    }

    markDirty() {
        if (!this.state.dirty) {
            this.state.dirty = true;
            window.adminFormIsDirty = true;
            this.trigger('save-status:changed');
        }
    }

    markClean() {
        if (this.state.dirty) {
            this.state.dirty = false;
            window.adminFormIsDirty = false;
            this.trigger('save-status:changed');
        }
    }

    selectedBlock() {
        return this.state.layout?.blocks?.find(b => b.id === this.state.selectedId) || null;
    }

    updateBlockSettings(blockId, newSettings, { isRichText = false } = {}) {
        const block = this.state.layout?.blocks?.find(b => b.id === blockId);
        if (block) {
            block.settings = { ...block.settings, ...newSettings };
            this.markDirty();
            if (isRichText) {
                this.trigger('rich-text:changed', { blockId, settings: block.settings });
            } else {
                this.trigger('block-settings:changed', { blockId, settings: block.settings });
            }
        }
    }

    toggleBlockEnabled(blockId) {
        const block = this.state.layout?.blocks?.find(b => b.id === blockId);
        if (block) {
            block.enabled = !block.enabled;
            this.markDirty();
            this.trigger('structure:changed');
        }
    }

    addBlock(newBlock) {
        if (!this.state.layout.blocks) {
            this.state.layout.blocks = [];
        }
        this.state.layout.blocks.push(newBlock);
        this.state.selectedId = newBlock.id;
        this.markDirty();
        this.trigger('structure:changed');
        this.trigger('selection:changed');
    }

    duplicateBlock(id, newId) {
        const index = this.state.layout.blocks.findIndex(b => b.id === id);
        if (index === -1) return;

        const original = this.state.layout.blocks[index];
        const newBlock = JSON.parse(JSON.stringify(original));
        newBlock.id = newId;
        newBlock.settings.title = original.settings.title + ' (Bản sao)';

        this.state.layout.blocks.splice(index + 1, 0, newBlock);
        this.state.selectedId = newBlock.id;
        this.markDirty();
        this.trigger('structure:changed');
        this.trigger('selection:changed');
    }

    deleteBlock(id) {
        this.state.layout.blocks = this.state.layout.blocks.filter(b => b.id !== id);
        if (this.state.selectedId === id) {
            this.state.selectedId = this.state.layout.blocks?.[0]?.id || null;
        }
        this.markDirty();
        this.trigger('structure:changed');
        this.trigger('selection:changed');
    }

    reorderBlocks(orderedIds) {
        if (!this.state.layout.blocks) return;
        this.state.layout.blocks.sort((a, b) => orderedIds.indexOf(a.id) - orderedIds.indexOf(b.id));
        this.markDirty();
        this.trigger('structure:changed');
    }

    subscribe(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    trigger(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(cb => cb(data));
        }
    }
}

export const store = new BuilderStore();
