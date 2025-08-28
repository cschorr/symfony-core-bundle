# EasyAdmin Background Customization

## Overview
This setup adds a beautiful background image to your EasyAdmin dashboard with custom color tones and glass-morphism effects.

## Files Created/Modified

### 1. CSS Files
- `assets/styles/admin.css` - Custom styling for admin interface
- `assets/styles/admin-global.css` - Global CSS that applies to all admin pages

### 2. JavaScript Files
- `assets/admin.js` - JavaScript for background management and color tone switching

### 3. Template Files
- `templates/admin/dashboard.html.twig` - Custom dashboard template

### 4. Configuration Files
- `config/packages/easy_admin.yaml` - EasyAdmin configuration
- Updated `src/Controller/Admin/DashboardController.php` - Dashboard controller

## Features

### 1. Background Image
- Uses your `bg7.png` image from `public/images/bg/`
- Applied with a dark overlay for better readability
- Fixed attachment for a professional look

### 2. Color Tones
Available color tones:
- **Default**: Dark gray overlay
- **Blue**: Blue color tone
- **Green**: Green color tone  
- **Purple**: Purple color tone
- **Orange**: Orange color tone

### 3. Glass-Morphism Effects
- Semi-transparent panels with blur effects
- Smooth transitions and animations
- Professional modern appearance

## Usage

### Changing Color Tones
To enable the color tone selector, edit `assets/admin.js` and uncomment this line:
```javascript
// createColorToneSelector();
```

### Customizing Colors
To add new color tones, edit `assets/styles/admin.css` and add:
```css
.bg-tone-yourcolor {
    background-image: linear-gradient(rgba(R, G, B, 0.4), rgba(R, G, B, 0.4)), url('/images/bg/bg7.png');
}
```

### Changing Background Image
1. Replace `bg7.png` in `public/images/bg/` with your new image
2. Update the CSS files to reference the new image name

### Customizing Transparency
Adjust the `rgba()` values in the CSS files:
- `rgba(255, 255, 255, 0.95)` - 95% opacity (panels)
- `rgba(255, 255, 255, 0.9)` - 90% opacity (cards)
- `rgba(0, 0, 0, 0.3)` - 30% opacity (background overlay)

## Browser Support
- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE: Limited support (no backdrop-filter)

## Performance Notes
- Background image is loaded once and cached
- CSS transitions are GPU-accelerated
- Backdrop-filter may impact performance on older devices

## Troubleshooting

### Background not showing
1. Check that `bg7.png` exists in `public/images/bg/`
2. Clear your browser cache
3. Run `php bin/console cache:clear`

### CSS not loading
1. Check that assets are properly compiled
2. Verify the CSS files are imported in `app.js`
3. Check browser console for errors

### JavaScript not working
1. Ensure Bootstrap is loaded (required for dropdowns)
2. Check browser console for errors
3. Verify the script is loaded after DOM content

## Additional Customization

### Adding More Background Images
You can create different background images for different sections:
```css
.bg-section-users {
    background-image: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)), url('/images/bg/users-bg.png');
}
```

### Seasonal Themes
Create different themes for seasons/holidays:
```css
.bg-theme-christmas {
    background-image: linear-gradient(rgba(255, 0, 0, 0.2), rgba(0, 128, 0, 0.2)), url('/images/bg/christmas-bg.png');
}
```

### Dark Mode Support
Add dark mode variants:
```css
@media (prefers-color-scheme: dark) {
    .ea-main-wrapper {
        background: rgba(0, 0, 0, 0.8);
        color: white;
    }
}
```

Enjoy your beautiful new EasyAdmin dashboard! 🎨
