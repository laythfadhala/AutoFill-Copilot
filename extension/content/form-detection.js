// Fields to exclude from detection
var EXCLUDED_FIELD_TYPES = [
    "hidden",
    "submit",
    "reset",
    "button",
    "image",
    "search",
    "password",
];
var EXCLUDED_FIELD_NAMES = [
    "csrf_token",
    "csrfmiddlewaretoken",
    "_token",
    "authenticity_token",
    "__requestverificationtoken",
    "verification_token",
    "nonce",
];

// Helper function to create field info from an input element
function createFieldInfo(input) {
    // Skip excluded field types
    if (EXCLUDED_FIELD_TYPES.includes(input.type)) {
        return null;
    }

    // Skip disabled fields
    if (input.disabled) {
        return null;
    }

    // Skip fields with excluded names
    const fieldName = (input.name || input.id || "").toLowerCase();
    if (EXCLUDED_FIELD_NAMES.some((excluded) => fieldName.includes(excluded))) {
        return null;
    }

    const wireModel =
        input.getAttribute("wire:model") ||
        input.getAttribute("wire:model.defer");

    const fieldInfo = {
        name: input.name || input.id || wireModel || "",
        type: input.type || "text",
        placeholder: input.placeholder || "",
        value: input.value || "",
        label: getFieldLabel(input),
        required: input.required || false,
        disabled: input.disabled || false,
    };

    // Add options for select elements
    if (input.tagName === "SELECT") {
        fieldInfo.options = Array.from(input.options).map((option) => ({
            value: option.value,
            text: option.text,
            selected: option.selected,
        }));
    }

    return fieldInfo;
}

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

// Get the label text for a form field
function getFieldLabel(input) {
    // Method 1: Check for label with 'for' attribute matching input id
    if (input.id) {
        const label = document.querySelector(`label[for="${input.id}"]`);
        if (label) {
            return label.textContent.trim();
        }
    }

    // Method 2: Check if input is inside a label element
    let parent = input.parentElement;
    while (parent && parent.tagName !== "FORM") {
        if (parent.tagName === "LABEL") {
            return parent.textContent.trim();
        }
        parent = parent.parentElement;
    }

    // Method 3: Look for nearby text that might be a label
    // Check previous sibling elements
    let sibling = input.previousElementSibling;
    while (sibling) {
        if (sibling.tagName === "LABEL") {
            return sibling.textContent.trim();
        }
        // If it's a text node or other element with text, it might be a label
        if (sibling.textContent && sibling.textContent.trim()) {
            // Only use if it's short (likely a label) and doesn't contain form controls
            const text = sibling.textContent.trim();
            if (
                text.length < 100 &&
                !sibling.querySelector("input, select, textarea, button")
            ) {
                return text;
            }
        }
        sibling = sibling.previousElementSibling;
    }

    // Method 4: Look around input
    const container = input.closest("div, p, fieldset, li");
    if (container) {
        // Try text before input within the same container
        const containerText = container.textContent;
        const inputText = input.textContent || input.value || "";
        const beforeInput = containerText.substring(
            0,
            containerText.indexOf(inputText || input.outerHTML)
        );
        const labelText = beforeInput.trim();
        if (labelText && labelText.length < 100) {
            return labelText;
        }

        // Try previous sibling of the container
        const labelDiv = container.previousElementSibling;
        if (labelDiv && labelDiv.textContent.trim().length < 100) {
            return labelDiv.textContent.trim();
        }
    }

    // Method 5: Use placeholder as fallback if no label found
    if (input.placeholder) {
        return input.placeholder;
    }

    // Method 6: Generate a generic label based on field name/type
    if (input.name) {
        return (
            input.name.charAt(0).toUpperCase() +
            input.name.slice(1).replace(/[_-]/g, " ")
        );
    }

    return "";
}

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
