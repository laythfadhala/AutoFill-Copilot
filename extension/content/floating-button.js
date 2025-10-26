// Floating Button Content Script
import browser from "webextension-polyfill";

(function () {
    console.log("AutoFill Copilot: Floating button script loaded");

    let isDragging = false;
    let dragOffsetY = 0;

    // Create the floating button container
    const floatingContainer = document.createElement("div");
    floatingContainer.id = "autofill-floating-container";
    floatingContainer.style.display = "none"; // Hide initially to prevent flash
    floatingContainer.innerHTML = `
        <div class="autofill-floating-main-btn">
            <div class="autofill-floating-main-area">
                <span class="autofill-floating-icon" id="main-icon">✨</span>
            </div>
            <div class="autofill-floating-drag-handle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <button class="autofill-floating-hide-btn" id="hide-btn">X</button>
        </div>
    `;

    // Check if floating button should be visible and append accordingly
    chrome.storage.local.get(
        ["floatingButtonVisible", "floatingButtonTop", "sidePanelOpen"],
        (data) => {
            if (data.floatingButtonVisible) {
                floatingContainer.style.display = "block";
            }
            // Else stays hidden
            if (data.floatingButtonTop) {
                floatingContainer.style.setProperty("top", data.floatingButtonTop, "important");
                floatingContainer.style.setProperty("transform", "translateY(0)", "important");
            }
            document.body.appendChild(floatingContainer);
            console.log("AutoFill Copilot: Floating button injected");

            // Check if side panel was open before reload
            if (data.sidePanelOpen) {
                showSidePanel();
            }

            // Get elements
            const mainBtn = floatingContainer.querySelector(".autofill-floating-main-btn");
            const mainArea = floatingContainer.querySelector(".autofill-floating-main-area");
            const dragHandle = floatingContainer.querySelector(".autofill-floating-drag-handle");
            const hideBtn = floatingContainer.querySelector("#hide-btn");

            // Drag functionality
            dragHandle.addEventListener("mousedown", (e) => {
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
                floatingContainer.style.setProperty("transform", "translateY(0)", "important");
            }

            function onMouseUp() {
                isDragging = false;
                // Save position
                chrome.storage.local.set({
                    floatingButtonTop: floatingContainer.style.top,
                });
                document.removeEventListener("mousemove", onMouseMove);
                document.removeEventListener("mouseup", onMouseUp);
            }

            // Hover to show hide button
            mainArea.addEventListener("mouseenter", () => {
                if (isDragging) return;
                hideBtn.classList.add("visible");
            });

            // Click to open side panel
            mainArea.addEventListener("click", () => {
                if (isDragging) return;
                chrome.runtime.sendMessage({ action: "openSidePanel" });
            });

            // Hide hide button after leaving the main button
            mainBtn.addEventListener("mouseleave", () => {
                hideBtn.classList.remove("visible");
            });

            // Hide button
            hideBtn.addEventListener("click", () => {
                floatingContainer.style.display = "none";
                chrome.storage.local.set({ floatingButtonVisible: false });
            });

            // Listen for messages from popup/background
            chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
                if (request.action === "showFloatingButton") {
                    floatingContainer.style.display = "block";
                    chrome.storage.local.set({
                        floatingButtonVisible: true,
                    });
                    sendResponse({ success: true });
                } else if (request.action === "showSidePanel") {
                    showSidePanel();
                    sendResponse({ success: true });
                }
                return true;
            });

            // Listen for storage changes to show/hide floating button
            chrome.storage.onChanged.addListener((changes, namespace) => {
                if (namespace === "local" && changes.floatingButtonVisible) {
                    if (changes.floatingButtonVisible.newValue) {
                        floatingContainer.style.display = "block";
                    } else {
                        floatingContainer.style.display = "none";
                    }
                }
            });
        }
    );
})();

// Function to show the side panel
function showSidePanel() {
    // Check if panel already exists
    let panel = document.getElementById("autofill-side-panel");
    if (panel) {
        panel.classList.add("show");
        return;
    }

    // Hide the floating button
    const floatingContainer = document.getElementById("autofill-floating-container");
    if (floatingContainer) {
        floatingContainer.style.display = "none";
    }

    // Create the side panel container
    panel = document.createElement("div");
    panel.id = "autofill-side-panel";
    panel.innerHTML = `
        <button class="panel-close-btn" id="close-panel-btn">×</button>
        <iframe src="${chrome.runtime.getURL(
            "popup.html"
        )}" style="width: 100%; height: 100vh; border: none;" scrolling="no"></iframe>
    `;

    document.body.appendChild(panel);

    // Show the panel
    setTimeout(() => {
        panel.classList.add("show");
    }, 10);

    // Set storage flag
    chrome.storage.local.set({ sidePanelOpen: true });

    // Close button functionality
    const closeBtn = panel.querySelector("#close-panel-btn");
    closeBtn.addEventListener("click", () => {
        closeSidePanel();
    });
}

// Function to close the side panel
function closeSidePanel() {
    const panel = document.getElementById("autofill-side-panel");
    const floatingContainer = document.getElementById("autofill-floating-container");

    if (panel) {
        panel.classList.remove("show");
        setTimeout(() => {
            panel.remove();
        }, 300);
    }

    // Show the floating button again
    if (floatingContainer) {
        floatingContainer.style.display = "block";
    }

    // Clear storage flag
    chrome.storage.local.set({ sidePanelOpen: false });
}
