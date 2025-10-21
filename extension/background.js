// AutoFill Copilot Background Script
const API_BASE_URL = 'http://localhost/api'; //TODO: replace with production URL

// Handle extension messages
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  console.log('Background received message:', request.action);
  
  switch (request.action) {
    case 'checkAuth':
      handleCheckAuth(sendResponse);
      break;
    case 'openLoginPage':
      handleOpenLoginPage(sendResponse);
      break;
    case 'logout':
      handleLogout(sendResponse);
      break;
    case 'fillForm':
      handleFillForm(request.tabId, sendResponse);
      break;
    case 'clearForm':
      handleClearForm(request.tabId, sendResponse);
      break;
    case 'testApiConnection':
      handleTestApiConnection(request.apiUrl, sendResponse);
      break;
    case 'optionsChanged':
      handleOptionsChanged(request.options, sendResponse);
      break;
    case 'updateFormCount':
      handleUpdateFormCount(request.count, request.url, sender.tab.id, sendResponse);
      break;
    case 'storeToken':
      handleStoreToken(request.token, sendResponse);
      break;
  }
  
  return true;
});

async function handleCheckAuth(sendResponse) {
  chrome.storage.local.get(['authToken'], async (data) => {
    try {
      if (!data.authToken) {
        sendResponse({ success: true, authenticated: false });
        return;
      }

      const response = await fetch(`${API_BASE_URL}/auth/profile`, {
        headers: {
          'Authorization': `Bearer ${data.authToken}`,
          'Accept': 'application/json'
        }
      });

      if (response.ok) {
        const apiResponse = await response.json();
        sendResponse({ 
          success: true, 
          authenticated: true, 
          user: apiResponse.data.user,
          stats: { formsFilled: 0, timeSaved: 0 }
        });
      } else {
        chrome.storage.local.clear(() => {
          sendResponse({ success: true, authenticated: false });
        });
      }
    } catch (error) {
      console.error('Auth check error:', error);
      sendResponse({ 
        success: false, 
        error: 'Cannot connect to server' 
      });
    }
  });
}

function handleOpenLoginPage(sendResponse) {
  const loginUrl = API_BASE_URL.replace('/api', '') + '/signin';
  chrome.tabs.create({ url: loginUrl });
  sendResponse({ success: true });
}

function handleStoreToken(token, sendResponse) {
  console.log('Storing token:', token);
  chrome.storage.local.set({ authToken: token }, () => {
    console.log('Token stored');
    sendResponse({ success: true });
  });
}

function handleLogout(sendResponse) {
  console.log('Logging out - clearing token');
  chrome.storage.local.remove(['authToken'], () => {
    console.log('Token cleared');
    sendResponse({ success: true });
  });
}

async function handleFillForm(tabId, sendResponse) {
}

async function handleClearForm(tabId, sendResponse) {
  try {
    await chrome.scripting.executeScript({
      target: { tabId: tabId },
      files: ['content/autofill-engine.js']
    });

    await chrome.tabs.sendMessage(tabId, {
      action: 'CLEAR_FORM'
    });

    sendResponse({ success: true });
  } catch (error) {
    console.error('Clear form error:', error);
    sendResponse({ success: false, error: 'Failed to clear form' });
  }
}

async function handleTestApiConnection(apiUrl, sendResponse) {
  try {
    if (!apiUrl) {
      sendResponse({ success: false, error: 'API URL is required' });
      return;
    }

    // Remove trailing slash if present
    const baseUrl = apiUrl.replace(/\/$/, '');
    
    // Test with a simple health check or status endpoint
    const response = await fetch(`${baseUrl}/api/health`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (response.ok) {
      sendResponse({ success: true });
    } else {
      sendResponse({ 
        success: false, 
        error: `Server returned status ${response.status}` 
      });
    }
  } catch (error) {
    console.error('Test API connection error:', error);
    sendResponse({ 
      success: false, 
      error: error.message || 'Connection failed' 
    });
  }
}

function handleOptionsChanged(options, sendResponse) {
  try {
    // Handle any necessary updates when options change
    console.log('Options updated:', options);
    
    // Update API base URL if it changed
    if (options.apiUrl) {
      // You could store this globally or update other parts of the extension
      console.log('API URL updated to:', options.apiUrl);
    }
    
    sendResponse({ success: true });
  } catch (error) {
    console.error('Options change error:', error);
    sendResponse({ success: false, error: error.message });
  }
}

// Form count tracking
const tabFormCounts = new Map();

// Direct form counting function
async function countFormsInTab(tabId, url) {
  try {
    console.log(`Counting forms in tab ${tabId}: ${url}`);
    
    const results = await chrome.scripting.executeScript({
      target: { tabId: tabId },
      func: () => {
        // Count forms directly in the page
        const forms = document.querySelectorAll('form');
        const inputs = document.querySelectorAll('input:not(form input), textarea:not(form textarea)');
        
        let formCount = forms.length;
        
        // Group standalone inputs
        if (inputs.length > 0) {
          const containers = new Set();
          inputs.forEach(input => {
            const container = input.closest('div[class*="form"], div[class*="login"], div[class*="signup"], div[class*="contact"], section, article') 
                           || input.closest('div');
            if (container && container !== document.body) {
              containers.add(container);
            }
          });
          formCount += containers.size;
        }
        
        const finalCount = Math.max(formCount, inputs.length > 0 ? 1 : 0);
        console.log(`AutoFill Copilot: Found ${finalCount} forms (${forms.length} explicit forms, ${inputs.length} standalone inputs)`);
        return finalCount;
      }
    });
    
    if (results && results[0] && typeof results[0].result === 'number') {
      const count = results[0].result;
      console.log(`Form count result for tab ${tabId}: ${count}`);
      
      // Store and update badge
      tabFormCounts.set(tabId, { count, url, timestamp: Date.now() });
      updateBadgeForTab(tabId, count);
    } else {
      console.log(`No valid form count result for tab ${tabId}`);
      updateBadgeForTab(tabId, 0);
    }
  } catch (error) {
    console.log(`Error counting forms in tab ${tabId}:`, error);
    updateBadgeForTab(tabId, 0);
  }
}

function handleUpdateFormCount(count, url, tabId, sendResponse) {
  try {
    console.log(`Form count update: ${count} forms found on tab ${tabId}`);
    
    // Store the count for this tab
    tabFormCounts.set(tabId, { count, url, timestamp: Date.now() });
    
    // Update badge for this specific tab
    updateBadgeForTab(tabId, count);
    
    if (sendResponse) {
      sendResponse({ success: true });
    }
  } catch (error) {
    console.error('Form count update error:', error);
    if (sendResponse) {
      sendResponse({ success: false, error: error.message });
    }
  }
}

function updateBadgeForTab(tabId, count) {
  const badgeText = count > 0 ? count.toString() : '';
  const badgeColor = count > 0 ? '#4CAF50' : '#757575';
  
  chrome.action.setBadgeText({ text: badgeText, tabId: tabId });
  chrome.action.setBadgeBackgroundColor({ color: badgeColor, tabId: tabId });
  
  console.log(`Badge updated for tab ${tabId}: ${badgeText || 'empty'} (${count} forms)`);
}

// Tab management - update badge when switching tabs
chrome.tabs.onActivated.addListener(async (activeInfo) => {
  console.log(`Tab activated: ${activeInfo.tabId}`);
  
  const tabData = tabFormCounts.get(activeInfo.tabId);
  if (tabData) {
    console.log(`Using cached form count for tab ${activeInfo.tabId}: ${tabData.count}`);
    updateBadgeForTab(activeInfo.tabId, tabData.count);
  } else {
    // No cached data, get tab info and count forms
    try {
      const tab = await chrome.tabs.get(activeInfo.tabId);
      if (tab.url && !tab.url.startsWith('chrome://') && !tab.url.startsWith('chrome-extension://')) {
        console.log(`No cached data for tab ${activeInfo.tabId}, counting forms...`);
        countFormsInTab(activeInfo.tabId, tab.url);
      } else {
        console.log(`Skipping form count for system tab: ${tab.url}`);
        updateBadgeForTab(activeInfo.tabId, 0);
      }
    } catch (error) {
      console.log(`Error getting tab info for ${activeInfo.tabId}:`, error);
      updateBadgeForTab(activeInfo.tabId, 0);
    }
  }
});

// Clean up form counts for closed tabs
chrome.tabs.onRemoved.addListener((tabId) => {
  tabFormCounts.delete(tabId);
});

// Handle tab updates (URL changes, page reloads)
chrome.tabs.onUpdated.addListener((tabId, changeInfo, tab) => {
  if (changeInfo.status === 'loading') {
    // Clear badge when page starts loading
    updateBadgeForTab(tabId, 0);
    tabFormCounts.delete(tabId);
    console.log(`Tab ${tabId} loading, cleared badge`);
  } else if (changeInfo.status === 'complete') {
    console.log(`Tab ${tabId} completed loading: ${tab.url}`);
    
    // Skip chrome:// and extension pages
    if (tab.url && (tab.url.startsWith('chrome://') || tab.url.startsWith('chrome-extension://'))) {
      updateBadgeForTab(tabId, 0);
      return;
    }
    
    // Count forms directly using scripting API
    setTimeout(() => {
      countFormsInTab(tabId, tab.url);
    }, 500);
  }
});

// Initialize badge styling on extension startup
chrome.runtime.onStartup.addListener(() => {
  console.log('Extension startup - initializing badge');
  chrome.action.setBadgeBackgroundColor({ color: '#4CAF50' });
});

chrome.runtime.onInstalled.addListener((details) => {
  console.log('Extension installed/updated - initializing badge');
  chrome.action.setBadgeBackgroundColor({ color: '#4CAF50' });
  
  // Clear any existing badge data on fresh install
  if (details.reason === 'install') {
    tabFormCounts.clear();
  }
});


