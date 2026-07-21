// tiptap-manager.js
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import { TableKit } from '@tiptap/extension-table';

// Custom Text Align Extension to output data-text-align
const CustomTextAlign = TextAlign.extend({
    addGlobalAttributes() {
        return [
            {
                types: this.options.types,
                attributes: {
                    textAlign: {
                        default: this.options.defaultAlignment,
                        parseHTML: element => element.getAttribute('data-text-align') || element.style.textAlign || this.options.defaultAlignment,
                        renderHTML: attributes => {
                            if (!attributes.textAlign || attributes.textAlign === this.options.defaultAlignment) {
                                return {};
                            }
                            return {
                                'data-text-align': attributes.textAlign,
                            };
                        },
                    },
                },
            },
        ];
    },
});

// Custom Image Extension to support data-media-id
export const CustomImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            mediaId: {
                default: null,
                parseHTML: element => element.getAttribute('data-media-id'),
                renderHTML: attributes => {
                    if (!attributes.mediaId) {
                        return {};
                    }
                    return {
                        'data-media-id': String(attributes.mediaId),
                    };
                },
            },
        };
    },
});

export class TiptapManager {
    constructor() {
        this.editor = null;
    }

    mount(element, initialContent, onUpdateCallback) {
        this.destroy();

        this.editor = new Editor({
            element: element,
            extensions: [
                StarterKit,
                Underline,
                Link.configure({
                    openOnClick: false,
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                    },
                }),
                CustomImage,
                CustomTextAlign.configure({
                    types: ['heading', 'paragraph'],
                }),
                TableKit.configure({
                    table: {
                        resizable: false,
                    },
                }),
            ],
            content: initialContent,
            onUpdate: ({ editor }) => {
                if (onUpdateCallback) {
                    onUpdateCallback(editor.getHTML());
                }
            },
        });

        return this.editor;
    }

    destroy() {
        if (this.editor) {
            this.editor.destroy();
            this.editor = null;
        }
    }

    getEditor() {
        return this.editor;
    }
}

export const tiptapManager = new TiptapManager();
