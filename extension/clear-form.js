// Clear Form functionality
import browser from "webextension-polyfill";

function initializeClearFormButton(states, elements, showState, showError) {
    // Clear Form button
    elements.clearFormBtn?.addEventListener("click", async () => {
        try {
            // Show filling state (reuse for clearing)
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

            // Update progress
            elements.formFillingMessage.textContent = "Clearing form fields...";
            elements.formFillingPage.textContent = detectResponse.data.title || "Current Page";
            const totalFields = detectResponse.data.forms.reduce(
                (sum, form) => sum + form.fields.length,
                0
            );
            elements.formFillingFields.textContent = totalFields;

            // Send clear command
            const clearResponse = await chrome.runtime.sendMessage({
                action: "clearForms",
                formData: detectResponse.data,
            });

            if (clearResponse.success) {
                // Show success state briefly
                elements.formFillingMessage.textContent = "Forms cleared successfully!";
                elements.formFillingMessage.style.color = "#4CAF50";

                // Wait a moment to show success, then return to logged in state
                setTimeout(() => {
                    showState("loggedIn");
                }, 1000);
            } else {
                showError("Failed to clear forms: " + clearResponse.error);
            }
        } catch (error) {
            console.error("Clear form error:", error);
            showError("An error occurred while clearing forms");
        }
    });
}

// Export the function so it can be used in popup.js
window.initializeClearFormButton = initializeClearFormButton;
