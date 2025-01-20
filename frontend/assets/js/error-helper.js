// Function to show the error modal
function showErrorModal(message) {
  console.log("Showing error modal:", message);
  const modal = document.getElementById("error-modal");
  const modalContent = modal.querySelector(".modal-content");
  const modalMessage = modalContent.querySelector("#error-modal-message"); // Updated to target the message element
  const overlay = document.getElementById("modal-overlay");
  if (modal && modalContent && modalMessage) {
    modalMessage.innerHTML = message; // Set the error message
    modal.classList.add("visible"); // Show the modal
    overlay.style.display = "block";
  }

  // Close the modal when clicking the close button
  const closeButton = modal.querySelector(".close");
  closeButton.addEventListener("click", () => {
    modal.classList.remove("visible"); // Hide the modal when clicked
    overlay.style.display = "none";
  });
}
