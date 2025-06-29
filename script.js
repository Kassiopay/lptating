function showMessage() {
  var login = document.getElementById("loginform");
  var password = document.getElementById("passwordform");
  if (!login.value || !password.value) {
    alert("Пожалуйста, заполните все поля");
    return false;
  }
  return true;
}

// Enhanced privacy modal implementation
function initPrivacyModal() {
  const modal = document.getElementById("privacyModal");
  const link = document.getElementById("privacyPolicyLink");
  
  if (!modal || !link) return false;

  const closeBtn = modal.querySelector(".close");
  
  link.addEventListener("click", function(e) {
    e.preventDefault();
    modal.style.display = "block";
  });

  closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
  });

  window.addEventListener("click", function(e) {
    if (e.target === modal) modal.style.display = "none";
  });

  return true;
}

// Initialize with retry logic
function initModals() {
  if (!initPrivacyModal()) setTimeout(initModals, 300);
}

// Start initialization
if (document.readyState === "complete") {
  initModals();
} else {
  document.addEventListener("DOMContentLoaded", initModals);
}
