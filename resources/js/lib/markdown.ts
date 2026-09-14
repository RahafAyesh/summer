import { Marked } from 'marked';
import DOMPurify from 'dompurify';

const marked = new Marked({
    breaks: true,
    gfm: true,
});

marked.use({
    renderer: {
        link({ href, title, text }) {
            const titleAttr = title ? ` title="${title}"` : '';
            return `<a href="${href}"${titleAttr} target="_blank" rel="noopener noreferrer">${text}</a>`;
        },
        heading({ text, depth }) {
            const level = Math.min(Math.max(depth, 3), 4);
            return `<h${level}>${text}</h${level}>`;
        },
    },
});

export function renderMarkdown(text: string): string {
    if (!text) return '';
    const raw = marked.parse(text) as string;
    return DOMPurify.sanitize(raw, {
        ADD_ATTR: ['target', 'rel'],
    });
}
