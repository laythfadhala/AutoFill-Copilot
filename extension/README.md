# AutoFill Copilot Browser Extension

Intelligent form auto-fill browser extension powered by AI that securely detects and fills forms on any website.

## Features

-   **Intelligent Form Detection**: Automatically detects and analyzes forms on web pages
-   **AI-Powered Field Mapping**: Uses machine learning to accurately map user data to form fields
-   **Visual Indicators**: Shows fillable fields with confidence indicators
-   **Secure Authentication**: Connects to your AutoFill service backend securely
-   **Customizable Settings**: Configure filling behavior, security options, and excluded domains
-   **Usage Statistics**: Track forms filled and time saved
-   **Cross-Platform**: Works on Chrome, Firefox, and other Chromium-based browsers

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

## Setup

1. **Start the Backend Service**: Ensure your AutoFill microservices backend is running at `https://localhost`

2. **Configure Extension**:

    - Click the AutoFill Copilot icon in your browser toolbar
    - Click "Login to AutoFill" to authenticate
    - Optionally, click "Extension Options" to customize settings

3. **Use the Extension**:
    - Navigate to any website with forms
    - The extension will automatically detect fillable forms
    - Click the AutoFill Copilot icon and select "Fill Current Form"
    - Or use the visual indicators on form fields

## Extension Components

### Core Files

-   `manifest.json` - Extension configuration and permissions
-   `background.js` - Service worker handling API communication and extension lifecycle
-   `popup.html/js` - Extension popup interface for user interactions
-   `options.html/js` - Options page for settings and configuration

### Content Scripts

-   `content/sync-auth.js` - Sync authentication between web app and extension
-   `content/styles.css` - Styles for UI elements

Note: In-page autofill and form-detection features have been removed in this branch.

### Resources

-   `icons/` - Extension icons in multiple sizes (16x16, 32x32, 48x48, 128x128)

## Permissions Explained

The extension requires these permissions:

-   **storage** - Store user preferences and authentication tokens
-   **activeTab** - Access the current tab to detect and fill forms
-   **scripting** - Inject content scripts for form detection
-   **tabs** - Manage tab interactions and messaging
-   **unlimitedStorage** - Store user data and form mappings
-   **host_permissions** - Connect to your backend API and access web pages

## Configuration Options

### Form Filling Preferences

-   **Auto-fill enabled** - Enable/disable automatic form filling
-   **Show indicators** - Display visual indicators on fillable fields
-   **Confirm before fill** - Ask for confirmation before filling forms
-   **Fill passwords** - Include password fields in auto-fill (less secure)
-   **Fill delay** - Delay between filling fields (default: 100ms)

### Security Settings

-   **Secure mode** - Only work on HTTPS sites
-   **Clear on logout** - Clear cached data when logging out
-   **Excluded domains** - Domains to never auto-fill (e.g., banking sites)

### API Connection

-   **API Base URL** - Backend service URL (default: https://localhost)
-   **Test Connection** - Verify connection to backend service

## Usage

### Basic Usage

1. Navigate to a website with forms
2. Click the AutoFill Copilot browser icon
3. Click "Fill Current Form" to automatically fill the detected form
4. Or click "Clear Form" to clear all form fields

### Visual Indicators

-   Forms with detected fields show colored indicators
-   Different colors represent different field types (email, name, phone, etc.)
-   Click indicators to fill individual fields

### Keyboard Shortcuts

-   **Popup**: Press `Enter` to login or fill form, `Esc` to close
-   **Options**: Press `Ctrl+S` (or `Cmd+S` on Mac) to save settings

## Troubleshooting

### Extension Not Working

1. Check that the backend service is running at the configured URL
2. Verify you're logged in by clicking the extension icon
3. Check the browser console for error messages
4. Try refreshing the page and testing again

### Forms Not Detected

1. Ensure the website uses standard HTML form elements
2. Check if the domain is in your excluded domains list
3. Some dynamic forms may require page refresh after loading
4. Complex single-page applications may need manual trigger

### API Connection Issues

1. Verify the backend service is running and accessible
2. Check the API URL in extension options
3. Ensure no firewall blocking localhost connections
4. Test connection using the "Test Connection" button in options

### Performance Issues

1. Increase the fill delay in options for slow websites
2. Disable auto-fill on problematic sites
3. Clear extension data if experiencing memory issues

## Security Notes

-   **Data Privacy**: User data is only sent to your configured backend service
-   **Local Storage**: Authentication tokens are stored locally and encrypted
-   **HTTPS Only**: Enable secure mode to only work on HTTPS sites
-   **Domain Exclusion**: Add sensitive sites (banking, etc.) to excluded domains
-   **Password Fields**: Disable password auto-fill for enhanced security

## Development

### File Structure

```
extension/
├── manifest.json          # Extension configuration
├── background.js          # Service worker
├── popup.html/js         # Extension popup
├── options.html/js       # Options page
├── content/              # Content scripts
│   ├── sync-auth.js      # Authentication sync (kept)
│   └── styles.css        # UI styles
└── icons/               # Extension icons
    ├── icon-16.png
    ├── icon-32.png
    ├── icon-48.png
    └── icon-128.png
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

-   **v1.0.0** - Initial release with core form detection and filling functionality
