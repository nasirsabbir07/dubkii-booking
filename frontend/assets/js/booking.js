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
  let selectedCourseCost = 0;
  let stripe, elements, clientSecret;

  // Check if pluginCourseId is defined and fetch the specific course
  if (typeof bookingData.currentCourseId !== "undefined") {
    fetchCourses(bookingData.currentCourseId);
  }

  // Function to show the current step
  function showStep(step) {
    steps.forEach((stepElement, index) => {
      stepElement.style.display = index === step - 1 ? "block" : "none";
    });

    tabs.forEach((tab) => tab.classList.remove("active"));
    tabs[step - 1].classList.add("active");
  }

  showStep(1);

  // Next and Previous button handling
  document.querySelectorAll(".next-button").forEach((button) => {
    button.addEventListener("click", function () {
      const nextStep = parseInt(this.getAttribute("data-next-step"));
      showStep(nextStep);
    });
  });

  document.querySelectorAll(".prev-button").forEach((button) => {
    button.addEventListener("click", function () {
      const prevStep = parseInt(this.getAttribute("data-prev-step"));
      showStep(prevStep);
    });
  });

  // Tab handling
  tabs.forEach((tab, index) => {
    tab.addEventListener("click", function () {
      showStep(index + 1);
    });
  });

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
      console.log(data);
      if (data.success) {
        allCourses = [data.course];
        populateDropdown(courseSelect, allCourses, "Select a course");
      }
    } catch (error) {
      console.error("Error fetching course data:", error);
    }
  }

  // Call the fetchOptions function to populate dropdowns

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
      clearDropdown(startDateSelect); // Clear start date and duration dropdowns if no course is selected
      clearDropdown(durationSelect);
      // Optionally clear sidebar as well
      document.getElementById("selected-course").textContent = "None";
      document.getElementById("course-price").textContent = "0";
      selectedCourseCost = 0;
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
      document.getElementById("course-price").textContent = "0.00";
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
      console.log(data);

      if (data.success) {
        const price = parseFloat(data.price);
        document.getElementById("course-price").textContent = price.toFixed(2); // Update the price
        updateTotalCost();
      } else {
        console.error("Error fetching price:", data.message);
        document.getElementById("course-price").textContent = "0.00"; // Fallback if error
        updateTotalCost();
      }
    } catch (error) {
      console.error("Error fetching price:", error);
      document.getElementById("course-price").textContent = "0.00"; // Fallback if error
      updateTotalCost();
    }
  }

  // Function to clear dropdown
  function clearDropdown(selectElement) {
    selectElement.innerHTML = "";
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

        // Add nationalities to the nationality dropdown
        const nationalityOption = document.createElement("option");
      });
    } else {
      console.error("Countries list not found or not loaded correctly");
    }
  }

  function setNationalityBasedOnCountry(selectedCountry) {
    nationalitySelect.innerHTML = '<option value="">Select nationality</option>';

    const country = window.countriesList.find((item) => item.name === selectedCountry);
    if (country) {
      const nationalityOption = document.createElement("option");
      nationalityOption.value = country.nationality;
      nationalityOption.textContent = country.nationality;
      nationalitySelect.appendChild(nationalityOption);
    }
  }

  populateCountryDropdown();

  countrySelect.addEventListener("change", function () {
    const selectedCountry = this.value;
    setNationalityBasedOnCountry(selectedCountry);
  });

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
        console.log("Fees stored in localStorage:", localStorage.getItem("fees"));
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
      transportCostElement.textContent = transportCost.toFixed(2);
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
    console.log(email);

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
        console.log(jsonData);

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
    feeElem.textContent = `${formattedFee}`;
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
    console.log(durationWeeks);
    const accommodationCost = storedFees ? parseFloat(storedFees.accommodation_cost) : 0;
    return durationWeeks * accommodationCost;
  }

  function updateSidebarWithAccommodationFee(duration) {
    const sidebar = document.querySelector(".sidebar");
    const feeElem = sidebar.querySelector("#accommodation-fee");
    const fee = calculateAccommodationFee(duration);
    const formattedFee = parseFloat(fee).toFixed(2);
    feeElem.textContent = `${formattedFee}`;

    updateTotalCost();
  }

  function resetAccommodationFee() {
    const accommodationFeeElem = document.querySelector("#accommodation-fee");

    accommodationFeeElem.textContent = "0.00";
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
    const coursePrice = parseFloat(document.querySelector("#course-price").textContent) || 0;
    console.log(coursePrice);
    const accommodationFee =
      parseFloat(document.querySelector("#accommodation-fee").textContent) || 0;
    const transportCost = parseFloat(transportCostElement.textContent) || 0;
    const registrationFee = parseFloat(registrationFeeElem.textContent) || 0.0;
    const totalCost = coursePrice + transportCost + registrationFee + accommodationFee;
    totalCostElem.textContent = totalCost.toFixed(2);
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
      <p><strong>Registration Fee:</strong> $${bookingDetails.registrationFee}</p>
      <p><strong>Accommodation Fee:</strong> $${bookingDetails.accommodationFee}</p>
      <p><strong>Total Paid:</strong> $${bookingDetails.amount}</p>
      <p><strong>Email:</strong> ${bookingDetails.email}</p>
      <p>Your booking ID is <strong>${bookingDetails.bookingId}</strong>.</p>
      <button onclick="window.location.reload()">Back to Home</button>
    `;
    }
  }

  // Check if Stripe is loaded
  if (typeof Stripe === "undefined") {
    console.error("Stripe not loaded");
    return;
  }

  // Initialize Stripe with the publishable key from localized data
  stripe = Stripe(bookingData.stripePublicKey);

  // Form submission handling
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // Validate form data before proceeding
    const requiredFields = ["name", "email", "course", "start_date", "duration"];
    for (const field of requiredFields) {
      if (!form[field].value) {
        console.log(`Please fill in the ${field} field.`);
        return;
      }
    }

    // Get nonce securely
    // const nonce = form.elements.namedItem("nonce").value;
    // console.log(nonce);
    // Get fee details and calculate the total amount
    const accommodationFee =
      parseFloat(document.querySelector("#accommodation-fee").textContent) || 0;
    const coursePrice = parseFloat(document.querySelector("#course-price").textContent) || 0;
    const totalAmount = parseFloat(document.querySelector("#total-cost").textContent) * 100 || 0; // Convert to cents

    // const formData = new FormData(form);
    // formData.append("action", "handle_booking_submission"); // Ensure action is set
    // formData.append("accommodationFee", accommodationFee);
    // formData.append("coursePrice", coursePrice);
    // formData.append("totalAmount", totalAmount);

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
      duration: form["duration"].value,
      english_level: form["english_level"].value,
      transport: form["transport"].value,
      accommodationFee: accommodationFee,
      totalAmount: totalAmount,
      // Include the nonce
    };

    try {
      // Send the request to the updated REST API endpoint
      const response = await fetch(`${bookingData.restApiUrl}submit-booking`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json", // Set content type to JSON
        },
        body: JSON.stringify(params),
      });

      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }

      const jsonData = await response.json(); // Parse the response as JSON
      console.log(jsonData);

      if (jsonData.success) {
        clientSecret = jsonData.clientSecret;
        bookingDetails = jsonData.bookingDetails;
        showStep(4);
        initializeStripePayment(clientSecret, bookingDetails);
      } else {
        // Handle error response from the server
        console.log("There was an error: " + (jsonData.message || "Unknown error"));
        console.log(jsonData);
      }
    } catch (error) {
      // Handle network or other errors
      console.error("Fetch error:", error);
      console.log("There was an error: " + error.message);
    }

    // Initialize payment in step 4
    function initializeStripePayment(clientSecret) {
      const elements = stripe.elements({ clientSecret });
      const paymentElement = elements.create("payment");

      paymentElement.mount("#payment-element");

      submitPaymentButton.addEventListener("click", async function () {
        try {
          await elements.submit();

          const { error } = await stripe.confirmPayment({
            elements,
            clientSecret: clientSecret,
            confirmParams: {},
            redirect: "if_required",
          });

          if (error) {
            console.log("Payment failed: " + error.message);
          } else {
            console.log("Payment successful!");
            removeSidebar();
            displayBookingDetails(bookingDetails);
            showStep(5);
            form.reset();
          }
        } catch (err) {
          console.error("Error submitting payment form:", err.message);
          console.log("Error: " + err.message);
        }
      });
    }
  });
});
