document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("booking-form");
  const tabs = document.querySelectorAll(".tab");
  const steps = document.querySelectorAll(".form-step");
  const courseSelect = document.getElementById("course");
  const startDateSelect = document.getElementById("start-date-wrapper");
  const durationSelect = document.getElementById("duration-wrapper");
  const coursePriceElem = document.getElementById("course-price");
  const totalCostElem = document.getElementById("total-cost");
  const submitPaymentButton = document.getElementById("pay-now-button");
  const storedFees = JSON.parse(localStorage.getItem("fees"));

  let allCourses = [];
  let appliedCouponCode = [];
  let originalCoursePrice = 0.0;
  // Sidebar rows mapped to their respective steps
  const stepRows = {
    1: ["booking-selected-course-row", "booking-accommodation-fee-row"],
    2: ["booking-registration-fee-row"],
    3: ["booking-transport-cost-row"],
  };
  // Check if pluginCourseId is defined and fetch the specific course
  if (typeof bookingData.currentCourseId !== "undefined") {
    fetchCourses(bookingData.currentCourseId);
  }

  function markCompleted(step) {
    const tab = tabs[step - 1]; // Get the tab corresponding to the step
    tab.classList.add("completed");
  }

  // Function to show the current step
  function showStep(step) {
    steps.forEach((stepElement, index) => {
      stepElement.style.display = index === step - 1 ? "block" : "none";
    });

    tabs.forEach((tab) => tab.classList.remove("active"));
    tabs[step - 1].classList.add("active");
    if (step === 4) {
      removeSidebar();
    }
  }

  // Function to update the visibility of sidebar rows
  function updateSidebar(step) {
    // Iterate through all rows and toggle visibility based on the step
    document.querySelectorAll(".booking-row").forEach((row) => {
      row.style.display = "none"; // Initially hide all rows
    });

    // Loop through all steps up to the current step
    for (let currentStep = 1; currentStep <= step; currentStep++) {
      if (stepRows[currentStep]) {
        stepRows[currentStep].forEach((rowId) => {
          const row = document.querySelector(`#${rowId}`);
          if (row) row.style.display = "flex"; // Show rows from this step
        });
      }
    }

    // Always keep the total row visible
    const totalRow = document.querySelector("#booking-total-row");
    if (totalRow) totalRow.style.display = "flex";
  }

  // Function to validate all required fields in the current step
  function validateStep(step) {
    const currentStep = document.querySelector(`.step-${step}`);
    const requiredFields = currentStep.querySelectorAll("[required");
    let isValid = true;

    requiredFields.forEach((field) => {
      if (field.type === "radio") {
        // Special handling for radio buttons: Ensure one is selected
        const radioGroup = currentStep.querySelectorAll(`input[name="${field.name}"]`);
        const isChecked = Array.from(radioGroup).some((radio) => radio.checked);
        if (!isChecked) {
          isValid = false;
          currentStep.querySelector(`#${field.name}-error`)?.classList.add("visible");
        } else {
          currentStep.querySelector(`#${field.name}-error`)?.classList.remove("visible");
        }
      } else if (!field.value.trim()) {
        isValid = false;
        field.style.borderColor = "red"; // Highlight empty fields
      } else {
        field.style.borderColor = ""; // Reset if valid
      }
    });
    return isValid;
  }

  // Next and Previous button handling
  document.querySelectorAll(".next-button").forEach((button) => {
    button.addEventListener("click", function () {
      const currentStep = parseInt(this.getAttribute("data-next-step")) - 1;

      if (validateStep(currentStep)) {
        const nextStep = parseInt(this.getAttribute("data-next-step"));
        showStep(nextStep);
        markCompleted(currentStep);
        updateSidebar(nextStep);
        if (nextStep === 4) {
          populateReviewTab();
        }
        // Scroll to the tabs at the top of the form
        const bookingFormContainer = document.querySelector(".booking-form"); // Replace with the correct tabs container selector
        if (bookingFormContainer) {
          bookingFormContainer.scrollIntoView({ behavior: "smooth" }); // Smooth scrolling to the tabs
        }
      } else {
        alert("Please fill in all required fields before proceeding.");
      }
    });
  });

  document.querySelectorAll(".prev-button").forEach((button) => {
    button.addEventListener("click", function () {
      const prevStep = parseInt(this.getAttribute("data-prev-step"));
      showStep(prevStep);
      updateSidebar(prevStep);
      // Scroll to the tabs at the top of the form
      const bookingFormContainer = document.querySelector(".booking-form"); // Replace with the correct tabs container selector
      if (bookingFormContainer) {
        bookingFormContainer.scrollIntoView({ behavior: "smooth" }); // Smooth scrolling to the tabs
      }
    });
  });

  // Tab handling
  tabs.forEach((tab, index) => {
    tab.addEventListener("click", function () {
      const targetStep = index + 1;

      // Validate the current step before allowing tab switch
      const currentStep = Array.from(tabs).findIndex((t) => t.classList.contains("active")) + 1;
      if (targetStep > currentStep && !validateStep(currentStep)) {
        alert("Please fill in all required fields before switching tabs.");
        return;
      }
      showStep(targetStep);
      updateSidebar(targetStep);
    });
  });

  showStep(1);
  updateSidebar(1);

  // Function to populate dropdown with data from backend
  async function fetchCourses(courseId) {
    try {
      const response = await fetch(`${bookingData.restApiUrl}courses/?course_id=${courseId}`, {
        method: "GET",
        headers: {
          "Content-type": "application/json",
        },
      });
      const data = await response.json();
      if (data.success) {
        allCourses = [data.course];
        populateDropdown(courseSelect, allCourses, "Select a course");
      }
    } catch (error) {
      console.error("Error fetching course data:", error);
    }
  }

  // Function to handle course selection and fetch corresponding start dates and durations
  async function fetchCourseDetails(courseId) {
    try {
      const response = await fetch(
        `${bookingData.restApiUrl}course-details/?course_id=${courseId}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      const data = await response.json();

      if (data.success) {
        populateDropdown(startDateSelect, data.start_dates, "Select a start date");
        populateDropdown(durationSelect, data.durations, "Select duration (weeks)");
      } else {
        console.error("Error fetching course details:", data.message);
      }
    } catch (error) {
      console.error("Error fetching course details:", error);
    }
  }

  // Function to populate a dropdown
  function populateDropdown(selectElement, items, placeholder) {
    clearDropdown(selectElement);

    // Add placeholder option
    const placeholderOption = document.createElement("option");
    placeholderOption.textContent = placeholder;
    placeholderOption.value = "";
    selectElement.appendChild(placeholderOption);

    items.forEach((item) => {
      const option = document.createElement("option");
      if (item.id && item.name) {
        option.value = item.id;
        option.textContent = item.name;
      } else if (item.id && item.duration_weeks) {
        option.value = item.id;
        option.textContent = item.duration_weeks ? `${item.duration_weeks} Weeks` : item.name;
        option.setAttribute("data-duration-weeks", item.duration_weeks);
      } else {
        option.value = item; // For start dates or other plain values
        option.textContent = item;
      }
      selectElement.appendChild(option);
    });
  }

  //Function to handle course selection and update sidebar and dropdowns
  async function handleCourseSelection() {
    const selectedCourseId = courseSelect.value;
    if (selectedCourseId) {
      fetchCourseDetails(selectedCourseId);

      const selectedCourse = allCourses.find((course) => course.id === selectedCourseId);
      if (selectedCourse) {
        document.getElementById("selected-course").textContent = selectedCourse.name;
      }
      resetAccommodationFee();
      await updatePriceOnSelection();
    } else {
      // Clear and reset dependent dropdowns with placeholders
      clearDropdown(startDateSelect, "Select a start date");
      clearDropdown(durationSelect, "Select duration (weeks)");
      // Optionally clear sidebar as well
      document.getElementById("selected-course").textContent = "None";
      document.getElementById("course-price").textContent = "$0.00";
      // selectedCourseCost = 0;
      resetAccommodationFee();
    }
    // updateTotalCost();
  }

  // Function to fetch price from the server and update the sidebar
  async function updatePriceOnSelection() {
    const selectedCourseId = courseSelect.value;
    const selectedDurationId = durationSelect.value;

    if (!selectedCourseId || !selectedDurationId) {
      // Clear the price if no valid selection is made
      document.getElementById("course-price").textContent = "$0.00";
      return;
    }

    try {
      const response = await fetch(
        `${bookingData.restApiUrl}get-course-price?course_id=${selectedCourseId}&duration_id=${selectedDurationId}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
            // Include the nonce for authentication
          },
        }
      );

      const data = await response.json();

      if (data.success) {
        const price = parseFloat(data.price);
        setCoursePrice(price);
        originalCoursePrice = price;
        updateTotalCost();
      } else {
        console.error("Error fetching price:", data.message);
        setCoursePrice(0.0); // Fallback if error
        originalCoursePrice = 0.0;
      }
    } catch (error) {
      console.error("Error fetching price:", error);
      setCoursePrice(0.0); // Fallback if error
      originalCoursePrice = 0.0;
    }
  }

  // Helper function to update the course price in the sidebar
  function setCoursePrice(price) {
    const coursePriceElem = document.getElementById("course-price");
    coursePriceElem.textContent = `$${price.toFixed(2)}`;

    // Save the original price as a data attribute for further calculations
    if (!originalCoursePrice) {
      originalCoursePrice = price;
    }
  }

  // Function to clear dropdown
  function clearDropdown(selectElement, placeholder) {
    while (selectElement.firstChild) {
      selectElement.removeChild(selectElement.firstChild);
    }
    if (placeholder) {
      const placeholderOption = document.createElement("option");
      placeholderOption.textContent = placeholder;
      placeholderOption.value = "";
      selectElement.appendChild(placeholderOption);
    }
  }

  // Add event listener for course selection change
  courseSelect.addEventListener("change", handleCourseSelection);

  // Event listener for duration selection change (if applicable)
  durationSelect.addEventListener("change", updatePriceOnSelection);

  const countrySelect = document.getElementById("country");
  const nationalitySelect = document.getElementById("nationality");

  // Function to populate country dropdown
  function populateCountryDropdown() {
    if (window.countriesList && Array.isArray(window.countriesList)) {
      window.countriesList.forEach((item) => {
        // Add countries to the country dropdown
        const countryOption = document.createElement("option");
        countryOption.value = item.name;
        countryOption.textContent = item.name;
        countrySelect.appendChild(countryOption);
      });
    } else {
      console.error("Countries list not found or not loaded correctly");
    }
  }

  // Function to populate the nationality dropdown
  function populateNationalityDropdown() {
    if (window.countriesList && Array.isArray(window.countriesList)) {
      window.countriesList.forEach((item) => {
        // Add nationalities to the nationality dropdown
        const nationalityOption = document.createElement("option");
        nationalityOption.value = item.nationality;
        nationalityOption.textContent = item.nationality;
        nationalitySelect.appendChild(nationalityOption);
      });
    } else {
      console.error("Countries list not found or not loaded correctly");
    }
  }

  populateCountryDropdown();
  populateNationalityDropdown();

  const transportRadios = document.querySelectorAll('input[name="transport"]');
  const transportCostElement = document.getElementById("transport-cost");
  const transportCostDisplay = document.getElementById("transport-cost-display");

  // Fetch Transport, accommodation and administration fee
  async function fetchTransportCost() {
    try {
      const response = await fetch(`${bookingData.restApiUrl}fees`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          // Include nonce for authentication
        },
      });

      const data = await response.json();

      if (data.success) {
        allFees = data.fees;
        localStorage.setItem("fees", JSON.stringify(allFees)); // Store fees in localStorage
        // console.log("Fees stored in localStorage:", localStorage.getItem("fees"));
      } else {
        console.error("Failed to retrieve fees:", data.message);
      }
    } catch (error) {
      console.error("Error fetching fees:", error);
    }
  }

  fetchTransportCost();

  if (storedFees && storedFees.transportation_cost) {
    // Update the transport cost display
    const transportCost = parseFloat(storedFees.transportation_cost).toFixed(2);
    transportCostDisplay.textContent = `$${transportCost}`;
  }

  // Function to update transport cost in the sidebar
  function updateTransportCost() {
    let transportCost = 0;
    const selectedTransport = document.querySelector('input[name="transport"]:checked');
    if (selectedTransport) {
      transportCost = storedFees ? parseFloat(storedFees.transportation_cost) : 0;
    } else {
      console.warn("No transport option selected");
    }
    if (transportCostElement) {
      transportCostElement.textContent = `$${transportCost.toFixed(2)}`;
    }
    updateTotalCost();
  }

  // Add event listeners for transport options
  transportRadios.forEach((radio) => {
    radio.addEventListener("change", updateTransportCost);
  });

  // Update transport cost on page load (default selection)
  updateTransportCost();

  // Registration fee
  const emailField = document.getElementById("email");
  const emailMessage = document.getElementById("email-message"); // Element for showing messages

  function debounce(func, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  // Check Email api call
  async function checkEmail(email) {
    if (email) {
      try {
        const response = await fetch(`${bookingData.restApiUrl}check-email`, {
          method: "POST", // Using GET method for simplicity
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ email: email }), // Sending email as JSON in the body
        });

        if (!response.ok) {
          const errorData = await response.text(); // Get the response body as text
          throw new Error(`HTTP error! Status: ${response.status}, Response: ${errorData}`);
        }

        const jsonData = await response.json();

        if (jsonData.success) {
          const registrationFee = jsonData.registrationFee;
          updateSidebarWithRegistrationFee(registrationFee); // Update sidebar fee
          updateEmailMessage(registrationFee); // Show/hide email message based on fee
        } else {
          console.error("Email check failed:", jsonData.message);
          updateSidebarWithRegistrationFee(0);
          updateEmailMessage(null);
        }
      } catch (error) {
        console.error("Error during email check:", error);
        // Reset sidebar and hide message in case of an error
        updateSidebarWithRegistrationFee(0);
        updateEmailMessage(null);
      }
    }
  }

  // Debounced email check function
  const debouncedCheckEmail = debounce(function () {
    const email = emailField.value.trim();
    checkEmail(email);
  }, 1000); // Delay of 500ms (adjust as needed)

  // Add event listener to email input field
  emailField.addEventListener("input", debouncedCheckEmail);

  function updateSidebarWithRegistrationFee(fee) {
    const sidebar = document.querySelector(".sidebar");
    const feeElem = sidebar.querySelector("#registration-fee");
    const formattedFee = parseFloat(fee).toFixed(2);
    feeElem.textContent = `$${formattedFee}`;
    updateTotalCost();
  }

  // Function to handle the email message
  function updateEmailMessage(fee) {
    const numericFee = parseFloat(fee);
    if (numericFee === 0) {
      emailMessage.style.display = "block";
      emailMessage.textContent = "As a returning student, your registration fee has been waived!";
    } else {
      emailMessage.style.display = "none"; // Hide the message if fee isn't waived
    }
  }

  // Accommodation Fee
  function calculateAccommodationFee(durationWeeks) {
    const accommodationCost = storedFees ? parseFloat(storedFees.accommodation_cost) : 0;
    return durationWeeks * accommodationCost;
  }

  function updateSidebarWithAccommodationFee(duration) {
    const sidebar = document.querySelector(".sidebar");
    const feeElem = sidebar.querySelector("#accommodation-fee");
    const fee = calculateAccommodationFee(duration);
    const formattedFee = parseFloat(fee).toFixed(2);
    feeElem.textContent = `$${formattedFee}`;

    updateTotalCost();
  }

  function resetAccommodationFee() {
    const accommodationFeeElem = document.querySelector("#accommodation-fee");

    accommodationFeeElem.textContent = "$0.00";
    updateTotalCost(); // Recalculate total cost to reflect the reset
  }

  // Trigger accommodation fee update on duration change
  durationSelect.addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];
    // Check if a valid option is selected
    if (selectedOption && selectedOption.hasAttribute("data-duration-weeks")) {
      const durationWeeks = parseInt(selectedOption.getAttribute("data-duration-weeks"), 10);
      updateSidebarWithAccommodationFee(durationWeeks);
    } else {
      resetAccommodationFee(); // Reset fee to 0.00 if no valid duration is selected
    }
  });

  function updateTotalCost() {
    const registrationFeeElem = document.querySelector("#registration-fee");
    const coursePrice =
      parseFloat(document.querySelector("#course-price").textContent.replace("$", "")) || 0;
    const accommodationFee =
      parseFloat(document.querySelector("#accommodation-fee").textContent.replace("$", "")) || 0;
    const transportCost = parseFloat(transportCostElement.textContent.replace("$", "")) || 0;
    const registrationFee = parseFloat(registrationFeeElem.textContent.replace("$", "")) || 0.0;
    const totalCost = coursePrice + transportCost + registrationFee + accommodationFee;
    totalCostElem.textContent = `$${totalCost.toFixed(2)}`;
    populateReviewTab();
  }

  // Event listener to show coupons
  document.getElementById("show-coupons").addEventListener("click", async function () {
    try {
      const response = await fetch(`${bookingData.restApiUrl}active-coupons`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      });

      const data = await response.json();

      if (data.success) {
        const couponList = data.data
          .map((coupon) => {
            // Skip rendering the apply button if coupon is already applied
            const isApplied = appliedCouponCode.includes(coupon.code);
            return `
              <li>
                <strong>${coupon.code}</strong>: 
                ${
                  coupon.discount_type === "fixed"
                    ? `$${coupon.discount_value} off`
                    : `${coupon.min_discount_percentage}% - ${coupon.max_discount_percentage}% off`
                }
                (Expires: ${coupon.expiry_date})
                ${
                  !isApplied
                    ? `
                  <button 
                    class="apply-coupon-btn" 
                    data-code="${coupon.code}" 
                    data-type="${coupon.discount_type}" 
                    data-value="${coupon.discount_value || ""}" 
                    data-min-price="${coupon.min_price_range || ""}" 
                    data-max-price="${coupon.max_price_range || ""}" 
                    data-min-discount="${coupon.min_discount_percentage || ""}" 
                    data-max-discount="${coupon.max_discount_percentage || ""}">
                    Apply
                  </button>
                `
                    : `
                  <span>Coupon Applied</span>
                `
                }
              </li>
            `;
          })
          .join("");

        document.getElementById("coupon-list").innerHTML = couponList;
        document.getElementById("coupon-modal").style.display = "block";
      } else {
        alert("No active coupons available.");
      }
    } catch (error) {
      console.error("Error fetching coupons:", error);
      alert("Error fetching coupons.");
    }
  });

  // Function to get the original price from the data attribute
  function getOriginalCoursePrice() {
    return originalCoursePrice;
  }

  // Function to apply a selected coupon
  function applyCoupon(
    couponCode,
    discountType,
    discountValue,
    minPrice,
    maxPrice,
    minDiscount,
    maxDiscount
  ) {
    const couponInput = document.getElementById("coupon_code");
    couponInput.value = couponCode; // Fill the input field with the selected coupon code

    if (appliedCouponCode === couponCode) {
      alert("This coupon has already been applied");
      return;
    }

    const originalPrice = getOriginalCoursePrice();
    let discountedPrice = originalPrice;

    // Calculate discounted price
    if (discountType === "fixed") {
      discountedPrice = Math.max(originalPrice - discountValue, 0); // Ensure non-negative price
    } else if (discountType === "percentage") {
      discountedPrice = applyPercentageCoupon(
        originalPrice,
        minPrice,
        maxPrice,
        minDiscount,
        maxDiscount
      );
    }

    // Update the course price with the discounted price
    setCoursePrice(discountedPrice);
    updateTotalCost();
    appliedCouponCode = couponCode;
    removeCouponBtn(couponCode);
    // Notify the user
    alert(`Coupon applied successfully! New price: $${discountedPrice.toFixed(2)}`);
  }

  function removeCouponBtn(couponCode) {
    // Find the button for the applied coupon
    const applyButton = document.querySelector(`.apply-coupon-btn[data-code="${couponCode}"]`);

    if (applyButton) {
      const applyButton = document.querySelector(`.apply-coupon-btn[data-code="${couponCode}"]`);
      if (applyButton) {
        applyButton.remove();
      } else {
        console.warn(`Button for coupon code "${couponCode}" not found.`);
      }
    }
  }

  // Event listener for coupon modal apply buttons
  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("apply-coupon-btn")) {
      // Fetch data attributes from the clicked button
      const couponCode = event.target.getAttribute("data-code"); // Fetch coupon code
      const discountType = event.target.getAttribute("data-type"); // Fetch discount type
      const discountValue = parseFloat(event.target.getAttribute("data-value")) || 0.0; // Fetch discount value
      const minPrice = parseFloat(event.target.getAttribute("data-min-price")) || 0.0;
      const maxPrice = parseFloat(event.target.getAttribute("data-max-price")) || 0.0;
      const minDiscount = parseFloat(event.target.getAttribute("data-min-discount")) || 0.0;
      const maxDiscount = parseFloat(event.target.getAttribute("data-max-discount")) || 0.0;

      applyCoupon(
        couponCode,
        discountType,
        discountValue,
        minPrice,
        maxPrice,
        minDiscount,
        maxDiscount
      );
      document.getElementById("coupon-modal").style.display = "none"; // Close the modal
    }
  });

  // Function to calculate the discount percentage
  function calculateDiscountPercentage(
    selectedPrice,
    minPrice,
    maxPrice,
    minDiscount,
    maxDiscount
  ) {
    if (selectedPrice < minPrice) selectedPrice = minPrice;
    if (selectedPrice > maxPrice) selectedPrice = maxPrice;

    const discountPercentage =
      minDiscount +
      ((selectedPrice - minPrice) / (maxPrice - minPrice)) * (maxDiscount - minDiscount);

    const finalDiscountPercentage = Math.min(
      Math.max(discountPercentage, minDiscount),
      maxDiscount
    );

    return finalDiscountPercentage.toFixed(2);
  }

  // Function to apply a percentage-based coupon
  function applyPercentageCoupon(selectedPrice, minPrice, maxPrice, minDiscount, maxDiscount) {
    const discountPercentage = calculateDiscountPercentage(
      selectedPrice,
      minPrice,
      maxPrice,
      minDiscount,
      maxDiscount
    );

    const discountAmount = (selectedPrice * discountPercentage) / 100;
    const discountedPrice = Math.max(selectedPrice - discountAmount, 0); // Ensure non-negative price

    return discountedPrice;
  }

  // Close the modal
  document.querySelector(".coupon-close-modal").addEventListener("click", function () {
    document.getElementById("coupon-modal").style.display = "none";
  });

  function populateReviewTab() {
    // Populate user details
    document.querySelector("#review-name span").textContent = document.querySelector("#name").value;
    document.querySelector("#review-contact span").textContent =
      document.querySelector("#contact_no").value;
    document.querySelector("#review-email span").textContent =
      document.querySelector("#email").value;
    document.querySelector("#review-address span").textContent = `${
      document.querySelector("#address").value
    }, 
        ${document.querySelector("#city").value}, ${document.querySelector("#post_code").value}, 
        ${document.querySelector("#country").value}`;

    // Populate booking cost breakdown
    // Fetch the selected duration's data-duration-weeks value
    const selectedOption = durationSelect.options[durationSelect.selectedIndex];
    const durationWeeks = selectedOption
      ? selectedOption.getAttribute("data-duration-weeks")
      : null;
    document.querySelector("#review-selected-course span").textContent =
      document.querySelector("#selected-course").textContent;
    document.querySelector("#review-course-price span").textContent =
      document.querySelector("#course-price").textContent;
    document.querySelector("#review-registration-fee span").textContent =
      document.querySelector("#registration-fee").textContent;
    // document.querySelector("#review-accommodation-fee span").textContent =
    //   document.querySelector("#accommodation-fee").textContent;
    // document.querySelector("#review-transport-cost span").textContent =
    //   document.querySelector("#transport-cost").textContent;
    document.querySelector("#review-total-cost span").textContent =
      document.querySelector("#total-cost").textContent;

    // Populate start date and duration
    document.querySelector("#review-course-start-date span").textContent = startDateSelect.value;
    document.querySelector("#review-course-duration span").textContent = durationWeeks
      ? `${durationWeeks} Weeks`
      : "Not selected";
  }

  function removeSidebar() {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar) {
      sidebar.style.display = "none";
    }
  }

  function displayBookingDetails(bookingDetails) {
    const successConstainer = document.querySelector(".step-5");
    if (successConstainer) {
      successConstainer.innerHTML = `
      <h3>Payment Successful!</h3>
      <p>Thank you for your booking.</p>
      <p><strong>Course:</strong> ${bookingDetails.courseName}</p>
      <p><strong>Registration Fee:</strong> $${bookingDetails.registrationFee.toFixed(2)}</p>
      <p><strong>Accommodation Fee:</strong> $${bookingDetails.accommodationFee.toFixed(2)}</p>
      <p><strong>Total Paid:</strong> $${bookingDetails.amount.toFixed(2)}</p>
      <p><strong>Email:</strong> ${bookingDetails.email}</p>
      <p>Your booking ID is <strong>${bookingDetails.bookingId}</strong>.</p>
      <div class="button-container"><button onclick="window.location.reload()">Back to Home</button></div>
    `;
    }
  }

  // Check if Stripe is loaded
  if (typeof Razorpay === "undefined") {
    console.error("Razorpay not loaded");
    return;
  }

  // Initialize Stripe with the publishable key from localized data
  const razorpayKey = bookingData.razorpayKey;

  // Form submission handling
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Validate form data before proceeding
    const requiredFields = ["name", "email", "course", "start_date", "duration"];
    for (const field of requiredFields) {
      if (!form[field].value) {
        return;
      }
    }

    // Validate transport selection
    const transportOptions = document.querySelectorAll('input[name="transport"]');
    const isTransportSelected = Array.from(transportOptions).some((option) => option.checked);

    if (!isTransportSelected) {
      alert("Please select a transport option before submitting.");
      return;
    }

    // Get nonce securely
    // const nonce = form.elements.namedItem("nonce").value;
    // console.log(nonce);
    // Get fee details and calculate the total amount
    const accommodationFee =
      parseFloat(document.querySelector("#accommodation-fee").textContent.replace("$", "")) || 0.0;
    const totalAmount =
      parseFloat(document.querySelector("#total-cost").textContent.replace("$", "")) * 100 || 0.0; // Convert to cents
    const transportationFee =
      parseFloat(document.querySelector("#transport-cost").textContent.replace("$", "")) || 0.0;
    const registrationFee =
      parseFloat(document.querySelector("#registration-fee").textContent.replace("$", "")) || 0.0;
    const couponCode = document.querySelector("#coupon_code").value.trim();

    const selectedDurationOption = form["duration"].options[form["duration"].selectedIndex];
    const durationWeeks = selectedDurationOption
      ? parseInt(selectedDurationOption.getAttribute("data-duration-weeks"), 10)
      : null;

    const params = {
      name: form["name"].value,
      email: form["email"].value,
      contact_no: form["contact_no"].value,
      dob: form["dob"].value,
      address: form["address"].value,
      city: form["city"].value,
      post_code: form["post_code"].value,
      nationality: form["nationality"].value,
      country: form["country"].value,
      course: form["course"].value,
      start_date: form["start_date"].value,
      duration: durationWeeks,
      english_level: form["english_level"].value,
      transport: form["transport"].value,
      accommodationFee: accommodationFee,
      transportationFee: transportationFee,
      totalAmount: totalAmount,
      registrationFee: registrationFee,
      couponCode: couponCode || null,
      contact_type: form["contact_type"].value, // Correctly map emergency contact type
      emergency_name: form["emergency_name"].value, // Correctly map emergency contact name
      emergency_email: form["emergency_email"].value, // Correctly map emergency contact email
      emergency_contact_no: form["emergency_contact_no"].value, // Correctly map emergency contact phone
      // Include the nonce
    };

    try {
      // Step 1: Create Razorpay order
      const orderResponse = await fetch(`${bookingData.restApiUrl}create-order`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(params),
      });

      const orderData = await orderResponse.json();
      if (!orderData.orderId) {
        console.error("No order ID returned from backend");
        return;
      }
      const razorpayOrderId = orderData.orderId;
      const tempId = orderData.tempId;

      // Step 2: Razorpay Checkout
      const razorpayOptions = {
        key: bookingData.razorpayKey,
        amount: params.totalAmount,
        currency: "USD",
        order_id: razorpayOrderId,
        handler: async function (response) {
          // Step 3: Verify Payment
          const verifyResponse = await fetch(`${bookingData.restApiUrl}verify-payment`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              razorpayPaymentId: response.razorpay_payment_id,
              razorpayOrderId: response.razorpay_order_id,
              razorpaySignature: response.razorpay_signature,
              tempId: tempId,
            }),
          });

          const verifyData = await verifyResponse.json();
          if (verifyData.success) {
            markCompleted(4);
            showStep(5);
            removeSidebar();
            displayBookingDetails(verifyData.bookingDetails);
          } else {
            alert("Payment verification failed.");
          }
        },
      };

      const rzp = new Razorpay(razorpayOptions);
      rzp.open();
    } catch (error) {
      console.error("Error:", error.message);
    }
  });
});
