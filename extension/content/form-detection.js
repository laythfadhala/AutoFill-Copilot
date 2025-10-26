// Form Detection Content Script
import browser from "webextension-polyfill";

// Detect standalone global inputs not in forms
function detectStandaloneGlobalInputs(processedInputs) {
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

    // Only return a form if there are multiple fields (exclude single-field utilities)
    if (standaloneFields.length > 1) {
        return {
            id: "globalFields",
            action: window.location.href,
            method: "GET",
            fields: standaloneFields,
        };
    }

    return null;
}

// Detect forms on the current page
function detectForms() {
    const forms = document.querySelectorAll("form");
    const formData = [];
    const processedInputs = new Set();

    forms.forEach((form, index) => {
        // Skip excluded forms (like search forms)
        if (shouldExcludeForm(form)) {
            return;
        }

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

    //* Detect standalone global inputs not in forms disabled for now
    // const standaloneForm = detectStandaloneGlobalInputs(processedInputs);
    // if (standaloneForm) {
    //     formData.push(standaloneForm);
    // }

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
