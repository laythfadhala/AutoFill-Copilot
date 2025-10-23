// Form Filling Content Script

// Fill forms with AI-generated data
function fillForms(filledData, forms) {
  let fieldsFilled = 0;
  let fieldsSkipped = 0;

  try {
    forms.forEach((form, formIndex) => {
      const pageForm = document.querySelectorAll('form')[formIndex];
      if (!pageForm) {
        console.warn(`Form ${formIndex} not found on page`);
        return;
      }

      form.fields.forEach(field => {
        try {
          if (!field.name) {
            fieldsSkipped++;
            return;
          }
            const input = pageForm.querySelector(`[name="${field.name}"]`) ||
                pageForm.querySelector(`#${field.name}`) ||
                pageForm.querySelector(`[id="${field.name}"]`) ||
                pageForm.querySelector(`[placeholder*="${field.name}"]`);

          if (!input) {
            console.warn(`Field ${field.name} not found in form ${formIndex}`);
            fieldsSkipped++;
            return;
          }

          // Skip disabled fields
          if (input.disabled) {
            fieldsSkipped++;
            return;
          }

          // Get the value from filled data
          const value = filledData[field.name];

          if (value !== undefined && value !== null) {
            // Handle different input types
            if (input.type === 'checkbox') {
              const stringValue = String(value).toLowerCase();
              input.checked = value === '1' || value === 1 || value === true || stringValue === 'yes';
            } else if (input.type === 'radio') {
              // For radio buttons, check if the value matches
              if (input.value == value.toString()) {
                input.checked = true;
              }
            } else if (input.tagName === 'SELECT') {
              // For select dropdowns, find the option with matching value or text
              const stringValue = String(value).toLowerCase();
              const options = Array.from(input.options);
              const matchingOption = options.find(option =>
                option.value === String(value) ||
                (option.text && option.text.toLowerCase() === stringValue)
              );

              if (matchingOption) {
                input.value = matchingOption.value;
                // Trigger change event
                input.dispatchEvent(new Event('change', { bubbles: true }));
              }
            } else {
              // For text inputs, textareas, etc.
              input.value = String(value);
              // Trigger input event
              input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            fieldsFilled++;
            console.log(`Filled field ${field.name} with value: ${value}`);
          } else {
            fieldsSkipped++;
          }
        } catch (fieldError) {
          console.error(`Error filling field ${field.name}:`, fieldError);
          fieldsSkipped++;
        }
      });
    });

    console.log(`Form filling complete: ${fieldsFilled} fields filled, ${fieldsSkipped} fields skipped`);
    return {
      success: true,
      fieldsFilled: fieldsFilled,
      fieldsSkipped: fieldsSkipped,
      message: `Successfully filled ${fieldsFilled} form fields`
    };
  } catch (error) {
    console.error('Error in fillForms function:', error);
    return {
      success: false,
      error: error.message,
      fieldsFilled: fieldsFilled,
      fieldsSkipped: fieldsSkipped,
      message: `Form filling failed: ${error.message}`
    };
  }
}

// Listen for messages from popup/background
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'fillForms') {
    try {
      const result = fillForms(request.filledData, request.forms);
      sendResponse(result);
    } catch (error) {
      console.error('Form filling error:', error);
      sendResponse({ success: false, error: error.message });
    }
  }
  return true;
});