// AutoFill Copilot Background Script
const API_BASE_URL = 'http://localhost/api'; //TODO: replace with production URL

// Handle extension messages
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
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
    case 'optionsChanged':
      handleOptionsChanged(request.options, sendResponse);
      break;
    case 'storeToken':
      handleStoreToken(request.token, sendResponse);
      break;
  }
  
  return true;
});

// Check authentication status and retrieve user profile info from API if authenticated
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
  chrome.storage.local.set({ authToken: token }, () => {
    sendResponse({ success: true });
  });
}

function handleLogout(sendResponse) {
  chrome.storage.local.remove(['authToken'], () => {
    sendResponse({ success: true });
  });
}

// Handle options changes from options page
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

    // Form-counting, badge and related tab listeners removed along with autofill feature

// Initialize badge styling on extension startup
chrome.runtime.onStartup.addListener(() => {
  chrome.action.setBadgeBackgroundColor({ color: '#4CAF50' });
});

chrome.runtime.onInstalled.addListener((details) => {
  chrome.action.setBadgeBackgroundColor({ color: '#4CAF50' });
  
  // Clear any existing badge data on fresh install
  if (details.reason === 'install') {
    // no form counts to clear
  }
});


