// Form Clearing Content Script
function clearForms(forms) {
    let fieldsCleared = 0;
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
                                isGlobalFields ? "global fields" : `form ${formIndex}`
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
                            document.querySelector(`[wire\\:model="${safeName}"]`) ||
                            document.querySelector(`[wire\\:model\\.defer="${safeName}"]`) ||
                            document.querySelector(`[placeholder*="${safeName}"]`);
                    } else {
                        // For regular forms, search within the form element
                        input =
                            pageForm.querySelector(`[name="${safeName}"]`) ||
                            pageForm.querySelector(`#${safeName}`) ||
                            pageForm.querySelector(`[id="${safeName}"]`) ||
                            pageForm.querySelector(`[wire\\:model="${safeName}"]`) ||
                            pageForm.querySelector(`[wire\\:model\\.defer="${safeName}"]`) ||
                            pageForm.querySelector(`[placeholder*="${safeName}"]`);
                    }

                    if (!input) {
                        console.warn(
                            `Field ${field.name} not found in ${
                                isGlobalFields ? "global fields" : `form ${formIndex}`
                            }`
                        );
                        fieldsSkipped++;
                        return;
                    }

                    // Skip disabled fields
                    if (input.disabled) {
                        console.warn(`Field ${field.name} is disabled, skipping`);
                        fieldsSkipped++;
                        return;
                    }

                    // Clear different input types
                    if (input.type === "checkbox" || input.type === "radio") {
                        input.checked = false;
                    } else if (input.tagName === "SELECT") {
                        input.selectedIndex = 0; // Reset to first option
                    } else {
                        // For text inputs, textareas, etc.
                        input.value = "";
                    }

                    // Trigger change/input event
                    input.dispatchEvent(
                        new Event(
                            input.type === "checkbox" || input.type === "radio"
                                ? "change"
                                : "input",
                            { bubbles: true }
                        )
                    );

                    fieldsCleared++;

                    // Add pulse ring animation to indicate clearing
                    input.classList.add("autofill-pulse-ring");

                    // Remove the animation class after the animation duration
                    setTimeout(() => {
                        input.classList.remove("autofill-pulse-ring");
                    }, 1000);
                } catch (fieldError) {
                    console.error(`Error clearing field ${field.name}:`, fieldError);
                    fieldsSkipped++;
                }
            });
        });

        console.log(
            `Form clearing complete: ${fieldsCleared} fields cleared, ${fieldsSkipped} fields skipped`
        );
        return {
            success: true,
            fieldsCleared: fieldsCleared,
            fieldsSkipped: fieldsSkipped,
            message: `Successfully cleared ${fieldsCleared} form fields`,
        };
    } catch (error) {
        console.error("Error in clearForms function:", error);
        return {
            success: false,
            error: error.message,
            fieldsCleared: fieldsCleared,
            fieldsSkipped: fieldsSkipped,
            message: `Form clearing failed: ${error.message}`,
        };
    }
}
