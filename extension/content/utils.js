// Shared utility functions for content scripts

// Get frameworks model value used on the page
function getFrameworkModels(input) {
    const wireModel = input.getAttribute("wire:model") || input.getAttribute("wire:model.defer");
    if (wireModel) return wireModel;

    const vueModel = input.getAttribute("v-model") || input.getAttribute("v-model.lazy");
    if (vueModel) return vueModel;

    const angularModel = input.getAttribute("ng-model");
    if (angularModel) return angularModel;

    const svelteModel = input.getAttribute("bind:value");
    if (svelteModel) return svelteModel;

    const alpineModel = input.getAttribute("x-model");
    if (alpineModel) return alpineModel;

    const knockoutModel = input.getAttribute("data-bind");
    if (knockoutModel) return knockoutModel;

    return null;
}

// Get list of model attributes for different frameworks
function getModelAttributes() {
    return [
        "wire:model",
        "wire:model.defer",
        "v-model",
        "v-model.lazy",
        "ng-model",
        "bind:value",
        "x-model",
        "data-bind",
    ];
}

// Detect if the page is using React for later use if needed
// function isReactApp() {
//     return !!document.querySelector(
//         "[data-reactroot], [data-react-helmet], [__reactInternalInstanceKey]"
//     );
// }

// Fields to exclude from detection
var EXCLUDED_FIELD_TYPES = [
    "hidden",
    "submit",
    "reset",
    "button",
    "image",
    "search",
    "password",
    "file",
];
var EXCLUDED_FIELD_NAMES = [
    "csrf_token",
    "csrfmiddlewaretoken",
    "_token",
    "authenticity_token",
    "__requestverificationtoken",
    "verification_token",
    "nonce",
    "g-recaptcha-response",
];

// Forms to exclude from detection
var EXCLUDED_FORM_CLASSES = [
    "mk-searchform",
    "searchform",
    "search-form",
    "search_form",
    "site-search",
    "search-box",
    "search-container",
    "navbar-search",
    "header-search",
    "wp-search",
    "woocommerce-product-search",
];

var EXCLUDED_FORM_IDS = [
    "searchform",
    "search-form",
    "search_form",
    "searchForm",
    "site-search",
    "search-box",
    "search-container",
    "navbar-search",
    "header-search",
];

// Check if a form should be excluded from detection
function shouldExcludeForm(form) {
    // Get all detectable input fields (excluding hidden, submit, etc.)
    const inputs = form.querySelectorAll("input, select, textarea");
    let detectableFields = 0;

    inputs.forEach((input) => {
        // Count fields that would normally be detected
        if (!EXCLUDED_FIELD_TYPES.includes(input.type)) {
            const fieldName = (input.name || input.id || "").toLowerCase();
            if (!EXCLUDED_FIELD_NAMES.some((excluded) => fieldName.includes(excluded))) {
                detectableFields++;
            }
        }
    });

    // Exclude forms with only one detectable field (likely search forms)
    if (detectableFields <= 1) {
        return true;
    }

    // Keep existing checks for good measure
    // Check class names
    const classList = Array.from(form.classList);
    if (
        classList.some((className) =>
            EXCLUDED_FORM_CLASSES.some((excluded) =>
                className.toLowerCase().includes(excluded.toLowerCase())
            )
        )
    ) {
        return true;
    }

    // Check ID
    if (
        form.id &&
        EXCLUDED_FORM_IDS.some((excluded) => form.id.toLowerCase().includes(excluded.toLowerCase()))
    ) {
        return true;
    }

    // Check action URL for search-related terms
    if (
        form.action &&
        (form.action.toLowerCase().includes("search") || form.action.toLowerCase().includes("/?s="))
    ) {
        return true;
    }

    return false;
}

// Get field label from various sources
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
            const labelText = parent.textContent.trim();
            return labelText.slice(0, 32); // ✅ return only first 32 characters
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
        if (sibling.textContent && sibling.textContent.trim()) {
            return sibling.textContent.trim().slice(0, 32);
        }
        sibling = sibling.previousElementSibling;
    }

    // Method 4: Use placeholder as fallback
    if (input.placeholder) {
        return input.placeholder.slice(0, 32);
    }

    // Method 5: Use name/id as last resort
    return (input.name || input.id || "").slice(0, 32);
}

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

    const frameWorksModels = getFrameworkModels(input);
    const fieldInfo = {
        name: frameWorksModels || input.id || input.name || "",
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
