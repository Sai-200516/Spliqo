import './bootstrap';

import Alpine from 'alpinejs';
import Chart  from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart  = Chart;

// Theme store
Alpine.store('theme', {
    dark: localStorage.getItem('theme') === 'dark' ||
          (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        fetch('/profile/theme', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body: JSON.stringify({ theme: this.dark ? 'dark' : 'light' }),
        }).catch(() => {});
    },
    init() {
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

// Toasts store
Alpine.store('toasts', {
    items: [],
    add(message, type = 'success') {
        const id = Date.now();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), 4000);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

window.addEventListener('toast', (e) => Alpine.store('toasts').add(e.detail.message, e.detail.type));

// Notifications store
Alpine.store('notifications', {
    unread: 0,
    items: [],
    loaded: false,
});

Alpine.start();

// Seed unread count from the server-rendered meta tag (set before Alpine boots)
document.addEventListener('DOMContentLoaded', () => {
    const meta = document.querySelector('meta[name="notif-unread"]');
    if (meta) {
        Alpine.store('notifications').unread = parseInt(meta.content, 10) || 0;
    }

    // Real-time: listen for new notifications via Reverb
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta && window.Echo) {
        window.Echo.private(`notifications.${userIdMeta.content}`)
            .listen('.notification.sent', (e) => {
                Alpine.store('notifications').unread++;
                Alpine.store('notifications').items.unshift({
                    id:         e.id,
                    type:       e.type,
                    title:      e.title,
                    message:    e.message,
                    is_read:    false,
                    created_at: 'just now',
                });
                Alpine.store('toasts').add(e.title, 'info');
            });
    }
});

// Service worker — only register in production builds
// In development, actively unregister any stale SW to prevent offline interception
if ('serviceWorker' in navigator) {
    if (import.meta.env.PROD) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    } else {
        navigator.serviceWorker.getRegistrations().then((registrations) => {
            registrations.forEach((reg) => reg.unregister());
        });
    }
}
