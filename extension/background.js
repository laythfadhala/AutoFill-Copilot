// AutoFill Copilot Background Script
const API_BASE_URL = "http://localhost/api"; //TODO: replace with production URL

// Handle extension messages
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    switch (request.action) {
        case "checkAuth":
            handleCheckAuth(sendResponse);
            break;
        case "openLoginPage":
            handleOpenLoginPage(sendResponse);
            break;
        case "logout":
            handleLogout(sendResponse);
            break;
        case "storeToken":
            handleStoreToken(request.token, sendResponse);
            break;
        case "detectForms":
            handleDetectForms(request, sendResponse);
            break;
        case "sendFormData":
            handleSendFormData(request.formData, sendResponse);
            break;
        case "fillSingleField":
            handleFillSingleField(request.fieldInfo, sendResponse, sender);
            break;
    }

    return true;
});

// Check authentication status and retrieve user profile info from API if authenticated
async function handleCheckAuth(sendResponse) {
    chrome.storage.local.get(["authToken"], async (data) => {
        try {
            if (!data.authToken) {
                console.log("No auth token found.");

                sendResponse({ success: true, authenticated: false });
                return;
            }

            const response = await fetch(`${API_BASE_URL}/auth/profile`, {
                headers: {
                    Authorization: `Bearer ${data.authToken}`,
                    Accept: "application/json",
                },
            });

            if (response.ok) {
                const apiResponse = await response.json();
                sendResponse({
                    success: true,
                    authenticated: true,
                    user: apiResponse.data.user,
                    stats: { formsFilled: 0, timeSaved: 0 },
                });
            } else {
                chrome.storage.local.clear(() => {
                    sendResponse({ success: true, authenticated: false });
                });
            }
        } catch (error) {
            console.error("Auth check error:", error);
            sendResponse({
                success: false,
                error: "Cannot connect to server",
            });
        }
    });
}

function handleOpenLoginPage(sendResponse) {
    const loginUrl = API_BASE_URL.replace("/api", "") + "/signin";
    chrome.tabs.create({ url: loginUrl });
    sendResponse({ success: true });
}

function handleStoreToken(token, sendResponse) {
    chrome.storage.local.set({ authToken: token }, () => {
        sendResponse({ success: true });
    });
}

function handleLogout(sendResponse) {
    chrome.storage.local.remove(["authToken"], () => {
        sendResponse({ success: true });
    });
}

async function handleDetectForms(request, sendResponse) {
    try {
        // Get the active tab
        const [tab] = await chrome.tabs.query({
            active: true,
            currentWindow: true,
        });

        if (!tab) {
            sendResponse({ success: false, error: "No active tab found" });
            return;
        }

        // Execute the content scripts to detect forms
        const results = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            files: ["content/form-detection.js", "content/form-filling.js"],
        });

        // The content script should have been injected already, so we can send a message
        const response = await chrome.tabs.sendMessage(tab.id, {
            action: "detectTabForms",
        });

        sendResponse(response);
    } catch (error) {
        console.error("Form detection error:", error);
        sendResponse({ success: false, error: error.message });
    }
}

async function handleSendFormData(formData, sendResponse) {
    try {
        // Get auth token
        const data = await chrome.storage.local.get(["authToken"]);

        if (!data.authToken) {
            sendResponse({ success: false, error: "Not authenticated" });
            return;
        }

        // Send form data to backend for filling
        const response = await fetch(`${API_BASE_URL}/forms/fill`, {
            method: "POST",
            headers: {
                Authorization: `Bearer ${data.authToken}`,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify(formData),
        });

        if (response.ok) {
            const result = await response.json();

            // If we got filled data, send it to the content script to fill the forms
            if (result.success && result.data.filled_data) {
                // Get the active tab to fill the forms
                const [tab] = await chrome.tabs.query({
                    active: true,
                    currentWindow: true,
                });

                if (tab) {
                    // Send the filled data to the content script
                    const fillResponse = await chrome.tabs.sendMessage(tab.id, {
                        action: "fillForms",
                        filledData: result.data.filled_data,
                        forms: formData.forms,
                    });

                    if (fillResponse.success) {
                        sendResponse({
                            success: true,
                            data: result,
                            message: "Forms filled successfully",
                        });
                    } else {
                        sendResponse({
                            success: false,
                            error:
                                "Failed to fill forms on page: " +
                                fillResponse.error,
                        });
                    }
                } else {
                    sendResponse({
                        success: false,
                        error: "No active tab found to fill forms",
                    });
                }
            } else {
                sendResponse({ success: true, data: result });
            }
        } else {
            const error = await response.text();
            sendResponse({
                success: false,
                error: `API error: ${response.status}`,
            });
        }
    } catch (error) {
        console.error("Send form data error:", error);
        sendResponse({ success: false, error: error.message });
    }
}

async function handleFillSingleField(fieldInfo, sendResponse, sender) {
    try {
        // Get auth token
        const data = await chrome.storage.local.get(["authToken"]);

        if (!data.authToken) {
            sendResponse({ success: false, error: "Not authenticated" });
            return;
        }

        const tab = sender.tab;
        // Create minimal formData for the single field
        const formData = {
            url: tab.url,
            title: tab.title,
            forms: [
                {
                    id: "singleField",
                    action: tab.url,
                    method: "GET",
                    fields: [fieldInfo],
                },
            ],
            timestamp: new Date().toISOString(),
        };

        // Send to API
        const response = await fetch(`${API_BASE_URL}/forms/fill`, {
            method: "POST",
            headers: {
                Authorization: `Bearer ${data.authToken}`,
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify(formData),
        });

        if (response.ok) {
            const result = await response.json();
            if (
                result.success &&
                result.data.filled_data &&
                result.data.filled_data[fieldInfo.name]
            ) {
                sendResponse({
                    success: true,
                    filledValue: result.data.filled_data[fieldInfo.name],
                });
            } else {
                sendResponse({
                    success: false,
                    error: "No filled data for this field",
                });
            }
        } else {
            sendResponse({
                success: false,
                error: `API error: ${response.status}`,
            });
        }
    } catch (error) {
        console.error("Fill single field error:", error);
        sendResponse({ success: false, error: error.message });
    }
}

// Actions to perform on extension startup
chrome.runtime.onStartup.addListener(() => {
    //
});

// Actions to perform on extension installation
chrome.runtime.onInstalled.addListener((details) => {
    // Create context menu for filling individual fields
    chrome.contextMenus.create({
        title: "Fill this field",
        contexts: ["all"],
        id: "fillField",
    });
});

// Handle context menu clicks
chrome.contextMenus.onClicked.addListener((info, tab) => {
    if (info.menuItemId === "fillField") {
        chrome.tabs.sendMessage(tab.id, { action: "fillCurrentField" });
    }
});
