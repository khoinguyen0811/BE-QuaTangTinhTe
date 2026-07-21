// sortable-manager.js
import Sortable from 'sortablejs';
import { store } from './builder-store.js';
import { historyManager } from './history-manager.js';
import { autosaveManager } from './autosave-manager.js';

export class SortableManager {
    constructor() {
        this.instance = null;
    }

    init(container, { onReorder } = {}) {
        this.destroy();

        if (!container) return;

        this.instance = Sortable.create(container, {
            draggable: '.builder-section-item',
            handle: '.builder-drag-handle',
            filter: 'input, textarea, select, button, a, .tiptap, [contenteditable="true"]',
            preventOnFilter: false,
            animation: 150,
            dataIdAttr: 'data-block-id',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',

            onEnd: () => {
                const orderedIds = this.instance.toArray();
                store.reorderBlocks(orderedIds);
                historyManager.pushSnapshot();
                autosaveManager.schedule();
                
                if (onReorder) {
                    onReorder();
                }
            },
        });
    }

    destroy() {
        if (this.instance) {
            this.instance.destroy();
            this.instance = null;
        }
    }
}

export const sortableManager = new SortableManager();
