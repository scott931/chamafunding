import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Load payment handlers
import './payments';
import './backer-dashboard';
