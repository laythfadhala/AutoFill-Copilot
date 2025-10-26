// Forms Observer - Monitors DOM changes and updates detected forms in storage

// Function to store detected forms in storage
function storeDetectedForms() {
    try {
        const pageData = detectForms();
        chrome.storage.local.set({ detectedForms: pageData });
    } catch (error) {
        console.error("Error storing detected forms:", error);
    }
}

// Initialize forms observer
function initializeFormsObserver() {
    // Store initial forms
    storeDetectedForms();

    // Create observer to watch for DOM changes
    const observer = new MutationObserver((mutations) => {
        let shouldUpdate = false;

        mutations.forEach((mutation) => {
            // Check if any added/removed nodes are forms or inputs
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (
                        node.tagName === "FORM" ||
                        node.querySelector("form, input, select, textarea")
                    ) {
                        shouldUpdate = true;
                    }
                }
            });

            mutation.removedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (
                        node.tagName === "FORM" ||
                        node.querySelector("form, input, select, textarea")
                    ) {
                        shouldUpdate = true;
                    }
                }
            });

            // Check if attributes changed on form elements
            if (mutation.type === "attributes" && mutation.target) {
                const target = mutation.target;
                if (
                    target.tagName === "FORM" ||
                    target.tagName === "INPUT" ||
                    target.tagName === "SELECT" ||
                    target.tagName === "TEXTAREA"
                ) {
                    shouldUpdate = true;
                }
            }
        });

        if (shouldUpdate) {
            // Debounce updates to avoid too frequent storage writes
            clearTimeout(window.formsUpdateTimeout);
            window.formsUpdateTimeout = setTimeout(storeDetectedForms, 500);
        }
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["type", "name", "id", "disabled", "required"],
    });
}

// Initialize observer when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeFormsObserver);
} else {
    initializeFormsObserver();
}
