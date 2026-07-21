// history-manager.js
import { store } from './builder-store.js';

export class HistoryManager {
    constructor() {
        this.undoStack = [];
        this.redoStack = [];
        this.maxSize = 30;
    }

    init() {
        this.undoStack = [];
        this.redoStack = [];
        // Save initial state
        this.pushSnapshot();
    }

    pushSnapshot() {
        // Clone layout blocks structure
        const layout = store.getLayout();
        if (!layout) return;

        const snapshot = JSON.parse(JSON.stringify(layout));

        // Avoid pushing identical consecutive states
        if (this.undoStack.length > 0) {
            const last = this.undoStack[this.undoStack.length - 1];
            if (JSON.stringify(last) === JSON.stringify(snapshot)) {
                return;
            }
        }

        this.undoStack.push(snapshot);
        if (this.undoStack.length > this.maxSize) {
            this.undoStack.shift();
        }
        this.redoStack = []; // Clear redo stack on new action
    }

    undo() {
        if (this.undoStack.length <= 1) return; // Need at least initial state

        const current = this.undoStack.pop();
        this.redoStack.push(current);

        const previous = this.undoStack[this.undoStack.length - 1];
        
        // Restore layout in store
        const layout = store.getLayout();
        layout.blocks = JSON.parse(JSON.stringify(previous.blocks));
        store.markDirty();
        store.trigger('structure:changed');
    }

    redo() {
        if (this.redoStack.length === 0) return;

        const next = this.redoStack.pop();
        this.undoStack.push(next);

        // Restore layout in store
        const layout = store.getLayout();
        layout.blocks = JSON.parse(JSON.stringify(next.blocks));
        store.markDirty();
        store.trigger('structure:changed');
    }

    canUndo() {
        return this.undoStack.length > 1;
    }

    canRedo() {
        return this.redoStack.length > 0;
    }
}

export const historyManager = new HistoryManager();
