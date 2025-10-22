document.addEventListener('DOMContentLoaded', async () => {
    
    const states = {
        loading: document.getElementById('loading-state'),
        error: document.getElementById('error-state'),
        loggedOut: document.getElementById('logged-out-state'),
        loggedIn: document.getElementById('logged-in-state')
    };

    const elements = {
        errorMessage: document.getElementById('error-message'),
        userEmail: document.getElementById('user-email'),
        loginBtn: document.getElementById('login-btn'),
        logoutBtn: document.getElementById('logout-btn'),
        retryBtn: document.getElementById('retry-btn')
    };

    function showState(stateName) {
        Object.values(states).forEach(el => el.classList.add('hidden'));
        if (states[stateName]) {
            states[stateName].classList.remove('hidden');
        }
    }

    function showError(message) {
        elements.errorMessage.textContent = message;
        showState('error');
    }

    // Initialize the first state
    showState('loading');
    const response = await chrome.runtime.sendMessage({ action: 'checkAuth' });

    if (response.success && response.authenticated) {
        elements.userEmail.textContent = response.user.email;
        showState('loggedIn');
    } else if (response.success) {
        showState('loggedOut');
    } else {
        showError(response.error);
    }

    // Listen for auth changes and update UI accordingly when token changes or removed from storage
    chrome.storage.onChanged.addListener((changes, namespace) => {
        if (namespace === 'local' && changes.authToken) {
            // Recheck auth when token changes
            chrome.runtime.sendMessage({ action: 'checkAuth' }).then(response => {
                if (response.success && response.authenticated) {
                    elements.userEmail.textContent = response.user.email;
                    showState('loggedIn');
                } else {
                    showState('loggedOut');
                }
            });
        }
    });

    // Login button - now opens web login page
    elements.loginBtn.addEventListener('click', async () => {
        const response = await chrome.runtime.sendMessage({ action: 'openLoginPage' });
        if (response.success) {
            // Wait a bit for user to login, then check auth
            setTimeout(async () => {
                const authResponse = await chrome.runtime.sendMessage({ action: 'checkAuth' });
                if (authResponse.success && authResponse.authenticated) {
                    elements.userEmail.textContent = authResponse.user.email;
                    showState('loggedIn');
                } else {
                    showState('loggedOut');
                }
            }, 5000); // 5 seconds
        }
    });

    // Logout
    elements.logoutBtn.addEventListener('click', async () => {
        showState('loading');
        const logoutResponse = await chrome.runtime.sendMessage({ action: 'logout' });
        showState('loggedOut');
    });

    // Retry button
    elements.retryBtn?.addEventListener('click', async () => {
        showState('loading');
        const response = await chrome.runtime.sendMessage({ action: 'checkAuth' });

        if (response.success && response.authenticated) {
            elements.userEmail.textContent = response.user.email || 'User';
            showState('loggedIn');
        } else if (response.success) {
            showState('loggedOut');
        } else {
            showError(response.error);
        }
    });

});
