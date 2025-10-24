// Floating Button Content Script
(function () {
    console.log("AutoFill Copilot: Floating button script loaded");

    // Create the floating button container
    const floatingContainer = document.createElement("div");
    floatingContainer.id = "autofill-floating-container";
    floatingContainer.innerHTML = `
        <div class="autofill-floating-main-btn">
            <span class="autofill-floating-icon">+</span>
        </div>
        <div class="autofill-floating-submenu">
            <button class="autofill-floating-sub-btn" id="fill-form-btn">
                <span class="autofill-floating-icon">📝</span>
                Fill Form
            </button>
            <button class="autofill-floating-sub-btn" id="open-popup-btn">
                <span class="autofill-floating-icon">⚙️</span>
                Settings
            </button>
            <button class="autofill-floating-sub-btn" id="hide-btn">
                <span class="autofill-floating-icon">❌</span>
                Hide
            </button>
        </div>
    `;
    document.body.appendChild(floatingContainer);
    console.log("AutoFill Copilot: Floating button injected");

    // Get elements
    const mainBtn = floatingContainer.querySelector(
        ".autofill-floating-main-btn"
    );
    const submenu = floatingContainer.querySelector(
        ".autofill-floating-submenu"
    );
    const fillBtn = floatingContainer.querySelector("#fill-form-btn");
    const openPopupBtn = floatingContainer.querySelector("#open-popup-btn");
    const hideBtn = floatingContainer.querySelector("#hide-btn");

    // Hover to show submenu
    mainBtn.addEventListener("mouseenter", () => {
        submenu.classList.add("show");
    });

    // Click to toggle submenu
    mainBtn.addEventListener("click", () => {
        submenu.classList.toggle("show");
    });

    floatingContainer.addEventListener("mouseleave", () => {
        submenu.classList.remove("show");
    });

    // Fill form button
    fillBtn.addEventListener("click", async () => {
        try {
            // Send message to background to fill forms
            const response = await chrome.runtime.sendMessage({
                action: "detectForms",
            });
            if (response.success) {
                // Proceed to fill
                const fillResponse = await chrome.runtime.sendMessage({
                    action: "sendFormData",
                    formData: response.data,
                });
                if (fillResponse.success) {
                    showNotification("Form filled successfully!", "success");
                } else {
                    showNotification(
                        "Failed to fill form: " + fillResponse.error,
                        "error"
                    );
                }
            } else {
                showNotification(
                    "Failed to detect forms: " + response.error,
                    "error"
                );
            }
        } catch (error) {
            showNotification("Error: " + error.message, "error");
        }
    });

    // Open popup button
    openPopupBtn.addEventListener("click", () => {
        chrome.runtime.sendMessage({ action: "openPopup" });
    });

    // Hide button
    hideBtn.addEventListener("click", () => {
        floatingContainer.style.display = "none";
    });

    // Notification function
    function showNotification(message, type = "success") {
        const notification = document.createElement("div");
        notification.className = `autofill-notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add("show");
        }, 100);

        setTimeout(() => {
            notification.classList.add("hide");
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
})();
