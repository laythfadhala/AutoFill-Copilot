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
    chrome.storage.local.get(["floatingButtonVisible", "floatingButtonTop"], (data) => {
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
            }
            return true;
        });
    });
})();
