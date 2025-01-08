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
  let originalCoursePrice = 0.0;
  let discountAmount = 0;
  let couponData = [];
  let appliedCoupon = null;

  // Sidebar rows mapped to their respective steps
  const stepRows = {
    1: ["booking-selected-course-row", "booking-accommodation-fee-row"],
    2: ["booking-registration-fee-row"],
    3: ["booking-transport-cost-row"],
  };

  document.querySelectorAll("label").forEach(function (label) {
    const associatedId = label.getAttribute("for");
    if (associatedId) {
      const element = document.getElementById(associatedId);
      // Check for 'required' attribute on associated form controls
      if (element && element.hasAttribute("required")) {
        const asterisk = document.createElement("span");
        asterisk.textContent = " *";
        asterisk.className = "required-asterisk";
        label.appendChild(asterisk);
      }
    }
  });

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
      showCoupons();
    }
    if (step === 5) {
      removeSidebar();
      populateConfirmationTab();
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

        if (nextStep === 5) {
          populateConfirmationTab();
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
      if (targetStep === 5) {
        populateConfirmationTab();
      }
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

        // Directly populate the course dropdown
        const courseSelect = document.querySelector("#course");
        populateDropdown(courseSelect, allCourses, "Select a course", allCourses[0].id);
        handleCourseSelection();
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
  function populateDropdown(selectElement, items, placeholder, selectedValue) {
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
      } else if (isValidDate(item)) {
        // Handle start dates
        option.value = item; // Assuming `item` is in ISO 8601 format (YYYY-MM-DD)
        option.textContent = formatDate(item); // Use your formatDate helper
      } else {
        option.value = item; // For start dates or other plain values
        option.textContent = item;
      }
      // Set selected option based on the provided selectedValue
      if (option.value === selectedValue) {
        option.selected = true;
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
      console.log(selectedCourse);
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
      document.getElementById("course-price").textContent = "$ 0.00";
      resetAccommodationFee();
    }
  }

  // Function to fetch price from the server and update the sidebar
  async function updatePriceOnSelection() {
    const selectedCourseId = courseSelect.value;
    const selectedDurationId = durationSelect.value;

    if (!selectedCourseId || !selectedDurationId) {
      // Clear the price if no valid selection is made
      document.getElementById("course-price").textContent = "$ 0.00";
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
    coursePriceElem.textContent = `$ ${price.toFixed(2)}`;
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
    transportCostDisplay.textContent = `$ ${transportCost}`;
  }

  // Function to update transport cost in the sidebar
  function updateTransportCost() {
    let transportCost = 0;
    const selectedTransport = document.querySelector('input[name="transport"]:checked');
    if (selectedTransport) {
      if (selectedTransport.value === "yes") {
        transportCost = storedFees ? parseFloat(storedFees.transportation_cost) : 0;
      } else if (selectedTransport.value === "no") {
        transportCost = 0;
      }
    } else {
      console.warn("No transport option selected");
    }
    if (transportCostElement) {
      transportCostElement.textContent = `$ ${transportCost.toFixed(2)}`;
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
    feeElem.textContent = `$ ${formattedFee}`;
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
    feeElem.textContent = `$ ${formattedFee}`;
    updateTotalCost();
  }

  function resetAccommodationFee() {
    const accommodationFeeElem = document.querySelector("#accommodation-fee");

    accommodationFeeElem.textContent = "$ 0.00";
    updateTotalCost();
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
    totalCostElem.textContent = `$ ${totalCost.toFixed(2)}`;
    populateReviewTab();
  }

  updateInputButton("Apply");

  // Event listener to show coupons
  async function showCoupons() {
    try {
      const response = await fetch(`${bookingData.restApiUrl}active-coupons`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      });

      const data = await response.json();

      if (data.success) {
        couponData = data.data;
        const couponList = couponData
          .map((coupon) => {
            // Skip rendering the apply button if coupon is already applied
            const isApplied = appliedCoupon?.code === coupon.code;
            // Format the expiry date to dd-mm-yyyy
            const formattedExpiryDate = formatCouponDate(coupon.expiry_date);
            return `
            <li class="coupon-item">
              <div class="coupon-header">
                <span>${coupon.code}</span> 
                <button 
                  class="apply-coupon-btn ${isApplied ? "coupon-btn-remove" : "coupon-btn-apply"}" 
                  data-code="${coupon.code}" 
                  data-type="${coupon.discount_type}" 
                  data-value="${coupon.discount_value || ""}" 
                  data-min-price="${coupon.min_price_range || ""}" 
                  data-max-price="${coupon.max_price_range || ""}" 
                  data-min-discount="${coupon.min_discount_percentage || ""}" 
                  data-max-discount="${coupon.max_discount_percentage || ""}"
                  data-action="${isApplied ? "remove" : "apply"}">
                  ${isApplied ? "Remove" : "Apply"}
                </button>
              </div>
              <div class="coupon-details">
                <span class="discount-info">${
                  coupon.discount_type === "fixed"
                    ? `$${coupon.discount_value} off`
                    : `${coupon.min_discount_percentage}% - ${coupon.max_discount_percentage}% off`
                }</span>
                <span class="discount-description">Get assured discount with for your desired course <button class="know-more-btn" data-code="${
                  coupon.code
                }" style="font-weight: bold; font-size: 12px; background: none; border: none; padding: 0; text-decoration: none; color: inherit; cursor: pointer; text-align:left;">
                    Know More
                  </button></span>
                
              </div>
             
            </li>
            <div style="margin-block: 10px; border:0.5px dashed #e6e6e6"></div>
          `;
          })
          .join("");

        document.getElementById("coupon-list").innerHTML = couponList;
        // Add event listeners for "Know More" buttons
        const knowMoreButtons = document.querySelectorAll(".know-more-btn");
        knowMoreButtons.forEach((button) => {
          button.addEventListener("click", (e) => {
            e.preventDefault();
            const couponCode = e.target.getAttribute("data-code");
            openCouponDetailsModal(couponCode);
          });
        });
      } else {
        alert("No active coupons available.");
      }
    } catch (error) {
      console.error("Error fetching coupons:", error);
      alert("Error fetching coupons.");
    }
  }

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
    // Reset the manual input button to default state
    updateInputButton("Apply");
    //  Reset all coupon buttons to their default state
    document.querySelectorAll(".apply-coupon-btn").forEach((button) => {
      button.textContent = "Apply";
      button.setAttribute("data-action", "apply");
      button.classList.remove("coupon-btn-remove");
      button.classList.add("coupon-btn-apply");
    });

    const couponInput = document.getElementById("coupon_code");
    couponInput.value = couponCode; // Fill the input field with the selected coupon code

    if (appliedCoupon === couponCode) {
      alert("This coupon has already been applied");
      return;
    }

    const originalPrice = getOriginalCoursePrice();
    discountAmount = 0;

    // Calculate discounted price
    if (discountType === "fixed") {
      discountAmount = discountValue; // Ensure non-negative price
    } else if (discountType === "percentage") {
      discountAmount = applyPercentageCoupon(
        originalPrice,
        minPrice,
        maxPrice,
        minDiscount,
        maxDiscount
      );
    }
    // Check if discount exceeds course price
    if (discountAmount > originalPrice) {
      alert("This coupon cannot be applied as the discount exceeds the course price.");
      return; // Exit without applying the coupon
    }

    // Show the discount row in the review tab
    document.querySelector("#review-discount-amount").textContent = `-$ ${discountAmount.toFixed(
      2
    )}`;
    const discountRow = document.querySelector(".review-discount-row");
    if (discountRow) {
      discountRow.style.display = "flex"; // Show discount row in the review tab
    }

    // Update total cost with the discounted price
    updateTotalCost();
    appliedCoupon = couponCode;
    updateCouponButton(couponCode);
    updateInputButton("Remove");
    // Notify the user
    showCouponModal(discountAmount);
  }

  // Function to update the coupon button (toggle between "Apply" and "Remove")
  function updateCouponButton(couponCode) {
    // Reset the state of the previously applied coupon, if any
    if (appliedCoupon && appliedCoupon !== couponCode) {
      const previousButton = document.querySelector(
        `.apply-coupon-btn[data-code="${appliedCoupon}"]`
      );
      if (previousButton) {
        previousButton.textContent = "Apply";
        previousButton.setAttribute("data-action", "apply");
        previousButton.classList.remove("coupon-btn-remove");
        previousButton.classList.add("coupon-btn-apply");
      }
    }
    const button = document.querySelector(`.apply-coupon-btn[data-code="${couponCode}"]`);
    if (button) {
      console.log("Button found:", button); // Debugging log
      if (button.textContent.trim() === "Apply") {
        // Change to "Remove" state
        button.textContent = "Remove";
        button.setAttribute("data-action", "remove");
        button.classList.remove("coupon-btn-apply");
        button.classList.add("coupon-btn-remove");
      } else if (button.textContent.trim() === "Remove") {
        // Change back to "Apply" state
        button.textContent = "Apply";
        button.setAttribute("data-action", "apply");
        button.classList.remove("coupon-btn-remove");
        button.classList.add("coupon-btn-apply");
      }
      console.log("Updated button classes:", button.classList);
    } else {
      console.log("Button not found for coupon code:", couponCode);
    }
  }

  // Function to update the input button (toggle between "Apply Coupon" and "Remove Coupon")
  function updateInputButton(buttonText) {
    const applyButton = document.getElementById("apply-coupon");
    if (!applyButton) return;
    applyButton.textContent = buttonText;
    applyButton.setAttribute("data-action", buttonText === "Remove" ? "remove" : "apply");

    // Remove both classes before adding the new one
    applyButton.classList.remove("coupon-btn-apply", "coupon-btn-remove");

    if (buttonText === "Remove") {
      applyButton.classList.add("coupon-btn-remove");
    } else {
      applyButton.style.color = "";
    }
  }

  function removeCoupon(couponCode) {
    if (appliedCoupon !== couponCode) {
      alert("No such coupon is currently applied.");
      return;
    }
    appliedCoupon = null; // Clear applied coupon
    discountAmount = 0;
    updateTotalCost(); // Update total cost

    updateCouponButton(couponCode);
    updateInputButton("Apply");

    // Clear the coupon input field
    const couponInput = document.getElementById("coupon_code");
    couponInput.value = ""; // Clear the input field

    const discountRow = document.querySelector(".review-discount-row");
    if (discountRow) {
      discountRow.style.display = "none"; // Hide the discount row in the review tab
    }

    document.getElementById("coupon-message").style.display = "none"; // Hide coupon message
  }

  // Event listener for coupon list apply buttons
  document.addEventListener("click", function (event) {
    if (event.target.classList.contains("apply-coupon-btn")) {
      event.preventDefault();
      if (event.target.closest(".coupons")) {
        const couponInput = document.getElementById("coupon_code");
        const enteredCouponCode = couponInput.value.trim();

        // Clear previous messages
        const couponMessage = document.getElementById("coupon-message");
        couponMessage.style.display = "none";
        couponMessage.textContent = "";

        const action = event.target.getAttribute("data-action");
        if (action === "apply") {
          // Validate input
          if (!enteredCouponCode) {
            couponMessage.style.color = "red";
            couponMessage.textContent = "Please enter a coupon code.";
            couponMessage.style.display = "block";
            return;
          }
          console.log(couponData);
          // Check if coupon exists in couponData
          const coupon = couponData.find((c) => c.code === enteredCouponCode);

          if (!coupon) {
            couponMessage.style.color = "red";
            couponMessage.textContent = "Invalid coupon code. Please try again.";
            couponMessage.style.display = "block";
            return;
          }

          // Check if the coupon is already applied
          if (appliedCoupon === enteredCouponCode) {
            couponMessage.style.color = "orange";
            couponMessage.textContent = "This coupon is already applied.";
            couponMessage.style.display = "block";
            return;
          }

          // Get the original course price
          const originalPrice = getOriginalCoursePrice();
          discountAmount = 0;

          // Calculate discount
          if (coupon.discount_type === "fixed") {
            discountAmount = coupon.discount_value;
          } else if (coupon.discount_type === "percentage") {
            discountAmount = applyPercentageCoupon(
              originalPrice,
              coupon.min_price_range,
              coupon.max_price_range,
              coupon.min_discount_percentage,
              coupon.max_discount_percentage
            );
          }

          // Ensure discount doesn't exceed course price
          if (discountAmount > originalPrice) {
            couponMessage.style.color = "red";
            couponMessage.textContent =
              "This coupon cannot be applied as the discount exceeds the course price.";
            couponMessage.style.display = "block";
            return;
          }

          // Mark coupon as applied
          appliedCoupon = enteredCouponCode;

          // Update the UI with discount details
          document.querySelector(
            "#review-discount-amount"
          ).textContent = `-$ ${discountAmount.toFixed(2)}`;
          const discountRow = document.querySelector(".review-discount-row");
          if (discountRow) {
            discountRow.style.display = "flex";
          }

          // Update total cost
          updateTotalCost();

          // Update button and input state
          updateCouponButton(enteredCouponCode);
          updateInputButton("Remove");

          // Show the coupon modal
          showCouponModal(discountAmount);

          // Display success message
          couponMessage.style.color = "green";
          couponMessage.textContent = "Coupon applied successfully!";
          couponMessage.style.display = "block";
          couponInput.value = enteredCouponCode;
          console.log("Manual input coupon applied:", enteredCouponCode);
        } else if (action === "remove") {
          removeCoupon(enteredCouponCode);
        }
      } else {
        // Fetch data attributes from the clicked button
        const couponCode =
          event.target.getAttribute("data-code") || document.getElementById("coupon_code").value; // Fetch coupon code
        const discountType = event.target.getAttribute("data-type"); // Fetch discount type
        const discountValue = parseFloat(event.target.getAttribute("data-value")) || 0.0; // Fetch discount value
        const minPrice = parseFloat(event.target.getAttribute("data-min-price")) || 0.0;
        const maxPrice = parseFloat(event.target.getAttribute("data-max-price")) || 0.0;
        const minDiscount = parseFloat(event.target.getAttribute("data-min-discount")) || 0.0;
        const maxDiscount = parseFloat(event.target.getAttribute("data-max-discount")) || 0.0;

        if (!couponCode) {
          alert("Please enter or select a valid coupon code.");
          return;
        }

        if (event.target.getAttribute("data-action") === "apply") {
          applyCoupon(
            couponCode,
            discountType,
            discountValue,
            minPrice,
            maxPrice,
            minDiscount,
            maxDiscount
          );
        } else if (event.target.getAttribute("data-action") === "remove") {
          // Remove the coupon if it's already applied
          removeCoupon(couponCode);
        }
      }
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
    const minDiscountNum = parseFloat(minDiscount);
    const maxDiscountNum = parseFloat(maxDiscount);
    // Ensure selectedPrice is within the valid range
    if (isNaN(selectedPrice) || selectedPrice <= 0) {
      console.error("Invalid selectedPrice");
      return 0; // Or handle this case as per your requirements
    }

    if (isNaN(minPrice) || minPrice <= 0 || isNaN(maxPrice) || maxPrice <= 0) {
      console.error("Invalid minPrice or maxPrice");
      return 0; // Or handle this case
    }

    if (minPrice === maxPrice) {
      console.error("minPrice cannot be equal to maxPrice");
      return 0; // Or handle this case
    }

    if (isNaN(minDiscount) || isNaN(maxDiscount) || minDiscount > maxDiscount) {
      console.error("Invalid discount values");
      return 0; // Or handle this case
    }
    if (selectedPrice < minPrice) selectedPrice = minPrice;
    if (selectedPrice > maxPrice) selectedPrice = maxPrice;
    // Proportional calculation for discount
    const proportionalValue = (selectedPrice - minPrice) / (maxPrice - minPrice);
    // Round the proportional value to avoid floating point issues
    const roundedProportionalValue = Math.round(proportionalValue * 10000) / 10000;

    const discountPercentage =
      minDiscountNum + roundedProportionalValue * (maxDiscountNum - minDiscountNum);

    // Round the discount percentage to avoid floating point issues
    const roundedDiscountPercentage = Math.round(discountPercentage * 100) / 100;

    // Check if any of the values is NaN before performing the calculation
    if (isNaN(minDiscount) || isNaN(maxDiscount) || isNaN(roundedProportionalValue)) {
      console.error("One of the values involved in the calculation is NaN.");
    }

    const finalDiscountPercentage = Math.min(
      Math.max(discountPercentage, minDiscount),
      maxDiscount
    );
    return finalDiscountPercentage;
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
    return discountAmount;
  }

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

    // Booking cost breakdown
    const coursePrice =
      parseFloat(document.querySelector("#course-price").textContent.replace("$", "")) || 0;
    const registrationFee =
      parseFloat(document.querySelector("#registration-fee").textContent.replace("$", "")) || 0;
    const accommodationFee =
      parseFloat(document.querySelector("#accommodation-fee").textContent.replace("$", "")) || 0;
    const transportCost =
      parseFloat(document.querySelector("#transport-cost").textContent.replace("$", "")) || 0;

    // Populate cost details
    document.querySelector("#review-selected-course span").textContent =
      document.querySelector("#selected-course").textContent;
    document.querySelector("#review-course-price").textContent = `$ ${coursePrice.toFixed(2)}`;
    document.querySelector("#review-registration-fee").textContent = `$ ${registrationFee.toFixed(
      2
    )}`;
    document.querySelector("#review-accommodation-fee").textContent = `$ ${accommodationFee.toFixed(
      2
    )}`;
    document.querySelector("#review-transport-cost").textContent = `$ ${transportCost.toFixed(2)}`;

    // Calculate total cost for the review tab
    const totalCost =
      coursePrice - discountAmount + transportCost + registrationFee + accommodationFee;
    document.querySelector("#review-total-cost").textContent = `$ ${totalCost.toFixed(2)}`;

    // Fetch the selected duration's data-duration-weeks value
    const selectedOption = durationSelect.options[durationSelect.selectedIndex];
    const durationWeeks = selectedOption
      ? selectedOption.getAttribute("data-duration-weeks")
      : null;

    // Populate start date and duration
    const formattedStartDate = formatDate(startDateSelect.value);
    document.querySelector("#review-course-start-date span").textContent = formattedStartDate;
    document.querySelector("#review-course-duration span").textContent = durationWeeks
      ? `${durationWeeks} Weeks`
      : "Not selected";
  }

  function populateConfirmationTab() {
    const courseName = document.querySelector("#review-selected-course span").textContent;

    const startDate = document.querySelector("#review-course-start-date span").textContent;

    const courseDuration = document.querySelector("#review-course-duration span").textContent;

    const successMessageElement = document.querySelector("#success-message");
    successMessageElement.innerHTML = `${courseName}, <span class="highlight">starting on ${startDate} (${courseDuration})</span>, has been successfully booked. Please check your email for the corresponding invoice.`;
  }

  function removeSidebar() {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar) {
      sidebar.style.display = "none";
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

    const submitButton = form.querySelector("button[type=submit]");
    submitButton.disabled = false;

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
    const transportationFee =
      parseFloat(document.querySelector("#transport-cost").textContent.replace("$", "")) || 0.0;
    const registrationFee =
      parseFloat(document.querySelector("#registration-fee").textContent.replace("$", "")) || 0.0;
    const couponCode = document.querySelector("#coupon_code").value.trim();
    const totalAmount =
      parseFloat(document.querySelector("#review-total-cost").textContent.replace("$", "")) * 100 ||
      0.0; // Convert to cents

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
            submitButton.disabled = true;
          } else {
            alert("Payment verification failed.");
            submitButton.disabled = false;
          }
        },
      };

      const rzp = new Razorpay(razorpayOptions);
      rzp.open();
    } catch (error) {
      console.error("Error:", error.message);
    }
  });

  // Helper function to format date to dd-mm-yyyy
  function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0"); // months are zero-indexed
    const year = date.getFullYear();

    return `${day}-${month}-${year}`;
  }
  function formatCouponDate(dateString) {
    const date = new Date(dateString);

    const day = String(date.getDate()).padStart(2, "0"); // Pad single digit days with leading zero
    const month = String(date.getMonth() + 1).padStart(2, "0"); // Months are zero-based, so add 1
    const year = date.getFullYear();

    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${day}-${month}-${year} ${hours}:${minutes}`;
  }

  // Function to show the modal with the discount message
  function showCouponModal(discountAmount) {
    const modal = document.getElementById("coupon-modal");
    const modalMessage = document.getElementById("coupon-modal-message");
    const overlay = document.getElementById("modal-overlay");

    // Set the modal message content
    modalMessage.textContent = `Congrats! You have saved $${discountAmount.toFixed(
      2
    )} on this course.`;

    // Show the modal and overlay
    modal.style.display = "flex";
    overlay.style.display = "block";

    setTimeout(() => {
      modal.classList.add("active"); // Add active class to trigger fade-in
      overlay.classList.add("active"); // Optional: Add active class for overlay fade-in
    }, 10);

    // Add an event listener to close the modal
    document.getElementById("modal-close").addEventListener("click", hideCouponModal);

    // Close modal when overlay is clicked
    overlay.addEventListener("click", hideCouponModal);
  }

  // Function to hide the modal
  function hideCouponModal() {
    const modal = document.getElementById("coupon-modal");
    const overlay = document.getElementById("modal-overlay");

    if (!modal || !overlay) return;

    // Remove the active class to trigger fade-out
    modal.classList.remove("active");
    overlay.classList.remove("active");
    // Wait for the transition to complete, then hide the modal and overlay
    setTimeout(() => {
      modal.style.display = "none";
      overlay.style.display = "none";
    }, 400); // Match the timeout to the CSS transition duration
  }

  function openCouponDetailsModal(couponCode) {
    // Fetch coupon details from your data (you could use a pre-fetched list or re-fetch from API)
    const coupon = couponData.find((coupon) => coupon.code === couponCode);

    if (coupon) {
      // Format the details in the modal
      const modalContent = `
      <div class="coupon-details-modal-header-section">
        <h3 class="coupon-details-modal-header">${coupon.code}</h3>
        <p class="coupon-details-modal-subheader">${
          coupon.discount_type === "fixed"
            ? `<span>Flat $${coupon.discount_value} off</span>`
            : `<span>Upto ${coupon.max_discount_percentage}% off</span>`
        }</p>
      </div>
      <div style="margin-block: 20px; border:0.5px dashed #e6e6e6"></div>
      <div class="coupon-details-key-terms">
        <span>Key terms and condition</span>
        <ul>
          <li><span>Coupon valid till ${formatCouponDate(coupon.expiry_date)}, hrs</span> </li>
          ${
            coupon.discount_type === "fixed"
              ? `<li><span>Get a flat discount of $${coupon.discount_value} on selected course.</span></li>`
              : `<li><span>Get a maximum discount of ${coupon.max_discount_percentage}%</span></li> <li><span>Get a minimum discount of ${coupon.min_discount_percentage}%</span></li>`
          }
        </ul>
      </div>  
    `;

      document.getElementById("coupon-details-content").innerHTML = modalContent;
      modal = document.getElementById("coupon-details-modal");
      const overlay = document.getElementById("modal-overlay");
      modal.style.display = "flex";
      overlay.style.display = "block";
      setTimeout(() => {
        modal.classList.add("active"); // Add active class for fade-in
        overlay.classList.add("active");
      }, 10);
    }
  }

  // Close the modal when the close button is clicked
  document.getElementById("detail-modal-close").addEventListener("click", () => {
    closeCouponDetailsModal();
  });

  document.querySelector(".reload").addEventListener("click", function (e) {
    e.preventDefault();
    window.location.reload();
  });

  // Function to close the modal
  function closeCouponDetailsModal() {
    const modal = document.getElementById("coupon-details-modal");
    const overlay = document.getElementById("modal-overlay");

    if (!modal || !overlay) return;

    // Remove the active class to trigger fade-out
    modal.classList.remove("active");
    overlay.classList.remove("active");
    // Wait for the transition to complete, then hide the modal and overlay
    setTimeout(() => {
      modal.style.display = "none";
      overlay.style.display = "none";
    }, 400); // Match the timeout to the CSS transition duration
  }

  // Helper function to check if a string is a valid date
  function isValidDate(dateString) {
    const date = new Date(dateString);
    return !isNaN(date); // Returns true if the date is valid
  }
});
