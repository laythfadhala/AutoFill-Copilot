// Form Filling Content Script

let currentField = null;

// Listen for right-click on form fields
document.addEventListener("contextmenu", function (e) {
    if (e.target.matches("input, select, textarea")) {
        currentField = e.target;
    }
});

function fillForms(filledData, forms) {
    let fieldsFilled = 0;
    let fieldsSkipped = 0;

    try {
        forms.forEach((form, formIndex) => {
            let pageForm;
            let isGlobalFields = false;

            if (form.id === "globalFields") {
                // Handle standalone inputs not in any form
                isGlobalFields = true;
                pageForm = document; // Search globally
            } else {
                pageForm = document.querySelectorAll("form")[form.id];
                if (!pageForm) {
                    console.warn(`Form ${formIndex} not found on page`);
                    return;
                }
            }

            form.fields.forEach((field) => {
                try {
                    if (!field.name) {
                        console.warn(
                            `Field with no name skipped in ${
                                isGlobalFields
                                    ? "global fields"
                                    : `form ${formIndex}`
                            }`
                        );
                        fieldsSkipped++;
                        return;
                    }

                    let input;
                    const safeName = CSS.escape(field.name || "");
                    if (isGlobalFields) {
                        // For global fields, search across the entire document
                        input =
                            document.querySelector(`[name="${safeName}"]`) ||
                            document.querySelector(`#${safeName}`) ||
                            document.querySelector(`[id="${safeName}"]`) ||
                            document.querySelector(
                                `[wire\\:model="${safeName}"]`
                            ) ||
                            document.querySelector(
                                `[wire\\:model\\.defer="${safeName}"]`
                            ) ||
                            document.querySelector(
                                `[placeholder*="${safeName}"]`
                            );
                    } else {
                        // For regular forms, search within the form element
                        input =
                            pageForm.querySelector(`[name="${safeName}"]`) ||
                            pageForm.querySelector(`#${safeName}`) ||
                            pageForm.querySelector(`[id="${safeName}"]`) ||
                            pageForm.querySelector(
                                `[wire\\:model="${safeName}"]`
                            ) ||
                            pageForm.querySelector(
                                `[wire\\:model\\.defer="${safeName}"]`
                            ) ||
                            pageForm.querySelector(
                                `[placeholder*="${safeName}"]`
                            );
                    }

                    if (!input) {
                        console.warn(
                            `Field ${field.name} not found in ${
                                isGlobalFields
                                    ? "global fields"
                                    : `form ${formIndex}`
                            }`
                        );
                        fieldsSkipped++;
                        return;
                    }

                    // Skip disabled fields
                    if (input.disabled) {
                        console.warn(
                            `Field ${field.name} is disabled, skipping`
                        );
                        fieldsSkipped++;
                        return;
                    }

                    // Get the value from filled data
                    const value = filledData[field.name];

                    if (value !== undefined && value !== null) {
                        // Handle different input types
                        if (input.type === "checkbox") {
                            const stringValue = String(value).toLowerCase();
                            input.checked =
                                value === "1" ||
                                value === 1 ||
                                value === true ||
                                stringValue === "yes";
                        } else if (input.type === "radio") {
                            // Handle radio button groups: find all radios with the same name
                            let radioGroup;
                            if (isGlobalFields) {
                                radioGroup = document.querySelectorAll(
                                    `input[type="radio"][name="${field.name}"]`
                                );
                            } else {
                                radioGroup = pageForm.querySelectorAll(
                                    `input[type="radio"][name="${field.name}"]`
                                );
                            }
                            if (radioGroup.length > 0) {
                                // Uncheck all radios in the group first
                                radioGroup.forEach((radio) => {
                                    radio.checked = false;
                                });
                                // Check the radio whose value matches (case-insensitive)
                                const matchingRadio = Array.from(
                                    radioGroup
                                ).find(
                                    (radio) =>
                                        radio.value.toLowerCase() ==
                                        value.toString().toLowerCase()
                                );
                                if (matchingRadio) {
                                    matchingRadio.checked = true;
                                    // Trigger change event
                                    matchingRadio.dispatchEvent(
                                        new Event("change", { bubbles: true })
                                    );
                                }
                            }
                        } else if (input.tagName === "SELECT") {
                            // For select dropdowns, find the option with matching value or text
                            const stringValue = String(value).toLowerCase();
                            const options = Array.from(input.options);
                            const matchingOption = options.find(
                                (option) =>
                                    option.value === String(value) ||
                                    (option.text &&
                                        option.text.toLowerCase() ===
                                            stringValue)
                            );

                            if (matchingOption) {
                                input.value = matchingOption.value;
                                // Trigger change event
                                input.dispatchEvent(
                                    new Event("change", { bubbles: true })
                                );
                            }
                        } else {
                            // For text inputs, textareas, etc.
                            input.value = String(value);
                            // Trigger input event
                            input.dispatchEvent(
                                new Event("input", { bubbles: true })
                            );
                        }

                        fieldsFilled++;
                        console.log(
                            `Filled field ${field.name} with value: ${value}`
                        );
                    } else {
                        console.warn(
                            `No value provided for field ${field.name}, skipping`
                        );
                        fieldsSkipped++;
                    }
                } catch (fieldError) {
                    console.error(
                        `Error filling field ${field.name}:`,
                        fieldError
                    );
                    fieldsSkipped++;
                }
            });
        });

        console.log(
            `Form filling complete: ${fieldsFilled} fields filled, ${fieldsSkipped} fields skipped`
        );
        return {
            success: true,
            fieldsFilled: fieldsFilled,
            fieldsSkipped: fieldsSkipped,
            message: `Successfully filled ${fieldsFilled} form fields`,
        };
    } catch (error) {
        console.error("Error in fillForms function:", error);
        return {
            success: false,
            error: error.message,
            fieldsFilled: fieldsFilled,
            fieldsSkipped: fieldsSkipped,
            message: `Form filling failed: ${error.message}`,
        };
    }
}

// Listen for messages from popup/background
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "fillForms") {
        try {
            const result = fillForms(request.filledData, request.forms);
            sendResponse(result);
        } catch (error) {
            console.error("Form filling error:", error);
            sendResponse({ success: false, error: error.message });
        }
    } else if (request.action === "checkCurrentField") {
        sendResponse({ hasField: !!currentField });
    } else if (request.action === "fillCurrentField") {
        // Capture the field at the time of the request to avoid race conditions
        const fieldToFill = currentField;
        if (fieldToFill) {
            const fieldInfo = createFieldInfo(fieldToFill);
            if (fieldInfo) {
                chrome.runtime.sendMessage(
                    { action: "fillSingleField", fieldInfo: fieldInfo },
                    (response) => {
                        if (response && response.success) {
                            const result = window.fillSingleField(
                                fieldToFill,
                                response.filledValue
                            );
                            sendResponse(result);
                        } else {
                            sendResponse({
                                success: false,
                                error: response
                                    ? response.error
                                    : "Unknown error",
                            });
                        }
                    }
                );
            } else {
                sendResponse({ success: false, error: "Invalid field" });
            }
        } else {
            sendResponse({ success: false, error: "No field selected" });
        }
    }
    return true;
});
