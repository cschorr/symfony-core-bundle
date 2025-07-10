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
            'default': 'Default Theme Active',
            'blue': 'Blue Theme Active',
            'green': 'Green Theme Active',
            'purple': 'Purple Theme Active',
            'orange': 'Orange Theme Active',
            'ocean': 'Ocean Theme Active'
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
                        'ocean': 'Ocean Theme Active'
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
                    statusElement.textContent = 'Ocean Theme Active';
                }
            }, 1000);
        }
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
                                <li><a class="dropdown-item" href="#" data-tone="default">Default (Dark)</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="blue">Blue</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="green">Green</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="purple">Purple</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="orange">Orange</a></li>
                                <li><a class="dropdown-item" href="#" data-tone="ocean">Ocean</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="sidebar-bg-status" id="sidebarBgStatus">
                        Ocean Theme Active
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
        } else {
            console.error('Could not find suitable sidebar element');
            // Try to add to body as fallback
            const fallbackElement = document.body;
            console.log('Adding to body as fallback');
            
            const fallbackSection = document.createElement('div');
            fallbackSection.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1000;
                background: rgba(255, 255, 255, 0.9);
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            `;
            fallbackSection.innerHTML = `
                <h6 style="margin: 0 0 10px 0; color: #333;">Background Theme</h6>
                <select id="fallbackColorPicker" style="width: 100%; padding: 5px;">
                    <option value="default">Default (Dark)</option>
                    <option value="blue">Blue</option>
                    <option value="green">Green</option>
                    <option value="purple">Purple</option>
                    <option value="orange">Orange</option>
                    <option value="ocean" selected>Ocean</option>
                </select>
                <div id="fallbackStatus" style="margin-top: 10px; font-size: 12px; color: #666;">Ocean Theme Active</div>
            `;
            
            fallbackElement.appendChild(fallbackSection);
            
            // Add event listener for fallback
            const fallbackPicker = document.getElementById('fallbackColorPicker');
            fallbackPicker.addEventListener('change', function() {
                const tone = this.value;
                console.log('Fallback picker changed to:', tone);
                changeColorTone(tone);
            });
        }
    }
    
    // Create the color tone selector
    createColorToneSelector();
    
    // Debug: Add manual color tone testing
    console.log('Admin.js loaded - Background customization active');
    console.log('Current body classes:', document.body.className);
    console.log('Current pathname:', window.location.pathname);
});

// Add some CSS animations for smooth transitions
const style = document.createElement('style');
style.textContent = `
    /* Sidebar Background Controls Styling */
    .ea-sidebar-bg-controls {
        position: relative;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: auto;
        padding: 15px;
    }
    
    .sidebar-bg-section {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        padding: 15px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-bg-title {
        color: #2c3e50;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 10px;
        text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
    }
    
    .sidebar-bg-title i {
        margin-right: 8px;
        color: #3498db;
        font-size: 1rem;
    }
    
    .sidebar-bg-content .dropdown {
        width: 100%;
    }
    
    .sidebar-bg-content .btn {
        width: 100%;
        background: rgba(108, 117, 125, 0.8);
        border: none;
        color: white;
        font-size: 0.85rem;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    
    .sidebar-bg-content .btn:hover {
        background: rgba(108, 117, 125, 1);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar-bg-content .dropdown-menu {
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .sidebar-bg-content .dropdown-item {
        padding: 8px 16px;
        font-size: 0.85rem;
        color: #2c3e50;
        transition: all 0.3s ease;
    }
    
    .sidebar-bg-content .dropdown-item:hover {
        background: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    /* Active Status Indicator in Sidebar */
    .sidebar-bg-status {
        margin-top: 10px;
        padding: 8px 12px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.8rem;
        color: #2c3e50;
        text-align: center;
        font-weight: 500;
    }
    
    /* Color-specific status indicators */
    body.bg-tone-blue .sidebar-bg-status {
        background: rgba(59, 130, 246, 0.2);
        border-color: rgba(59, 130, 246, 0.3);
        color: #1e40af;
    }
    
    body.bg-tone-green .sidebar-bg-status {
        background: rgba(34, 197, 94, 0.2);
        border-color: rgba(34, 197, 94, 0.3);
        color: #15803d;
    }
    
    body.bg-tone-purple .sidebar-bg-status {
        background: rgba(147, 51, 234, 0.2);
        border-color: rgba(147, 51, 234, 0.3);
        color: #7c2d12;
    }
    
    body.bg-tone-orange .sidebar-bg-status {
        background: rgba(251, 146, 60, 0.2);
        border-color: rgba(251, 146, 60, 0.3);
        color: #c2410c;
    }
    
    body.bg-tone-ocean .sidebar-bg-status {
        background: rgba(0, 168, 134, 0.2);
        border-color: rgba(0, 168, 134, 0.3);
        color: #065f46;
    }
    
    /* Make sidebar taller to accommodate new controls */
    .ea-sidebar {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    /* Ensure main menu takes available space */
    .ea-main-menu {
        flex: 1;
    }
    
    body {
        transition: background-image 0.5s ease-in-out !important;
    }
    
    .ea-main-wrapper,
    .ea-sidebar,
    .ea-content-wrapper,
    .ea-header,
    .card {
        transition: background 0.3s ease-in-out, backdrop-filter 0.3s ease-in-out;
    }
    
    /* Remove old fixed positioning styles */
    .color-tone-selector {
        position: relative;
        top: auto;
        right: auto;
        z-index: auto;
        background: transparent;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
        backdrop-filter: none;
    }
`;
document.head.appendChild(style);
