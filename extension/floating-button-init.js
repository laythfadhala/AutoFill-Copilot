// Floating Button Initialization Script
async function initializeFloatingButton() {
    try {
        await chrome.storage.local.set({ floatingButtonVisible: true });
        const [tab] = await chrome.tabs.query({
            active: true,
            currentWindow: true,
        });
        if (tab) {
            chrome.tabs.sendMessage(tab.id, { action: "showFloatingButton" });
        }
    } catch (error) {
        console.error("Failed to show floating button:", error);
    }
}
