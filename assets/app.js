import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// custom: import bs5 css
import 'bootstrap/dist/css/bootstrap.min.css';
// custom: import bootstrap JS for dropdown functionality
import 'bootstrap';

// Import admin functionality
import './admin.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
