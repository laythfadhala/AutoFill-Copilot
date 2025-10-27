// AutoFill Copilot Popup Script
import browser from "webextension-polyfill";

const BASE_URL = "http://localhost"; // TODO: replace with production URL

async function initializeFloatingButton() {
    try {
        await chrome.storage.local.set({ floatingButtonVisible: true });
        // The floating button will show automatically when storage changes
    } catch (error) {
        console.error("Failed to show floating button:", error);
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    // Initialize floating button
    await initializeFloatingButton();

    const states = {
        loading: document.getElementById("loading-state"),
        error: document.getElementById("error-state"),
        loggedOut: document.getElementById("logged-out-state"),
        loggedIn: document.getElementById("logged-in-state"),
        formFilling: document.getElementById("form-filling-state"),
    };

    const elements = {
        errorMessage: document.getElementById("error-message"),
        userEmail: document.getElementById("user-email"),
        loginBtn: document.getElementById("login-btn"),
        logoutBtn: document.getElementById("logout-btn"),
        fillCurrentFormBtn: document.getElementById("fill-current-form-btn"),
        clearFormBtn: document.getElementById("clear-form-btn"),
        reloadFormsBtn: document.getElementById("reload-forms-btn"),
        formFillRetryBtn: document.getElementById("form-fill-retry-btn"),
        formFillingMessage: document.getElementById("form-filling-message"),
        formFillingPage: document.getElementById("form-filling-page"),
        formFillingFields: document.getElementById("form-filling-fields"),
        profileSelect: document.getElementById("profile-select"),
        dashboardBtn: document.getElementById("dashboard-btn-logged-in"),
        formsDetected: document.getElementById("forms-detected"),
        formSelectPopup: document.getElementById("form-select-popup"),
    };

    // Listen for changes to detected forms in storage
    chrome.storage.onChanged.addListener((changes, namespace) => {
        if (namespace === "local" && changes.detectedForms) {
            updateDetectedForms();
        }
    });

    function showState(stateName) {
        Object.values(states).forEach((el) => el.classList.add("hidden"));
        if (states[stateName]) {
            states[stateName].classList.remove("hidden");
            if (stateName === "loggedIn") {
                loadProfiles();
                updateDetectedForms();
            }
        }
    }

    function showError(message) {
        elements.errorMessage.textContent = message;
        showState("error");
    }

    function getFormFriendlyName(form) {
        if (!form.action) return "Form";
        try {
            const pathname = new URL(form.action || window.location.href).pathname.toLowerCase();
            const cleanPath = pathname.replace(/^\//, "").replace(/-/g, " ").replace(/_/g, " ");
            return cleanPath.charAt(0).toUpperCase() + cleanPath.slice(1) + " Form";
        } catch (error) {
            return "Form";
        }
    }

    async function updateDetectedForms() {
        try {
            const result = await chrome.storage.local.get(["detectedForms"]);
            const pageData = result.detectedForms;
            if (pageData && pageData.forms) {
                const forms = pageData.forms;
                elements.formsDetected.textContent = forms.length;

                // Populate form selector
                elements.formSelectPopup.innerHTML = '<option value="">Select a form...</option>';
                forms.forEach((form, index) => {
                    const option = document.createElement("option");
                    option.value = form.id;
                    const friendlyName = getFormFriendlyName(form);
                    const fieldsCount = form.fields.length;
                    option.textContent = `${friendlyName}: ${fieldsCount} fields`;
                    elements.formSelectPopup.appendChild(option);
                });

                // Select first form by default if available
                if (forms.length > 0) {
                    elements.formSelectPopup.value = forms[0].id;
                }

                // Update button text to show forms found
                const formText = forms.length === 1 ? "Form" : "Forms";
                elements.reloadFormsBtn.textContent = `${forms.length} ${formText} found`;
                // Reset to "Search Forms" after 1 second
                setTimeout(() => {
                    elements.reloadFormsBtn.textContent = "Search Forms";
                }, 1000);
            } else {
                elements.formsDetected.textContent = "0";
                elements.formSelectPopup.innerHTML = '<option value="">No forms detected</option>';
                elements.reloadFormsBtn.textContent = "Search Forms";
            }
        } catch (error) {
            console.error("Failed to load forms from storage:", error);
            elements.formsDetected.textContent = "0";
            elements.formSelectPopup.innerHTML = '<option value="">Error loading forms</option>';
            elements.reloadFormsBtn.textContent = "Search Forms";
        }
    }

    async function loadProfiles() {
        try {
            // Assume endpoint is /profiles
            const response = await chrome.runtime.sendMessage({
                action: "getProfiles",
            });
            if (response.success) {
                elements.profileSelect.innerHTML = '<option value="">Select a profile...</option>';
                response.profiles.forEach((profile) => {
                    const option = document.createElement("option");
                    option.value = profile.id;
                    option.textContent = `${profile.name} (${profile.type})`;
                    elements.profileSelect.appendChild(option);
                });

                // Load previously selected profile from storage
                const storageData = await chrome.storage.local.get(["selectedProfileId"]);
                const savedProfileId = storageData.selectedProfileId;

                // Select saved profile or first profile by default
                if (savedProfileId && response.profiles.some((p) => p.id == savedProfileId)) {
                    elements.profileSelect.value = savedProfileId;
                } else if (response.profiles.length > 0) {
                    elements.profileSelect.value = response.profiles[0].id;
                }
            } else {
                elements.profileSelect.innerHTML =
                    '<option value="">Failed to load profiles</option>';
            }
        } catch (error) {
            console.error("Failed to load profiles:", error);
            elements.profileSelect.innerHTML = '<option value="">Error loading profiles</option>';
        }
    }

    // Initialize the first state
    showState("loading");
    const response = await chrome.runtime.sendMessage({ action: "checkAuth" });

    if (response.success && response.authenticated) {
        elements.userEmail.textContent = response.user.email;
        showState("loggedIn");
    } else if (response.success) {
        showState("loggedOut");
    } else {
        showError(response.error);
    }

    // Listen for auth changes and update UI accordingly when token changes or removed from storage
    chrome.storage.onChanged.addListener((changes, namespace) => {
        if (namespace === "local" && changes.authToken) {
            // Recheck auth when token changes
            chrome.runtime.sendMessage({ action: "checkAuth" }).then((response) => {
                if (response.success && response.authenticated) {
                    elements.userEmail.textContent = response.user.email;
                    showState("loggedIn");
                } else {
                    showState("loggedOut");
                }
            });
        }
    });

    // Login button - now opens web login page
    elements.loginBtn.addEventListener("click", async () => {
        const response = await chrome.runtime.sendMessage({
            action: "openLoginPage",
        });
        if (response.success) {
            // Wait a bit for user to login, then check auth
            setTimeout(async () => {
                const authResponse = await chrome.runtime.sendMessage({
                    action: "checkAuth",
                });
                if (authResponse.success && authResponse.authenticated) {
                    elements.userEmail.textContent = authResponse.user.email;
                    showState("loggedIn");
                } else {
                    showState("loggedOut");
                }
            }, 5000); // 5 seconds
        }
    });

    // Logout
    elements.logoutBtn.addEventListener("click", async () => {
        showState("loading");
        const logoutResponse = await chrome.runtime.sendMessage({
            action: "logout",
        });
        // Clear selected profile from storage on logout
        await chrome.storage.local.remove(["selectedProfileId"]);
        showState("loggedOut");
    });

    // Dashboard button
    elements.dashboardBtn.addEventListener("click", async () => {
        // Open dashboard in new tab
        const dashboardUrl = BASE_URL + "/dashboard";
        await chrome.tabs.create({ url: dashboardUrl });
    });

    // Profile selection change listener
    elements.profileSelect.addEventListener("change", async () => {
        const selectedProfileId = elements.profileSelect.value;
        await chrome.storage.local.set({ selectedProfileId });
    });

    // Retry button
    elements.formFillRetryBtn?.addEventListener("click", async () => {
        showState("loading");
        const response = await chrome.runtime.sendMessage({
            action: "checkAuth",
        });

        if (response.success && response.authenticated) {
            elements.userEmail.textContent = response.user.email || "User";
            showState("loggedIn");
        } else if (response.success) {
            showState("loggedOut");
        } else {
            showError(response.error);
        }
    });

    // Fill Current Form button
    elements.fillCurrentFormBtn?.addEventListener("click", async () => {
        try {
            const selectedFormId = elements.formSelectPopup.value;
            if (!selectedFormId) {
                showError("Please select a form to fill");
                return;
            }

            // Show filling state
            showState("formFilling");
            elements.formFillingMessage.textContent = "Detecting form fields...";
            elements.formFillingPage.textContent = "Loading...";
            elements.formFillingFields.textContent = "0";

            // First detect forms on the current page
            const detectResponse = await chrome.runtime.sendMessage({
                action: "detectForms",
            });

            if (!detectResponse.success) {
                showError("Failed to detect forms: " + detectResponse.error);
                return;
            }

            if (detectResponse.data.forms.length === 0) {
                showError("No forms found on this page");
                return;
            }

            // Find the selected form
            const selectedForm = detectResponse.data.forms.find(
                (form) => form.id == selectedFormId
            );
            if (!selectedForm) {
                showError("Selected form not found");
                return;
            }

            // Create form data with only the selected form
            const selectedFormData = {
                url: detectResponse.data.url,
                title: detectResponse.data.title,
                forms: [selectedForm],
                timestamp: detectResponse.data.timestamp,
            };

            // Update progress
            elements.formFillingMessage.textContent = "Analyzing form structure...";
            elements.formFillingPage.textContent = detectResponse.data.title || "Current Page";
            elements.formFillingFields.textContent = selectedForm.fields.length;

            // Send form data to backend for filling
            elements.formFillingMessage.textContent = "Filling form with your data...";
            const selectedProfile = elements.profileSelect.value;
            const sendResponse = await chrome.runtime.sendMessage({
                action: "sendFormData",
                formData: selectedFormData,
                profileId: selectedProfile,
            });

            if (sendResponse.success) {
                // Show success state briefly
                elements.formFillingMessage.textContent = "Forms filled successfully!";
                elements.formFillingMessage.style.color = "#4CAF50";

                // Wait a moment to show success, then return to logged in state
                setTimeout(() => {
                    showState("loggedIn");
                }, 1000);
            } else {
                showError("Failed to fill forms: " + sendResponse.error);
            }
        } catch (error) {
            console.error("Fill form error:", error);
            showError("An error occurred while processing forms");
        }
    });

    // Reload Forms button
    elements.reloadFormsBtn?.addEventListener("click", async () => {
        try {
            // Show loading state
            elements.reloadFormsBtn.innerHTML = '<div class="spinner"></div> Searching...';
            elements.reloadFormsBtn.disabled = true;

            // Send message to content script to reload forms
            const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
            if (tab) {
                await chrome.tabs.sendMessage(tab.id, { action: "reloadForms" });
                // Reset button
                elements.reloadFormsBtn.innerHTML = "Search Forms";
                elements.reloadFormsBtn.disabled = false;
            } else {
                throw new Error("No active tab found");
            }
        } catch (error) {
            console.error("Reload forms error:", error);
            showError("Failed to reload forms");
            // Reset button on error
            elements.reloadFormsBtn.innerHTML = "Reload Forms";
            elements.reloadFormsBtn.disabled = false;
        }
    });

    // Initialize clear form functionality
    if (typeof initializeClearFormButton === "function") {
        initializeClearFormButton(states, elements, showState, showError);
    }
});
