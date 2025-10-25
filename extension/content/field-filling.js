// Field Filling Utilities
// Helper function to animate text typing effect
function animateText(input, text, callback) {
    let i = 0;
    const interval = setInterval(() => {
        input.value = text.substring(0, i);
        i++;
        if (i > text.length) {
            clearInterval(interval);
            if (callback) callback();
        }
    }, 10); // Increased to 100ms per character for better visibility
}

// Helper function to fill a single field
function fillSingleField(input, value) {
    try {
        if (input.type === "checkbox") {
            const stringValue = String(value).toLowerCase();
            input.checked = value === "1" || value === 1 || value === true || stringValue === "yes";
        } else if (input.type === "radio") {
            // Handle radio button groups: find all radios with the same name
            const radioGroup = document.querySelectorAll(
                `input[type="radio"][name="${input.name}"]`
            );
            if (radioGroup.length > 0) {
                // Uncheck all radios in the group first
                radioGroup.forEach((radio) => {
                    radio.checked = false;
                });
                // Check the radio whose value matches (case-insensitive)
                const matchingRadio = Array.from(radioGroup).find(
                    (radio) => radio.value.toLowerCase() == value.toString().toLowerCase()
                );
                if (matchingRadio) {
                    matchingRadio.checked = true;
                    // Trigger change event
                    matchingRadio.dispatchEvent(new Event("change", { bubbles: true }));
                }
            }
        } else if (input.tagName === "SELECT") {
            // For select dropdowns, find the option with matching value or text
            const stringValue = String(value).toLowerCase();
            const options = Array.from(input.options);
            const matchingOption = options.find(
                (option) =>
                    option.value === String(value) ||
                    (option.text && option.text.toLowerCase() === stringValue)
            );

            if (matchingOption) {
                input.value = matchingOption.value;
                // Trigger change event
                input.dispatchEvent(new Event("change", { bubbles: true }));
            }
        } else {
            // For text inputs, textareas, date inputs, etc.
            const text = String(value);
            animateText(input, text, () => {
                // Trigger input event after animation completes
                input.dispatchEvent(new Event("input", { bubbles: true }));
            });
        }
        input.classList.remove("autofill-pulse-ring");
        return { success: true };
    } catch (error) {
        console.error("Error filling single field:", error);
        return { success: false, error: error.message };
    }
}

// Export for use in other modules
window.fillSingleField = fillSingleField;
