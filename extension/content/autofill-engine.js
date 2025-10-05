// AutoFill Copilot - Simple Form Filler
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.action === 'TRIGGER_AUTOFILL') {
    fillForm(request.userData);
    sendResponse({ success: true, message: 'Form filled' });
  } else if (request.action === 'CLEAR_FORM') {
    clearForm();
    sendResponse({ success: true, message: 'Form cleared' });
  }
  return true;
});

function fillForm(userData) {
  const inputs = document.querySelectorAll('input, textarea');
  inputs.forEach(input => {
    const value = getValueForInput(input, userData);
    if (value) {
      input.value = value;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
}

function clearForm() {
  const inputs = document.querySelectorAll('input:not([type="submit"]):not([type="button"]), textarea');
  inputs.forEach(input => {
    if (input.type !== 'checkbox' && input.type !== 'radio') {
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
      input.checked = false;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
}

function getValueForInput(input, userData) {
  const name = (input.name || input.id || input.placeholder || '').toLowerCase();
  const label = getFieldLabel(input);
  const searchText = (name + ' ' + label).toLowerCase();
  const type = input.type ? input.type.toLowerCase() : '';
  
  // Personal Information
  if (searchText.includes('title') && !searchText.includes('job') && !searchText.includes('work')) {
    return userData.title || '';
  }
  if (searchText.includes('first') && searchText.includes('name') || searchText.includes('firstname') || searchText.includes('fname')) {
    return userData.firstName || '';
  }
  if (searchText.includes('middle') && (searchText.includes('initial') || searchText.includes('name'))) {
    return userData.middleInitial || userData.middleName || '';
  }
  if (searchText.includes('last') && searchText.includes('name') || searchText.includes('lastname') || searchText.includes('lname') || searchText.includes('surname')) {
    return userData.lastName || '';
  }
  if (searchText.includes('full') && searchText.includes('name') || (searchText.match(/\bname\b/) && !searchText.includes('first') && !searchText.includes('last') && !searchText.includes('middle') && !searchText.includes('user') && !searchText.includes('company'))) {
    return (userData.firstName || '') + ' ' + (userData.lastName || '');
  }
  
  // Contact Information
  if (type === 'email' || searchText.includes('email') || searchText.includes('e-mail') || searchText.includes('mail')) {
    return userData.email || '';
  }
  if (searchText.includes('website') || searchText.includes('web') || searchText.includes('url') || searchText.includes('homepage')) {
    return userData.website || '';
  }
  
  // Phone Numbers
  if (type === 'tel' || searchText.includes('phone') || searchText.includes('telephone')) {
    if (searchText.includes('home')) return userData.homePhone || userData.phone || '';
    if (searchText.includes('work') || searchText.includes('office') || searchText.includes('business')) return userData.workPhone || userData.phone || '';
    if (searchText.includes('cell') || searchText.includes('mobile')) return userData.cellPhone || userData.phone || '';
    if (searchText.includes('fax')) return userData.fax || '';
    return userData.phone || '';
  }
  
  // Company Information
  if (searchText.includes('company') || searchText.includes('organization') || searchText.includes('employer') || searchText.includes('business')) {
    return userData.company || '';
  }
  if (searchText.includes('position') || searchText.includes('job') || searchText.includes('title')) {
    return userData.position || userData.jobTitle || '';
  }
  
  // Address Information
  if (searchText.includes('address')) {
    if (searchText.includes('line') && (searchText.includes('1') || searchText.includes('one'))) {
      return userData.addressLine1 || userData.address || '';
    }
    if (searchText.includes('line') && (searchText.includes('2') || searchText.includes('two'))) {
      return userData.addressLine2 || '';
    }
    return userData.address || userData.addressLine1 || '';
  }
  if (searchText.includes('street') || searchText.includes('addr')) {
    return userData.address || userData.addressLine1 || '';
  }
  if (searchText.includes('city') || searchText.includes('town')) {
    return userData.city || '';
  }
  if (searchText.includes('state') || searchText.includes('province') || searchText.includes('region')) {
    return userData.state || userData.province || '';
  }
  if (searchText.includes('country')) {
    return userData.country || '';
  }
  if (searchText.includes('zip') || searchText.includes('postal') || searchText.includes('postcode')) {
    return userData.zipCode || userData.postalCode || '';
  }
  
  // Credit Card Information
  if (searchText.includes('credit') && searchText.includes('card')) {
    if (searchText.includes('type')) return userData.creditCardType || '';
    if (searchText.includes('number')) return userData.creditCardNumber || '';
    if (searchText.includes('name') || searchText.includes('holder')) return userData.creditCardName || userData.firstName + ' ' + userData.lastName || '';
    if (searchText.includes('bank') || searchText.includes('issuing')) return userData.creditCardBank || '';
    if (searchText.includes('service') || searchText.includes('support')) return userData.creditCardServicePhone || '';
  }
  if (searchText.includes('card')) {
    if (searchText.includes('verification') || searchText.includes('cvv') || searchText.includes('cvc') || searchText.includes('security')) {
      return userData.cvv || '';
    }
    if (searchText.includes('expir') || searchText.includes('exp')) {
      if (searchText.includes('month')) return userData.creditCardExpMonth || '';
      if (searchText.includes('year')) return userData.creditCardExpYear || '';
      return userData.creditCardExpiry || '';
    }
  }
  
  // Authentication
  if (searchText.includes('user') && (searchText.includes('id') || searchText.includes('name'))) {
    return userData.username || userData.email || '';
  }
  if (type === 'password' || searchText.includes('password') || searchText.includes('pass')) {
    return userData.password || '';
  }
  
  // Personal Details
  if (searchText.includes('sex') || searchText.includes('gender')) {
    return userData.sex || userData.gender || '';
  }
  if (searchText.includes('social') && searchText.includes('security')) {
    return userData.ssn || '';
  }
  if (searchText.includes('driver') && searchText.includes('license')) {
    return userData.driverLicense || '';
  }
  if (searchText.includes('birth')) {
    if (searchText.includes('place')) return userData.birthPlace || '';
    if (searchText.includes('month')) return userData.birthMonth || '';
    if (searchText.includes('day')) return userData.birthDay || '';
    if (searchText.includes('year')) return userData.birthYear || '';
    return userData.dateOfBirth || '';
  }
  if (searchText.includes('age')) {
    return userData.age || '';
  }
  if (searchText.includes('income')) {
    return userData.income || '';
  }
  
  // Comments and Custom Fields
  if (searchText.includes('comment') || searchText.includes('message') || searchText.includes('note')) {
    return userData.comments || userData.customMessage || '';
  }
  
  return null;
}

function getFieldLabel(input) {
  // Try to find associated label
  let label = '';
  
  if (input.id) {
    const labelElement = document.querySelector(`label[for="${input.id}"]`);
    if (labelElement) {
      label = labelElement.textContent.trim();
    }
  }
  
  if (!label) {
    const parentLabel = input.closest('label');
    if (parentLabel) {
      label = parentLabel.textContent.replace(input.value, '').trim();
    }
  }
  
  return label;
}

// Form detection and counting
function countForms() {
  const forms = document.querySelectorAll('form');
  const standaloneInputs = document.querySelectorAll('input:not(form input), textarea:not(form textarea)');
  
  // Count explicit forms plus groups of standalone inputs
  let formCount = forms.length;
  
  // Group standalone inputs by proximity/context to estimate "implied forms"
  if (standaloneInputs.length > 0) {
    const inputContainers = new Set();
    standaloneInputs.forEach(input => {
      // Look for common container elements that might represent a form-like section
      const container = input.closest('div[class*="form"], div[class*="login"], div[class*="signup"], div[class*="contact"], section, article') 
                       || input.closest('div');
      if (container && container !== document.body) {
        inputContainers.add(container);
      }
    });
    
    // Add estimated form groups
    formCount += inputContainers.size;
  }
  
  return Math.max(formCount, standaloneInputs.length > 0 ? 1 : 0);
}

function updateFormCount() {
  const count = countForms();
  console.log(`AutoFill Copilot: Found ${count} forms on ${window.location.href}`);
  
  // Send message with retry mechanism
  const sendCount = () => {
    chrome.runtime.sendMessage({
      action: 'updateFormCount',
      count: count,
      url: window.location.href
    }).then(response => {
      console.log('AutoFill Copilot: Form count sent successfully');
    }).catch(error => {
      console.log('AutoFill Copilot: Could not send form count:', error);
      // Retry once after a short delay
      setTimeout(() => {
        chrome.runtime.sendMessage({
          action: 'updateFormCount',
          count: count,
          url: window.location.href
        }).catch(() => {
          console.log('AutoFill Copilot: Retry failed');
        });
      }, 1000);
    });
  };
  
  sendCount();
}

// Wait for DOM to be fully loaded before initial count
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(updateFormCount, 100);
  });
} else {
  // DOM already loaded
  setTimeout(updateFormCount, 100);
}

// Also send count when page is fully loaded (including images, etc.)
if (document.readyState !== 'complete') {
  window.addEventListener('load', () => {
    setTimeout(updateFormCount, 200);
  });
}

// Watch for dynamic form additions
const observer = new MutationObserver((mutations) => {
  let shouldUpdate = false;
  mutations.forEach((mutation) => {
    if (mutation.type === 'childList') {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const hasForm = node.tagName === 'FORM' || node.querySelector('form');
          const hasInputs = node.tagName === 'INPUT' || node.tagName === 'TEXTAREA' || 
                           node.querySelector('input, textarea');
          if (hasForm || hasInputs) {
            shouldUpdate = true;
          }
        }
      });
    }
  });
  
  if (shouldUpdate) {
    // Debounce updates to avoid excessive calls
    clearTimeout(window.formCountTimeout);
    window.formCountTimeout = setTimeout(updateFormCount, 500);
  }
});

observer.observe(document.body, {
  childList: true,
  subtree: true
});

console.log('AutoFill Copilot: Enhanced engine loaded with form counting');
