import './bootstrap';
import { reportTabs } from './report-tabs';

// Register Alpine.js components globally for Filament
window.Alpine = window.Alpine || {};
window.Alpine.data = window.Alpine.data || function () {};

// Register reportTabs component when Alpine is available
if (window.Alpine) {
    window.Alpine.data('reportTabs', reportTabs);
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('reportTabs', reportTabs);
    });
}

