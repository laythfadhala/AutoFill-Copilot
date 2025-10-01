document.addEventListener('DOMContentLoaded', () => {
  const countEl = document.getElementById('count');
  const incBtn = document.getElementById('inc');

  function refreshCount() {
    chrome.storage.local.get({ clickCount: 0 }, ({ clickCount }) => {
      countEl.textContent = String(clickCount);
    });
  }

  incBtn.addEventListener('click', () => {
    chrome.storage.local.get({ clickCount: 0 }, ({ clickCount }) => {
      chrome.storage.local.set({ clickCount: clickCount + 1 }, refreshCount);
    });
  });

  refreshCount();
});

