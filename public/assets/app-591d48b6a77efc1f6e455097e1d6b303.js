import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

import { startStimulusApp } from '@symfony/stimulus-bundle';
import { registerChart } from '@symfony/ux-chartjs';
import '@hotwired/turbo';

// Stimulus configuration
registerChart();
export const app = startStimulusApp();

// Alpine.js configuration
import 'alpinejs';
import '@alpinejs/collapse';

console.log('This log comes from assets/app.js - welcome to AssetMapper! ');
