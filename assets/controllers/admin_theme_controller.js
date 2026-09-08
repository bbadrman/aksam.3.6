import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sun', 'moon'];

    connect() {
        this.sync();
    }

    toggle() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
        this.sync();
    }

    sync() {
        const isDark = document.documentElement.classList.contains('dark');
        this.sunTarget.classList.toggle('hidden', isDark);
        this.moonTarget.classList.toggle('hidden', !isDark);
    }
}
