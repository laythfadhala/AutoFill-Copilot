// Background service worker for Manifest V3

chrome.runtime.onInstalled.addListener(() => {
  console.log('Test Extension installed');
  // Initialize click count
  chrome.storage.local.set({ clickCount: 0 });
});

// Example action click log (popup handles UI)
chrome.action.onClicked.addListener(() => {
  console.log('Extension action clicked');
});

