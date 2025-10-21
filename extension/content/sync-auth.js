// Sync auth between web app and extension

const allowedOrigins = [
  'http://localhost', //TODO: remove before production
  'https://autofillcopilot.com',
  'https://www.autofillcopilot.com'
];

window.addEventListener('message', (event) => {
  console.log('Content script received message:', event.data, 'from', event.origin);
  // Only accept messages from localhost
  if (!allowedOrigins.includes(event.origin)) {
    console.log('Ignoring message from', event.origin);
    return;
  }

  if (event.data.type === 'loginSuccess' && event.data.token) {
    console.log('Sending token to background:', event.data.token);
    chrome.runtime.sendMessage({ action: 'storeToken', token: event.data.token });
  } else if (event.data.type === 'logout') {
    console.log('Sending logout to background');
    chrome.runtime.sendMessage({ action: 'logout' });
  }
});