// Floating Button Content Script
(function () {
    console.log("AutoFill Copilot: Floating button script loaded");

    let hideTimeout;
    let isDragging = false;
    let dragOffsetY = 0;

    // Create the floating button container
    const floatingContainer = document.createElement("div");
    floatingContainer.id = "autofill-floating-container";
    floatingContainer.style.display = "none"; // Hide initially to prevent flash
    floatingContainer.innerHTML = `
        <div class="autofill-floating-main-btn">
            <span class="autofill-floating-icon" id="main-icon">+</span>
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

    // Check if floating button should be visible
    chrome.storage.local.get(["floatingButtonVisible"], (data) => {
        if (data.floatingButtonVisible) {
            floatingContainer.style.display = "block";
        } else {
            floatingContainer.style.display = "none";
        }
    });

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

    // Drag functionality
    mainBtn.addEventListener("mousedown", (e) => {
        isDragging = true;
        dragOffsetY = e.clientY - floatingContainer.offsetTop;
        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
        e.preventDefault(); // Prevent text selection
    });

    function onMouseMove(e) {
        if (!isDragging) return;
        let newY = e.clientY - dragOffsetY;
        const maxY = window.innerHeight - floatingContainer.offsetHeight;
        newY = Math.max(0, Math.min(newY, maxY));
        floatingContainer.style.setProperty("top", newY + "px", "important");
        floatingContainer.style.setProperty(
            "transform",
            "translateY(0)",
            "important"
        );
    }

    function onMouseUp() {
        isDragging = false;
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
    }

    // Hover to show submenu
    mainBtn.addEventListener("mouseenter", () => {
        if (isDragging) return;
        clearTimeout(hideTimeout);
        submenu.classList.add("show");
    });

    // Click to toggle submenu
    mainBtn.addEventListener("click", () => {
        if (isDragging) return;
        submenu.classList.toggle("show");
    });

    // Keep submenu shown when hovering over it
    submenu.addEventListener("mouseenter", () => {
        clearTimeout(hideTimeout);
    });

    // Hide submenu after leaving the container
    floatingContainer.addEventListener("mouseleave", () => {
        hideTimeout = setTimeout(() => {
            submenu.classList.remove("show");
        }, 500); // Delay hide by 300ms
    });

    // Fill form button
    fillBtn.addEventListener("click", async () => {
        const mainIcon = document.getElementById("main-icon");
        mainIcon.innerHTML = '<div class="autofill-floating-spinner"></div>';
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
        } finally {
            mainIcon.innerHTML = "+";
        }
    });

    // Open popup button
    openPopupBtn.addEventListener("click", () => {
        chrome.runtime.sendMessage({ action: "openPopup" });
    });

    // Hide button
    hideBtn.addEventListener("click", () => {
        floatingContainer.style.display = "none";
        chrome.storage.local.set({ floatingButtonVisible: false });
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

    // Listen for messages from popup/background
    chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
        if (request.action === "showFloatingButton") {
            floatingContainer.style.display = "block";
            chrome.storage.local.set({ floatingButtonVisible: true });
            sendResponse({ success: true });
        }
        return true;
    });
})();
