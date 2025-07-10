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
        } else {
            console.log('No EasyAdmin sidebar found - theme picker not added');
        }
    }
    
    // Debug: Add manual color tone testing
    console.log('Admin.js loaded - Background customization active');
    console.log('Current body classes:', document.body.className);
    console.log('Current pathname:', window.location.pathname);
});
