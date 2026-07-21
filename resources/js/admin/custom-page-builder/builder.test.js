// builder.test.js
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { store } from './builder-store.js';
import { historyManager } from './history-manager.js';
import { autosaveManager } from './autosave-manager.js';
import { tiptapManager } from './tiptap-manager.js';

// Setup jsdom environment globals if needed
if (typeof window === 'undefined') {
    global.window = {};
}

describe('Custom Page Builder Javascript Modules', () => {
    let mockPayload;

    beforeEach(() => {
        vi.restoreAllMocks();
        
        mockPayload = {
            id: 1,
            title: 'Test Page',
            slug: 'test-page',
            lock_version: 10,
            draft: {
                schema_version: 1,
                blocks: [
                    {
                        id: 'block-1',
                        type: 'rich_text',
                        version: 1,
                        enabled: true,
                        settings: {
                            title: 'Rich text title',
                            content: '<p>Initial content</p>',
                            align: 'left',
                        }
                    },
                    {
                        id: 'block-2',
                        type: 'faq',
                        version: 1,
                        enabled: true,
                        settings: {
                            title: 'FAQ title',
                            items: [{ question: 'Q1', answer: 'A1' }]
                        }
                    }
                ]
            }
        };

        store.init(mockPayload);
        historyManager.init();
    });

    describe('BuilderStore', () => {
        it('should initialize with payload and default values', () => {
            expect(store.getLockVersion()).toBe(10);
            expect(store.getSelectedId()).toBe('block-1');
            expect(store.isDirty()).toBe(false);
            expect(store.getLayout().blocks.length).toBe(2);
        });

        it('should mark store dirty when setting is updated', () => {
            store.updateBlockSettings('block-1', { title: 'New title' });
            expect(store.isDirty()).toBe(true);
            expect(store.selectedBlock().settings.title).toBe('New title');
        });

        it('should support block reordering and preserve block IDs', () => {
            const initialIds = store.getLayout().blocks.map(b => b.id);
            expect(initialIds).toEqual(['block-1', 'block-2']);

            store.reorderBlocks(['block-2', 'block-1']);
            const newIds = store.getLayout().blocks.map(b => b.id);
            expect(newIds).toEqual(['block-2', 'block-1']);
        });

        it('should support duplicate block with a new ID', () => {
            store.duplicateBlock('block-1', 'block-3');
            expect(store.getLayout().blocks.length).toBe(3);
            expect(store.getLayout().blocks[1].id).toBe('block-3');
            expect(store.getLayout().blocks[1].settings.title).toBe('Rich text title (Bản sao)');
        });

        it('should delete a block and fallback selection ID', () => {
            store.deleteBlock('block-1');
            expect(store.getLayout().blocks.length).toBe(1);
            expect(store.getSelectedId()).toBe('block-2');
        });
    });

    describe('HistoryManager', () => {
        it('should push snapshots and execute undo/redo correctly', () => {
            expect(historyManager.canUndo()).toBe(false);

            // Change state and push snapshot
            store.updateBlockSettings('block-1', { title: 'Title change 1' });
            historyManager.pushSnapshot();
            expect(historyManager.canUndo()).toBe(true);

            // Change state again
            store.updateBlockSettings('block-1', { title: 'Title change 2' });
            historyManager.pushSnapshot();

            // Undo first change
            historyManager.undo();
            expect(store.selectedBlock().settings.title).toBe('Title change 1');

            // Undo to initial state
            historyManager.undo();
            expect(store.selectedBlock().settings.title).toBe('Rich text title');

            // Redo
            historyManager.redo();
            expect(store.selectedBlock().settings.title).toBe('Title change 1');
        });
    });

    describe('AutosaveManager', () => {
        it('should maintain single-flight save queue and process sequentially', async () => {
            let callCount = 0;
            const fakeFetch = vi.fn().mockImplementation(() => {
                callCount++;
                return Promise.resolve({
                    ok: true,
                    text: () => Promise.resolve(JSON.stringify({
                        success: true,
                        data: { lock_version: 10 + callCount, updated_at: '2026-07-20T12:00:00Z' }
                    }))
                });
            });
            global.fetch = fakeFetch;

            autosaveManager.init('/save-url', 'csrf-token');

            // Mark store dirty to trigger save
            store.markDirty();

            // Fire first flush
            const promise1 = autosaveManager.flush();
            expect(autosaveManager.saving).toBe(true);

            // Mark dirty again during first saving and request second save
            store.updateBlockSettings('block-1', { title: 'New change while saving' });
            const promise2 = autosaveManager.flush();

            // Expect second request is queued (pendingSave is true)
            expect(autosaveManager.pendingSave).toBe(true);

            await promise1;
            await promise2;

            // Fetch should be called exactly twice sequentially
            expect(fakeFetch).toHaveBeenCalledTimes(2);
            expect(store.getLockVersion()).toBe(12);
        });
    });

    describe('TiptapManager', () => {
        it('should manage and destroy Tiptap instances correctly', () => {
            const container = document.createElement('div');
            
            // Mount editor
            const editor = tiptapManager.mount(container, '<p>Test</p>', () => {});
            expect(tiptapManager.getEditor()).toBe(editor);
            expect(editor.getHTML()).toContain('Test');

            // Destroy editor
            tiptapManager.destroy();
            expect(tiptapManager.getEditor()).toBeNull();
        });
    });
});
