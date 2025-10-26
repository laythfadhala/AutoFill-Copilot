# AutoFill Copilot Browser Extension

Intelligent form auto-fill browser extension powered by AI that securely detects and fills forms on any website.

## Features

-   **Intelligent Form Detection**: Automatically detects and analyzes forms on web pages, excluding search forms and utility inputs
-   **AI-Powered Field Mapping**: Uses machine learning to accurately map user data to form fields
-   **Visual Indicators**: Shows fillable fields with floating button interface
-   **Secure Authentication**: Connects to your AutoFill service backend securely
-   **Cross-Browser Support**: Works on Chrome, Firefox, and Safari
-   **Smart Form Exclusion**: Automatically excludes search forms, single-field utilities, and other non-fillable forms
-   **Form Clearing**: Clear all form fields with one click
-   **Real-time Form Detection**: Updates form list as you navigate pages

## Installation

### Chrome/Chromium Browsers

1. Open Chrome and navigate to `chrome://extensions/`
2. Enable "Developer mode" in the top right corner
3. Click "Load unpacked" and select the `extension` folder
4. The AutoFill Copilot extension should now appear in your browser

### Firefox

1. Open Firefox and navigate to `about:debugging`
2. Click "This Firefox" in the sidebar
3. Click "Load Temporary Add-on..."
4. Select the `manifest.json` file in the extension folder

### Safari

1. Open Safari and enable "Develop" menu in Safari preferences
2. Open Safari Extension preferences
3. Enable "Developer Mode"
4. Click "+" to add extension and select the extension folder

## Setup

1. **Start the Backend Service**: Ensure your AutoFill microservices backend is running at `http://localhost`

2. **Configure Extension**:

    - Click the AutoFill Copilot icon in your browser toolbar
    - Click "Login to AutoFill" to authenticate
    - The extension will automatically detect forms on pages you visit

3. **Use the Extension**:
    - Navigate to any website with forms
    - The extension automatically detects fillable forms (excluding search forms)
    - Click the AutoFill Copilot icon and select "Fill Current Form"
    - Use the floating button on form fields for individual filling
    - Use "Clear Form" to clear all form fields

## Development

### Prerequisites

-   Node.js and npm

### Setup

```bash
npm install
```

### Build

Build for specific browsers:

```bash
# Build for Chrome
npm run build:chrome

# Build for Firefox
npm run build:firefox

# Build for Safari
npm run build:safari

# Build for all browsers
npm run build  # Builds for the last selected manifest
```

### Watch Mode (for development)

```bash
npm run watch
```

### Cross-Browser Compatibility

This extension uses `webextension-polyfill` to ensure compatibility across different browsers (Chrome, Firefox, Safari).

The build process creates browser-specific manifests and bundles all scripts in the `dist/` directory.

## Extension Components

### Core Files

-   `manifest.json` - Extension configuration and permissions (auto-switched per browser)
-   `background.js` - Service worker handling API communication and extension lifecycle
-   `popup.html/js` - Extension popup interface for user interactions
-   `clear-form.js` - Form clearing functionality

### Content Scripts

-   `content/sync-auth.js` - Sync authentication between web app and extension
-   `content/form-detection.js` - Intelligent form detection with search form exclusion
-   `content/form-filling.js` - AI-powered form filling logic
-   `content/field-filling.js` - Individual field filling utilities
-   `content/forms-observer.js` - Observes DOM changes for dynamic forms
-   `content/floating-button.js` - Floating UI button for form interactions
-   `content/clear-forms.js` - Form clearing implementation
-   `content/utils.js` - Shared utilities and form exclusion logic
-   `content/styles.css` - Styles for UI elements

### Browser-Specific Manifests

-   `manifest-chrome.json` - Chrome/Chromium configuration (Manifest V3)
-   `manifest-firefox.json` - Firefox configuration (Manifest V3)
-   `manifest-safari.json` - Safari configuration (Manifest V2)

### Resources

-   `icons/` - Extension icons in multiple sizes (16x16, 32x32, 48x48, 128x128)

## Permissions Explained

The extension requires these permissions:

-   **storage** - Store user preferences and authentication tokens
-   **activeTab** - Access the current tab to detect and fill forms
-   **tabs** - Manage tab interactions and messaging
-   **scripting** (Chrome) / **activeTab** (Firefox/Safari) - Inject content scripts for form detection
-   **host_permissions** - Connect to backend API (`http://localhost/*`) and access web pages (`https://*/*`)

### Browser-Specific Differences

-   **Chrome**: Uses Manifest V3 with service worker and `scripting` permission
-   **Firefox**: Uses Manifest V3 with background scripts
-   **Safari**: Uses Manifest V2 with background scripts and `browser_action`

## Configuration Options

Currently, the extension works with default settings optimized for security and performance:

### Smart Form Detection

-   **Automatic Exclusion**: Search forms, single-field utilities, and hidden fields are automatically excluded
-   **Multi-Field Focus**: Only forms with 2+ detectable fields are processed
-   **Secure Field Handling**: Password and sensitive fields are handled appropriately

### Security Settings

-   **Local API**: Connects only to localhost backend for security
-   **No External Data**: User data never leaves your local environment
-   **Form Validation**: Intelligent detection prevents filling inappropriate forms

## Usage

### Basic Usage

1. Navigate to a website with forms
2. The extension automatically detects fillable forms (excluding search forms)
3. Click the AutoFill Copilot browser icon
4. Click "Fill Current Form" to automatically fill the detected form
5. Or click "Clear Form" to clear all form fields

### Visual Indicators

-   Forms with detected fields show a floating button interface
-   Different field types are intelligently mapped (email, name, phone, address, etc.)
-   Click the floating button to fill individual fields or entire forms

### Smart Form Detection

The extension automatically excludes:

-   Search forms (single text input + submit)
-   Single-field utility forms
-   Forms with excluded field names (CSRF tokens, etc.)
-   Hidden, submit, and button fields

### Cross-Browser Features

-   **Chrome**: Full Manifest V3 support with service worker
-   **Firefox**: Manifest V3 compatibility with background scripts
-   **Safari**: Manifest V2 support with browser action

## Troubleshooting

### Extension Not Working

1. Check that the backend service is running at `http://localhost`
2. Verify you're logged in by clicking the extension icon
3. Check the browser console for error messages (`F12` → Console)
4. Try refreshing the page and testing again

### Forms Not Detected

1. Ensure the website uses standard HTML form elements
2. Check if the form is a search form (automatically excluded)
3. Some dynamic forms may require page refresh after loading
4. Complex single-page applications may need manual trigger
5. Verify the form has more than one detectable field

### Clear Form Button Not Working

1. Ensure the page has detectable forms (not excluded as search forms)
2. Check browser console for JavaScript errors
3. Try refreshing the page
4. Verify the extension has proper permissions

### API Connection Issues

1. Verify the backend service is running and accessible
2. Check that `http://localhost` is accessible in your browser
3. Ensure no firewall blocking localhost connections
4. Test connection by checking if you can access `http://localhost` directly

### Cross-Browser Issues

1. **Chrome**: Ensure Manifest V3 is properly loaded
2. **Firefox**: Check that background scripts are enabled
3. **Safari**: Verify extension is enabled in Safari preferences
4. Try rebuilding for your specific browser: `npm run build:chrome/firefox/safari`

### Performance Issues

1. The extension automatically excludes single-field forms for better performance
2. Check browser console for performance warnings
3. Disable on problematic sites if needed

## Security Notes

-   **Data Privacy**: All user data stays on your local machine and backend
-   **Local Storage**: Authentication tokens are stored locally in browser storage
-   **Form Intelligence**: Smart exclusion prevents filling search forms and utilities
-   **No External APIs**: Extension only communicates with your local backend
-   **Field Validation**: Sensitive fields (passwords, hidden fields) are handled securely
-   **Cross-Browser Security**: Same security model across Chrome, Firefox, and Safari

## Development

### File Structure

```
extension/
├── manifest-*.json        # Browser-specific manifests
│   ├── manifest-chrome.json
│   ├── manifest-firefox.json
│   └── manifest-safari.json
├── background.js          # Service worker & API communication
├── popup.html/js         # Extension popup interface
├── clear-form.js         # Form clearing functionality
├── webpack.config.js     # Build configuration
├── content/              # Content scripts
│   ├── form-detection.js # Form detection logic
│   ├── form-filling.js   # AI-powered form filling
│   ├── field-filling.js  # Field-level filling utilities
│   ├── forms-observer.js # DOM change observation
│   ├── floating-button.js # Floating UI button
│   ├── clear-forms.js    # Form clearing implementation
│   ├── sync-auth.js      # Authentication sync
│   ├── utils.js          # Shared utilities
│   ├── styles.css        # UI styles
│   └── animation-utils.js # Animation helpers
├── icons/               # Extension icons
│   ├── icon-16.png
│   ├── icon-32.png
│   ├── icon-48.png
│   └── icon-128.png
├── dist/                # Built files (generated)
└── node_modules/        # Dependencies
```

### Testing

1. Load the extension in developer mode
2. Open browser developer tools to monitor console logs
3. Test on various websites with different form types
4. Verify API communication in Network tab
5. Test options page functionality

## Support

For issues and support:

1. Check the browser console for error messages
2. Verify backend service connectivity
3. Review extension options and settings
4. Test with default settings to isolate issues

## Version History

-   **v1.0.2** - Cross-browser support (Chrome, Firefox, Safari), smart form exclusion, floating button interface, form clearing functionality
-   **v1.0.1** - Enhanced form detection with search form exclusion, improved error handling
-   **v1.0.0** - Initial release with core form detection and filling functionality
