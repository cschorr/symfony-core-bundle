// Ocean theme only - NO WIDGETS
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('/admin')) {
        document.body.classList.add('ea-admin-bg', 'bg-tone-ocean');
    }
});
