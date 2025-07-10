// Admin Dashboard Background Customization

// Function to change color tone - moved outside DOMContentLoaded for global access
function changeColorTone(tone) {
    console.log('Changing color tone to:', tone);
    
    // Remove existing tone classes
    document.body.classList.remove('bg-tone-blue', 'bg-tone-green', 'bg-tone-purple', 'bg-tone-orange', 'bg-tone-ocean');
    
    // Add the new tone class
    if (tone && tone !== 'default') {
        document.body.classList.add('bg-tone-' + tone);
        console.log('Added class:', 'bg-tone-' + tone);
    }
    
    // Update sidebar status
    const statusElement = document.getElementById('sidebarBgStatus');
    if (statusElement) {
        const statusTexts = {
            'default': 'Dark Theme Active',
            'blue': 'Blue Theme Active',
            'green': 'Green Theme Active',
            'purple': 'Purple Theme Active',
            'orange': 'Orange Theme Active',
            'ocean': 'Ocean Theme Active (Default)'
        };
        statusElement.textContent = statusTexts[tone] || 'Ocean Theme Active';
    }
    
    // Store the preference
    localStorage.setItem('admin-bg-tone', tone);
    console.log('Color tone saved:', tone);
}

// Make function available globally
window.changeColorTone = changeColorTone;

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on an admin page
    if (window.location.pathname.includes('/admin')) {
        // Add the custom background class to the body
        document.body.classList.add('ea-admin-bg');
        
        // Load saved color tone preference or set ocean as default
        const savedTone = localStorage.getItem('admin-bg-tone');
        if (savedTone && savedTone !== 'default') {
            document.body.classList.add('bg-tone-' + savedTone);
            
            // Update status when page loads
            setTimeout(() => {
                const statusElement = document.getElementById('sidebarBgStatus');
                if (statusElement) {
                    const statusTexts = {
                        'blue': 'Blue Theme Active',
                        'green': 'Green Theme Active',
                        'purple': 'Purple Theme Active',
                        'orange': 'Orange Theme Active',
                        'ocean': 'Ocean Theme Active (Default)'
                    };
                    statusElement.textContent = statusTexts[savedTone] || 'Ocean Theme Active';
                }
            }, 1000);
        } else {
            // Set ocean as default if no preference is saved
            document.body.classList.add('bg-tone-ocean');
            localStorage.setItem('admin-bg-tone', 'ocean');
            
            // Update status when page loads
            setTimeout(() => {
                const statusElement = document.getElementById('sidebarBgStatus');
                if (statusElement) {
                    statusElement.textContent = 'Ocean Theme Active (Default)';
                }
            }, 1000);
        }
        
        // Create the color tone selector only for admin pages (not login)
        createColorToneSelector();
    }
    
    // Create color tone selector
    function createColorToneSelector() {
        console.log('Creating color tone selector...');
        
        // Add to the sidebar - try multiple selectors to find the right location
        let sidebarElement = document.querySelector('.ea-sidebar') || 
                           document.querySelector('.sidebar') || 
                           document.querySelector('.main-sidebar') ||
                           document.querySelector('aside');
        
        console.log('Found sidebar element:', sidebarElement);
        
        if (sidebarElement) {
            // Create a sidebar section for background controls
            const sidebarSection = document.createElement('div');
            sidebarSection.className = 'ea-sidebar-bg-controls';
            sidebarSection.innerHTML = `
                <div class="sidebar-bg-section">
                    <h6 class="sidebar-bg-title">
                        <i class="fas fa-palette"></i> Background Theme
                    </h6>
                    <div class="sidebar-bg-content">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="colorToneDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-palette"></i> Background Tone
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="colorToneDropdown">
                                <li><a class="dropdown-item" href="#" data-tone="ocean">Ocean (Default)</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="default">Dark Theme</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="blue">Blue</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="green">Green</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="purple">Purple</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="orange">Orange</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="sidebar-bg-status" id="sidebarBgStatus">
                        Ocean Theme Active (Default)
                    </div>
                </div>
            `;
            
            // Add to the bottom of the sidebar
            sidebarElement.appendChild(sidebarSection);
            console.log('Sidebar section added');
            
            // Add event listeners AFTER adding to DOM
            const menuItems = sidebarSection.querySelectorAll('[data-tone]');
            console.log('Found menu items:', menuItems.length);
            
            menuItems.forEach((item, index) => {
                console.log('Adding event listener to item', index, 'with tone:', item.getAttribute('data-tone'));
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Menu item clicked!');
                    const tone = this.getAttribute('data-tone');
                    console.log('Tone selected:', tone);
                    changeColorTone(tone);
                });
            });
            
            console.log('Color tone selector added to sidebar:', sidebarElement.className);
            
            // Setup sidebar monitoring after widget is created
            setupSidebarMonitoring();
        } else {
            console.log('No EasyAdmin sidebar found - theme picker not added');
        }
    }
    
    // Function to check sidebar state and control widget visibility
    function controlWidgetVisibility() {
        const widget = document.querySelector('.ea-sidebar-bg-controls');
        if (!widget) {
            console.log('Widget not found');
            return;
        }
        
        // Check various indicators of sidebar collapsed state
        const sidebarWrapper = document.querySelector('.sidebar-wrapper');
        const sidebar = document.querySelector('.sidebar');
        const body = document.body;
        const navigationToggler = document.getElementById('navigation-toggler');
        
        let isCollapsed = false;
        let reasons = [];
        
        // Primary check: Navigation toggler visibility (indicates mobile/responsive mode)
        if (navigationToggler) {
            const togglerStyle = window.getComputedStyle(navigationToggler);
            const isTogglerVisible = togglerStyle.display !== 'none' && togglerStyle.visibility !== 'hidden';
            
            if (isTogglerVisible) {
                isCollapsed = true;
                reasons.push('navigation-toggler-visible');
            }
        }
        
        // Check if sidebar wrapper width is very small (collapsed state)
        if (sidebarWrapper && !isCollapsed) {
            const sidebarRect = sidebarWrapper.getBoundingClientRect();
            if (sidebarRect.width < 80) {
                isCollapsed = true;
                reasons.push('sidebar-width-small: ' + sidebarRect.width);
            }
        }
        
        // Check if window is very small (mobile)
        if (window.innerWidth < 768 && !isCollapsed) {
            isCollapsed = true;
            reasons.push('window-width-mobile: ' + window.innerWidth);
        }
        
        // Show/hide widget based on collapsed state
        if (isCollapsed) {
            widget.style.display = 'none';
            console.log('Widget hidden - reasons:', reasons);
        } else {
            widget.style.display = 'block';
            console.log('Widget shown - sidebar appears expanded');
        }
        
        // Safety check: if we can't determine the state reliably, show the widget
        if (!navigationToggler && !sidebarWrapper) {
            console.log('Widget shown - unable to determine sidebar state reliably');
            widget.style.display = 'block';
            return;
        }
        
        console.log('Widget visibility check - collapsed:', isCollapsed, 'widget display:', widget.style.display);
    }
    
    // Monitor for sidebar state changes
    function setupSidebarMonitoring() {
        // Initial check
        setTimeout(controlWidgetVisibility, 500);
        
        // Listen for window resize
        window.addEventListener('resize', controlWidgetVisibility);
        
        // Listen for navigation toggler clicks
        const navigationToggler = document.getElementById('navigation-toggler');
        if (navigationToggler) {
            navigationToggler.addEventListener('click', () => {
                setTimeout(controlWidgetVisibility, 100);
            });
        }
        
        // Listen for potential sidebar width changes via ResizeObserver
        if (window.ResizeObserver) {
            const sidebarWrapper = document.querySelector('.sidebar-wrapper');
            if (sidebarWrapper) {
                const resizeObserver = new ResizeObserver(() => {
                    controlWidgetVisibility();
                });
                resizeObserver.observe(sidebarWrapper);
            }
        }
        
        // Fallback: check periodically
        setInterval(controlWidgetVisibility, 2000);
    }
    
    // Debug: Add manual color tone testing
    console.log('Admin.js loaded - Background customization active');
    console.log('Current body classes:', document.body.className);
    console.log('Current pathname:', window.location.pathname);
});

// Make functions available globally for testing
window.showThemeWidget = function() {
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    if (widget) {
        widget.style.display = 'block';
        console.log('Theme widget force shown');
    }
};

window.hideThemeWidget = function() {
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    if (widget) {
        widget.style.display = 'none';
        console.log('Theme widget force hidden');
    }
};

window.checkWidgetState = function() {
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    const sidebarWrapper = document.querySelector('.sidebar-wrapper');
    const navigationToggler = document.getElementById('navigation-toggler');
    
    console.log('Widget state check:');
    console.log('- Widget exists:', !!widget);
    console.log('- Widget display:', widget ? widget.style.display : 'N/A');
    console.log('- Sidebar wrapper exists:', !!sidebarWrapper);
    console.log('- Sidebar wrapper width:', sidebarWrapper ? sidebarWrapper.getBoundingClientRect().width : 'N/A');
    console.log('- Navigation toggler exists:', !!navigationToggler);
    console.log('- Navigation toggler visible:', navigationToggler ? window.getComputedStyle(navigationToggler).display !== 'none' : 'N/A');
    console.log('- Window width:', window.innerWidth);
};
