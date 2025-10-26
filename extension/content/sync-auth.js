// Sync auth between web app and extension
(function () {
    const allowedOrigins = [
        "http://localhost", //TODO: remove before production
        "https://autofillcopilot.com",
        "https://www.autofillcopilot.com",
    ];

    window.addEventListener("message", (event) => {
        // Only accept messages from localhost
        if (!allowedOrigins.includes(event.origin)) {
            return;
        }

        if (event.data.type === "loginSuccess" && event.data.token) {
            chrome.runtime.sendMessage({
                action: "storeToken",
                token: event.data.token,
            });
        } else if (event.data.type === "logout") {
            chrome.runtime.sendMessage({ action: "logout" });
        }
    });
})();
