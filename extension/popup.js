// AutoFill Copilot Popup Script
const BASE_URL = "http://localhost"; // TODO: replace with production URL

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
        formFillRetryBtn: document.getElementById("form-fill-retry-btn"),
        formFillingMessage: document.getElementById("form-filling-message"),
        formFillingPage: document.getElementById("form-filling-page"),
        formFillingFields: document.getElementById("form-filling-fields"),
        profileSelect: document.getElementById("profile-select"),
        dashboardBtn: document.getElementById("dashboard-btn-logged-in"),
        formsDetected: document.getElementById("forms-detected"),
    };

    function showState(stateName) {
        Object.values(states).forEach((el) => el.classList.add("hidden"));
        if (states[stateName]) {
            states[stateName].classList.remove("hidden");
            if (stateName === "loggedIn") {
                loadProfiles();
                updateFormsDetected();
            }
        }
    }

    function showError(message) {
        elements.errorMessage.textContent = message;
        showState("error");
    }

    async function updateFormsDetected() {
        try {
            const detectResponse = await chrome.runtime.sendMessage({
                action: "detectForms",
            });
            if (detectResponse.success) {
                elements.formsDetected.textContent =
                    detectResponse.data.forms.length;
            } else {
                elements.formsDetected.textContent = "0";
            }
        } catch (error) {
            console.error("Failed to detect forms:", error);
            elements.formsDetected.textContent = "0";
        }
    }

    async function loadProfiles() {
        try {
            // Assume endpoint is /profiles
            const response = await chrome.runtime.sendMessage({
                action: "getProfiles",
            });
            if (response.success) {
                elements.profileSelect.innerHTML =
                    '<option value="">Select a profile...</option>';
                response.profiles.forEach((profile) => {
                    const option = document.createElement("option");
                    option.value = profile.id;
                    option.textContent = `${profile.name} (${profile.type})`;
                    elements.profileSelect.appendChild(option);
                });

                // Load previously selected profile from storage
                const storageData = await chrome.storage.local.get([
                    "selectedProfileId",
                ]);
                const savedProfileId = storageData.selectedProfileId;

                // Select saved profile or first profile by default
                if (
                    savedProfileId &&
                    response.profiles.some((p) => p.id == savedProfileId)
                ) {
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
            elements.profileSelect.innerHTML =
                '<option value="">Error loading profiles</option>';
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
            chrome.runtime
                .sendMessage({ action: "checkAuth" })
                .then((response) => {
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
            // Show filling state
            showState("formFilling");
            elements.formFillingMessage.textContent =
                "Detecting form fields...";
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

            // Update progress
            elements.formFillingMessage.textContent =
                "Analyzing form structure...";
            elements.formFillingPage.textContent =
                detectResponse.data.title || "Current Page";
            const totalFields = detectResponse.data.forms.reduce(
                (sum, form) => sum + form.fields.length,
                0
            );
            elements.formFillingFields.textContent = totalFields;

            // Send form data to backend for filling
            elements.formFillingMessage.textContent =
                "Filling forms with your data...";
            const selectedProfile = elements.profileSelect.value;
            const sendResponse = await chrome.runtime.sendMessage({
                action: "sendFormData",
                formData: detectResponse.data,
                profileId: selectedProfile,
            });

            if (sendResponse.success) {
                // Show success state briefly
                elements.formFillingMessage.textContent =
                    "Forms filled successfully!";
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

    // Initialize clear form functionality
    if (typeof initializeClearFormButton === "function") {
        initializeClearFormButton(states, elements, showState, showError);
    }
});
