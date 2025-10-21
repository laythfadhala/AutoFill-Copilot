// AutoFill Copilot Options Script
// Manages extension settings and preferences

document.addEventListener('DOMContentLoaded', async () => {
    console.log('Options page loading...');
    
    // Default options
    const defaultOptions = {
        apiUrl: 'http://localhost',
        autoFillEnabled: true,
        showIndicators: true,
        confirmBeforeFill: false,
        fillPasswords: false,
        fillDelay: 100,
        secureMode: true,
        clearOnLogout: true,
        excludedDomains: []
    };

    // UI Elements
    const elements = {
        statusMessage: document.getElementById('status-message'),
        apiStatusIndicator: document.getElementById('api-status-indicator'),
        apiStatusText: document.getElementById('api-status-text'),
        apiUrl: document.getElementById('api-url'),
        autoFillEnabled: document.getElementById('auto-fill-enabled'),
        showIndicators: document.getElementById('show-indicators'),
        confirmBeforeFill: document.getElementById('confirm-before-fill'),
        fillPasswords: document.getElementById('fill-passwords'),
        fillDelay: document.getElementById('fill-delay'),
        secureMode: document.getElementById('secure-mode'),
        clearOnLogout: document.getElementById('clear-on-logout'),
        excludedDomains: document.getElementById('excluded-domains'),
        totalFormsFilled: document.getElementById('total-forms-filled'),
        totalTimeSaved: document.getElementById('total-time-saved'),
        successRate: document.getElementById('success-rate'),
        lastUsed: document.getElementById('last-used'),
        testConnection: document.getElementById('test-connection'),
        resetStats: document.getElementById('reset-stats'),
        exportData: document.getElementById('export-data'),
        importData: document.getElementById('import-data'),
        clearAllData: document.getElementById('clear-all-data'),
        saveOptions: document.getElementById('save-options'),
        resetOptions: document.getElementById('reset-options')
    };

    // Show status message
    function showStatus(message, type = 'success') {
        elements.statusMessage.textContent = message;
        elements.statusMessage.className = `status-message ${type}`;
        elements.statusMessage.classList.remove('hidden');
        
        setTimeout(() => {
            elements.statusMessage.classList.add('hidden');
        }, 3000);
    }

    // Update API connection status
    function updateApiStatus(online, message) {
        elements.apiStatusIndicator.className = `status-indicator ${online ? 'online' : 'offline'}`;
        elements.apiStatusText.textContent = message;
    }

    // Load options from storage
    async function loadOptions() {
        try {
            const result = await chrome.storage.sync.get(defaultOptions);
            const options = { ...defaultOptions, ...result };

            // Populate form fields
            elements.apiUrl.value = options.apiUrl;
            elements.autoFillEnabled.checked = options.autoFillEnabled;
            elements.showIndicators.checked = options.showIndicators;
            elements.confirmBeforeFill.checked = options.confirmBeforeFill;
            elements.fillPasswords.checked = options.fillPasswords;
            elements.fillDelay.value = options.fillDelay;
            elements.secureMode.checked = options.secureMode;
            elements.clearOnLogout.checked = options.clearOnLogout;
            elements.excludedDomains.value = (options.excludedDomains || []).join('\n');

            return options;
        } catch (error) {
            console.error('Error loading options:', error);
            showStatus('Failed to load options', 'error');
            return defaultOptions;
        }
    }

    // Save options to storage
    async function saveOptions() {
        try {
            const options = {
                apiUrl: elements.apiUrl.value.trim(),
                autoFillEnabled: elements.autoFillEnabled.checked,
                showIndicators: elements.showIndicators.checked,
                confirmBeforeFill: elements.confirmBeforeFill.checked,
                fillPasswords: elements.fillPasswords.checked,
                fillDelay: parseInt(elements.fillDelay.value) || 100,
                secureMode: elements.secureMode.checked,
                clearOnLogout: elements.clearOnLogout.checked,
                excludedDomains: elements.excludedDomains.value
                    .split('\n')
                    .map(domain => domain.trim())
                    .filter(domain => domain.length > 0)
            };

            await chrome.storage.sync.set(options);
            
            // Notify background script of options change
            chrome.runtime.sendMessage({
                action: 'optionsChanged',
                options: options
            });

            showStatus('Settings saved successfully!');
        } catch (error) {
            console.error('Error saving options:', error);
            showStatus('Failed to save settings', 'error');
        }
    }

    // Load and display usage statistics
    async function loadStats() {
        try {
            const stats = await chrome.storage.local.get([
                'formsFilled',
                'timeSaved',
                'totalAttempts',
                'successfulFills',
                'lastUsed'
            ]);

            elements.totalFormsFilled.textContent = stats.formsFilled || 0;
            elements.totalTimeSaved.textContent = stats.timeSaved || 0;
            
            const successRate = stats.totalAttempts > 0 
                ? Math.round((stats.successfulFills / stats.totalAttempts) * 100)
                : 0;
            elements.successRate.textContent = `${successRate}%`;
            
            if (stats.lastUsed) {
                const lastUsedDate = new Date(stats.lastUsed);
                elements.lastUsed.textContent = lastUsedDate.toLocaleDateString();
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    // Test API connection
    async function testConnection() {
        const apiUrl = elements.apiUrl.value.trim();
        if (!apiUrl) {
            showStatus('Please enter an API URL', 'error');
            return;
        }

        updateApiStatus(false, 'Testing connection...');
        
        try {
            // Check if chrome.runtime is available
            if (!chrome.runtime) {
                throw new Error('Chrome runtime not available');
            }

            const response = await chrome.runtime.sendMessage({
                action: 'testApiConnection',
                apiUrl: apiUrl
            });

            if (response && response.success) {
                updateApiStatus(true, 'Connection successful');
                showStatus('API connection test successful!');
            } else {
                updateApiStatus(false, response?.error || 'Connection failed');
                showStatus('API connection failed', 'error');
            }
        } catch (error) {
            console.error('Connection test error:', error);
            updateApiStatus(false, 'Connection error');
            showStatus('Failed to test connection - ' + error.message, 'error');
        }
    }

    // Reset statistics
    async function resetStats() {
        if (!confirm('Are you sure you want to reset all usage statistics?')) {
            return;
        }

        try {
            await chrome.storage.local.remove([
                'formsFilled',
                'timeSaved',
                'totalAttempts',
                'successfulFills',
                'lastUsed'
            ]);

            await loadStats();
            showStatus('Statistics reset successfully!');
        } catch (error) {
            console.error('Error resetting stats:', error);
            showStatus('Failed to reset statistics', 'error');
        }
    }

    // Export settings
    async function exportData() {
        try {
            const [syncData, localData] = await Promise.all([
                chrome.storage.sync.get(null),
                chrome.storage.local.get(null)
            ]);

            const exportData = {
                settings: syncData,
                stats: localData,
                exportDate: new Date().toISOString(),
                version: '1.0'
            };

            const blob = new Blob([JSON.stringify(exportData, null, 2)], {
                type: 'application/json'
            });

            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `autofill-copilot-export-${new Date().toISOString().split('T')[0]}.json`;
            a.click();
            
            URL.revokeObjectURL(url);
            showStatus('Settings exported successfully!');
        } catch (error) {
            console.error('Error exporting data:', error);
            showStatus('Failed to export settings', 'error');
        }
    }

    // Import settings
    function importData() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';
        
        input.onchange = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            try {
                const text = await file.text();
                const data = JSON.parse(text);

                if (!data.settings || !data.version) {
                    throw new Error('Invalid export file format');
                }

                // Import settings
                await chrome.storage.sync.set(data.settings);
                
                // Optionally import stats
                if (data.stats && confirm('Do you want to import usage statistics as well?')) {
                    await chrome.storage.local.set(data.stats);
                }

                // Reload the page to reflect changes
                location.reload();
            } catch (error) {
                console.error('Error importing data:', error);
                showStatus('Failed to import settings', 'error');
            }
        };
        
        input.click();
    }

    // Clear all data
    async function clearAllData() {
        if (!confirm('Are you sure you want to clear ALL extension data? This cannot be undone.')) {
            return;
        }

        try {
            await Promise.all([
                chrome.storage.sync.clear(),
                chrome.storage.local.clear()
            ]);

            location.reload();
        } catch (error) {
            console.error('Error clearing data:', error);
            showStatus('Failed to clear all data', 'error');
        }
    }

    // Reset to defaults
    async function resetToDefaults() {
        if (!confirm('Are you sure you want to reset all settings to default values?')) {
            return;
        }

        try {
            await chrome.storage.sync.clear();
            location.reload();
        } catch (error) {
            console.error('Error resetting options:', error);
            showStatus('Failed to reset settings', 'error');
        }
    }

    // Event listeners (with null checks)
    elements.testConnection?.addEventListener('click', testConnection);
    elements.saveOptions?.addEventListener('click', saveOptions);
    elements.resetOptions?.addEventListener('click', resetToDefaults);
    elements.resetStats?.addEventListener('click', resetStats);
    elements.exportData?.addEventListener('click', exportData);
    elements.importData?.addEventListener('click', importData);
    elements.clearAllData?.addEventListener('click', clearAllData);

    // Auto-save on input changes (with debounce)
    let saveTimeout;
    const autoSaveInputs = [
        elements.apiUrl,
        elements.autoFillEnabled,
        elements.showIndicators,
        elements.confirmBeforeFill,
        elements.fillPasswords,
        elements.fillDelay,
        elements.secureMode,
        elements.clearOnLogout,
        elements.excludedDomains
    ];

    autoSaveInputs.forEach(input => {
        if (input) {
            input.addEventListener('change', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    saveOptions().catch(error => {
                        console.error('Auto-save failed:', error);
                        showStatus('Auto-save failed', 'error');
                    });
                }, 1000); // Auto-save after 1 second
            });
        }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault();
            saveOptions();
        }
    });

    // Initialize
    try {
        await loadOptions();
        await loadStats();
        
        // Only test connection if API URL is set
        if (elements.apiUrl?.value?.trim()) {
            await testConnection();
        } else {
            updateApiStatus(false, 'No API URL configured');
        }
    } catch (error) {
        console.error('Initialization error:', error);
        showStatus('Failed to initialize options page', 'error');
    }
});