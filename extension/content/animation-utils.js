// Animation Utilities
// Injects the pulse ring CSS animation if not already present
window.injectPulseStyle = function () {
    if (!document.head) {
        // If head is not ready, wait for DOMContentLoaded
        document.addEventListener("DOMContentLoaded", window.injectPulseStyle);
        return;
    }
    if (!document.getElementById("autofill-pulse-style")) {
        const style = document.createElement("style");
        style.id = "autofill-pulse-style";
        style.textContent = `
            @keyframes pulse-ring {
                0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
                70% { box-shadow: 0 0 0 5px rgba(59, 130, 246, 0); }
                100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
            }
            .autofill-pulse-ring {
                animation: pulse-ring 1.5s ease-out infinite;
            }
        `;
        document.head.appendChild(style);
    }
};
