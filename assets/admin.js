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

// Simple function to check EasyAdmin's sidebar state using official system
function isEasyAdminSidebarCollapsed() {
    const body = document.body;
    
    // Check EasyAdmin's official CSS classes that are added by page-layout.js
    const hasFullContentWidth = body.classList.contains('ea-content-width-full');
    
    // Check for very small screens
    if (window.innerWidth < 576) {
        return true;
    }
    
    // EasyAdmin sets ea-content-width-full when sidebar is collapsed
    return hasFullContentWidth;
}

// Make helper function available globally
window.isEasyAdminSidebarCollapsed = isEasyAdminSidebarCollapsed;

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 DOMContentLoaded fired');
    console.log('🌐 Current URL:', window.location.href);
    console.log('📍 Pathname:', window.location.pathname);
    
    // Check if we're on an admin page
    if (window.location.pathname.includes('/admin')) {
        console.log('✅ Admin page detected');
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
        
        // Widget creation disabled - just use ocean theme automatically
        // if (!document.querySelector('.ea-sidebar-bg-controls')) {
        //     createColorToneSelector();
        // }
    }
    
    // Create color tone selector
    function createColorToneSelector() {
        console.log('Creating color tone selector...');
        
        // Wait a bit for EasyAdmin to fully load
        setTimeout(() => {
            // Try multiple approaches to find EasyAdmin's sidebar
            let sidebarElement = null;
            
            // First try specific EasyAdmin selectors
            const possibleSelectors = [
                '.ea-sidebar',
                '[data-ea-sidebar]',
                '#ea-sidebar',
                'aside.sidebar',
                '.sidebar',
                '.main-sidebar',
                'aside',
                '[role="navigation"]',
                '.sidebar-wrapper',
                '#sidebar'
            ];
            
            console.log('Testing sidebar selectors...');
            for (const selector of possibleSelectors) {
                const element = document.querySelector(selector);
                console.log(`${selector}:`, element ? `Found (${element.tagName}.${element.className})` : 'Not found');
                if (element && !sidebarElement) {
                    sidebarElement = element;
                }
            }
            
            console.log('Selected sidebar element:', sidebarElement);
            console.log('Sidebar element class:', sidebarElement ? sidebarElement.className : 'N/A');
            console.log('Sidebar element tag:', sidebarElement ? sidebarElement.tagName : 'N/A');
            
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
            console.log('Sidebar section added to:', sidebarElement.tagName, sidebarElement.className);
            console.log('Widget element:', sidebarSection);
            console.log('Widget in DOM:', document.contains(sidebarSection));
            
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
            
            // Setup EasyAdmin sidebar state watching
            setupEasyAdminSidebarWatch();
            
            // Force check visibility after creation
            setTimeout(() => {
                console.log('Post-creation widget check...');
                const widget = document.querySelector('.ea-sidebar-bg-controls');
                console.log('Widget found after creation:', !!widget);
                if (widget) {
                    console.log('Widget display after creation:', widget.style.display);
                    console.log('Widget computed display:', window.getComputedStyle(widget).display);
                    console.log('Widget offsetHeight:', widget.offsetHeight);
                    console.log('Widget offsetWidth:', widget.offsetWidth);
                }
            }, 100);
            
        } else {
            console.log('No EasyAdmin sidebar found - trying fallback location');
            // Try to add to a navigation area or main container as fallback
            const fallbackContainer = document.querySelector('.wrapper') || 
                                    document.querySelector('.main-header') || 
                                    document.querySelector('body');
            
            if (fallbackContainer) {
                console.log('Adding widget to fallback container');
                // Create a simpler fallback widget
                const fallbackWidget = document.createElement('div');
                fallbackWidget.className = 'ea-sidebar-bg-controls-fallback';
                fallbackWidget.style.cssText = `
                    position: fixed;
                    top: 100px;
                    right: 20px;
                    z-index: 9999;
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 15px;
                    border-radius: 8px;
                    font-size: 14px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                `;
                fallbackWidget.innerHTML = `
                    <div style="margin-bottom: 10px;">
                        <strong><i class="fas fa-palette"></i> Background Theme</strong>
                    </div>
                    <select onchange="changeColorTone(this.value)" style="width: 100%; padding: 5px; border-radius: 4px;">
                        <option value="ocean">Ocean (Default)</option>
                        <option value="default">Dark Theme</option>
                        <option value="blue">Blue</option>
                        <option value="green">Green</option>
                        <option value="purple">Purple</option>
                        <option value="orange">Orange</option>
                    </select>
                    <div id="sidebarBgStatus" style="margin-top: 8px; font-size: 12px; text-align: center; opacity: 0.8;">
                        Ocean Theme Active (Default)
                    </div>
                `;
                fallbackContainer.appendChild(fallbackWidget);
                console.log('Fallback widget added');
            }
        }
        }, 1000); // Wait for EasyAdmin to fully load
    }
    
    // Simple widget visibility controller
    function updateWidgetVisibility() {
        const widget = document.querySelector('.ea-sidebar-bg-controls');
        if (!widget) {
            console.log('Widget not found in updateWidgetVisibility');
            return;
        }
        
        const isCollapsed = isEasyAdminSidebarCollapsed();
        const currentDisplay = widget.style.display;
        
        console.log('updateWidgetVisibility - collapsed:', isCollapsed, 'current display:', currentDisplay);
        
        // Only change if different from current state to avoid unnecessary changes
        if (isCollapsed && currentDisplay !== 'none') {
            widget.style.display = 'none';
            console.log('Widget HIDDEN - sidebar collapsed');
        } else if (!isCollapsed && currentDisplay !== 'block') {
            widget.style.display = 'block';
            console.log('Widget SHOWN - sidebar expanded');
        } else {
            console.log('Widget state unchanged');
        }
    }
    
    // Setup monitoring for EasyAdmin sidebar state using official CSS classes
    function setupEasyAdminSidebarWatch() {
        // Initial check
        updateWidgetVisibility();
        
        // Watch for EasyAdmin's CSS class changes on body
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const hasFullWidth = document.body.classList.contains('ea-content-width-full');
                    console.log('EasyAdmin class changed - ea-content-width-full:', hasFullWidth);
                    updateWidgetVisibility();
                }
            });
        });
        
        observer.observe(document.body, { 
            attributes: true, 
            attributeFilter: ['class'] 
        });
        
        // Listen for window resize for responsive behavior
        window.addEventListener('resize', updateWidgetVisibility);
        
        console.log('EasyAdmin CSS class monitoring setup complete');
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
    const navigationToggler = document.getElementById('navigation-toggler');
    const contentWidth = localStorage.getItem('ea/content/width') || document.body.dataset.eaContentWidth;
    
    console.log('EasyAdmin sidebar state check:');
    console.log('- Widget exists:', !!widget);
    console.log('- Widget display:', widget ? widget.style.display : 'N/A');
    console.log('- EasyAdmin content width:', contentWidth);
    console.log('- Navigation toggler exists:', !!navigationToggler);
    console.log('- Navigation toggler visible:', navigationToggler ? window.getComputedStyle(navigationToggler).display !== 'none' : 'N/A');
    console.log('- Window width:', window.innerWidth);
    console.log('- Sidebar collapsed?:', isEasyAdminSidebarCollapsed ? isEasyAdminSidebarCollapsed() : 'function not available');
};

// Add manual test function to check current state immediately
window.testSidebarState = function() {
    console.log('=== EASYADMIN CSS CLASS TEST ===');
    const body = document.body;
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    
    console.log('body.className:', body.className);
    console.log('ea-content-width-full:', body.classList.contains('ea-content-width-full'));
    console.log('ea-content-width-normal:', body.classList.contains('ea-content-width-normal'));
    console.log('ea-sidebar-width-normal:', body.classList.contains('ea-sidebar-width-normal'));
    console.log('ea-sidebar-width-full:', body.classList.contains('ea-sidebar-width-full'));
    
    console.log('data-ea-content-width:', body.dataset.eaContentWidth);
    console.log('data-ea-sidebar-width:', body.dataset.eaSidebarWidth);
    
    console.log('localStorage ea/content/width:', localStorage.getItem('ea/content/width'));
    console.log('localStorage ea/sidebar/width:', localStorage.getItem('ea/sidebar/width'));
    
    console.log('window.innerWidth:', window.innerWidth);
    console.log('widget exists:', !!widget);
    if (widget) {
        console.log('widget.style.display:', widget.style.display);
    }
    
    console.log('isEasyAdminSidebarCollapsed():', isEasyAdminSidebarCollapsed());
    console.log('================================');
};

// Add immediate test functions for debugging
window.forceShowWidget = function() {
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    if (widget) {
        widget.style.display = 'block';
        console.log('Widget force shown');
    } else {
        console.log('Widget not found');
    }
};

window.forceHideWidget = function() {
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    if (widget) {
        widget.style.display = 'none';
        console.log('Widget force hidden');
    } else {
        console.log('Widget not found');
    }
};

window.checkCurrentState = function() {
    const widget = document.querySelector('.ea-sidebar-bg-controls');
    const isCollapsed = isEasyAdminSidebarCollapsed();
    
    console.log('=== CURRENT STATE CHECK ===');
    console.log('Widget exists:', !!widget);
    console.log('Widget display:', widget ? widget.style.display : 'N/A');
    console.log('Sidebar collapsed:', isCollapsed);
    console.log('Body classes:', document.body.className);
    console.log('Should show widget:', !isCollapsed);
    
    if (widget) {
        updateWidgetVisibility();
        console.log('After update - Widget display:', widget.style.display);
    }
    console.log('========================');
};
