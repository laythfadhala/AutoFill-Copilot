// Form Detection Content Script
import browser from "webextension-polyfill";

// Detect forms on the current page
function detectForms() {
    const forms = document.querySelectorAll("form");
    const formData = [];
    const processedInputs = new Set();

    forms.forEach((form, index) => {
        const formInfo = {
            id: index,
            action: form.action || window.location.href,
            method: (form.method || "GET").toUpperCase(),
            fields: [],
        };

        // Get all input fields
        const inputs = form.querySelectorAll("input, select, textarea");
        inputs.forEach((input) => {
            const fieldInfo = createFieldInfo(input);
            if (fieldInfo) {
                formInfo.fields.push(fieldInfo);
                processedInputs.add(input);
            }
        });

        // Only include forms that have detectable fields
        if (formInfo.fields.length > 0) {
            formData.push(formInfo);
        }
    });

    // Detect standalone global inputs not in forms
    const allInputs = document.querySelectorAll("input, select, textarea");
    const standaloneFields = [];

    allInputs.forEach((input) => {
        if (processedInputs.has(input)) {
            return;
        }

        const fieldInfo = createFieldInfo(input);
        if (fieldInfo) {
            standaloneFields.push(fieldInfo);
        }
    });

    // Add standalone inputs as a separate form if any exist
    if (standaloneFields.length > 0) {
        const standaloneForm = {
            id: "globalFields",
            action: window.location.href,
            method: "GET",
            fields: standaloneFields,
        };
        formData.push(standaloneForm);
    }

    return {
        url: window.location.href,
        title: document.title,
        forms: formData,
        timestamp: new Date().toISOString(),
    };
}

// Make detectForms globally available for other content scripts
window.detectForms = detectForms;

// Listen for messages from popup/background
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "detectTabForms") {
        try {
            const pageData = detectForms();
            sendResponse({ success: true, data: pageData });
        } catch (error) {
            console.error("Form detection error:", error);
            sendResponse({ success: false, error: error.message });
        }
    }
    return true;
});

// Make detectForms globally available for other content scripts
window.detectForms = detectForms;
