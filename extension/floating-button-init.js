// Floating Button Initialization Script
import browser from "webextension-polyfill";

async function initializeFloatingButton() {
    try {
        await chrome.storage.local.set({ floatingButtonVisible: true });
        // The floating button will show automatically when storage changes
    } catch (error) {
        console.error("Failed to show floating button:", error);
    }
}
