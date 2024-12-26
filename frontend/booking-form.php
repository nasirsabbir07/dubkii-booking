<div class="booking-form">
    <ul class="tab-list">
        <li class="tab active" data-step="1">Select Course</li>
        <li class="tab" data-step="2">Personal Details</li>
        <li class="tab" data-step="3">Transport</li>
        <li class="tab" data-step="4">Review</li>
        <li class="tab" data-step="5">Done</li>
    </ul>

    <div class="form-container">
        <form id="booking-form" method="post" action="">
            <!-- Add nonce field here -->

            <!-- Step 1: Select Course -->

            <div class="form-step step-1">
                <h3>Course Selection</h3>
                <div class="course-detail-selection">
                    <label for="course" class="selection-label">Select Course</label>
                    <select name="course" id="course" required>
                        <option value="">Select A Course</option>
                    </select>
                </div>

                <div class="course-detail-selection">
                    <label for="start-date-wrapper" class="selection-label">Select Start Date</label>
                    <select name="start_date" id="start-date-wrapper" required>
                        <option value="">Select a start date</option>
                    </select>
                </div>

                <div class="course-detail-selection">
                    <label for="duration-wrapper" class="selection-label">Duration</label>
                    <select name="duration" id="duration-wrapper" required>
                        <option value="">Select duration</option>
                    </select>
                </div>
                <div class="button-container right-btn">
                    <button type="button" class="next-button" data-next-step="2">Next</button>
                </div>

            </div>

            <!-- Step 2: Personal Details -->
            <div class="form-step step-2" style="display: none;">
                <h3>Personal Details</h3>
                <div class="personal-details">
                    <label for="name" class="personal-details-label">Full Name</label>
                    <input type="text" id="name" name="name" class="details-input" required>
                </div>
                <div class="personal-details">
                    <label for="email" class="personal-details-label">Email</label>
                    <div class="input-email-msg">
                        <input type="email" id="email" name="email" class="details-input" required>
                        <div id="email-message" style="margin-bottom: 5px; color: green; display: none;"></div>
                    </div>
                </div>
                <div class="personal-details">
                    <label for="dob" class="personal-details-label">Date of birth</label>
                    <input type="date" id="dob" name="dob" class="details-input" required>
                </div>
                <div class="personal-details">
                    <label for="address" class="personal-details-label">Address</label>
                    <input type="text" id="address" name="address" class="details-input" required>
                </div>
                <div class="personal-details">
                    <label for="city" class="personal-details-label">City</label>
                    <input type="text" id="city" name="city" class="details-input" required>
                </div>
                <div class="personal-details">
                    <label for="post_code" class="personal-details-label">Post Code/Zip Code</label>
                    <input type="text" id="post_code" name="post_code" class="details-input" required>
                </div>
                <div class="country-selection personal-details">
                    <label for="country" class="personal-details-label">Country</label>
                    <select id="country" name="country" class="details-selection" required>
                        <option value="">Select a country</option>
                    </select>
                </div>
                <div class="personal-details">
                    <label for="contact_no" class="personal-details-label">Phone</label>
                    <input type="text" id="contact_no" name="contact_no" class="details-input" required>
                </div>
                <div class="nationality-selection personal-details">
                    <label for="nationality" class="personal-details-label">Nationality</label>
                    <select id="nationality" name="nationality" class="details-selection" required>
                        <option value="">Select a nationality</option>
                    </select>
                </div>
                <div class="english-level-selection personal-details">
                    <label for="english_level" class="personal-details-label">Level of English</label>
                    <select name="english_level" id="english-level" class="details-selection" required>
                        <option value="">Select your level</option>
                        <option value="None">None</option>
                        <option value="Basic">Basic</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advance">Advance</option>
                    </select>
                </div>
                <div class="emergency-contact" style="margin-bottom: 10px;">
                    <h3>Emergency Details</h3>
                    <div class="contact-type-selection personal-details">
                        <label for="contact_type" class="personal-details-label">Contact Type</label>
                        <select name="contact_type" id="contact_type" class="details-selection" required>
                            <option value="parent">Parent</option>
                            <option value="guardian">Guardian</option>
                            <option value="sibling">Sibling</option>
                            <option value="agent">Agent</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="personal-details">
                        <label for="emergency_name" class="personal-details-label">Full Name</label>
                        <input type="text" id="emergency_name" name="emergency_name" class="details-input" required>
                    </div>
                    <div class="personal-details">
                        <label for="emergency_email" class="personal-details-label">Email</label>
                        <input type="email" id="emergency_email" name="emergency_email" class="details-input" required>
                    </div>
                    <div class="personal-details">
                        <label for="emergency_contact_no" class="personal-details-label">Phone</label>
                        <input type="text" id="emergency_contact_no" name="emergency_contact_no" class="details-input" required>
                    </div>
                </div>
                <div class="button-container">
                    <button type="button" class="prev-button" data-prev-step="1">Back</button>
                    <button type="button" class="next-button" data-next-step="3">Next</button>
                </div>

            </div>
            <!-- Step 3: Transport Selection -->
            <div class="form-step step-3" style="display: none;">
                <h3>Transport Options</h3>
                <p>We can arrange for you to be met at your arrival airport and taken to your accommodation</p>
                <div class="transport-details">
                    <span class="transport-details-txt">
                        <input type="radio" name="transport" value="yes" class="transport-details-input" required> I want transport (<span id="transport-cost-display">$0</span>)
                    </span>
                    <span class="transport-details-txt">
                        <input type="radio" name="transport" value="no" class="transport-details-input" required> I don't want transport
                    </span>
                </div>

                <div id="transport-error" class="error-message" style="color: red; display: none;">
                    Please select a transport option.
                </div>
                <div class="button-container">
                    <button type="button" class="prev-button" data-prev-step="2">Back</button>
                    <button type="button" class="next-button" data-next-step="4">Next</button>
                    <!-- <button type="submit">Submit</button> -->
                </div>
            </div>
            <div id="step-review" class="form-step step-4" style="display: none;">
                <p>Please review your booking and move on to payment. Thank you.</p>
                <div class="review-container">
                    <div class="review-left-column">
                        <!-- User Details Section -->
                        <div class="review-user-details">
                            <h3>Your Details</h3>
                            <div class="user-details-row">
                                <div class="user-details-top">
                                    <div id="review-name"><span></span></div>
                                    <div id="review-email"><span></span></div>
                                </div>
                                <div class="user-details-bottom">
                                    <div id="review-contact"><span></span></div>
                                    <div id="review-address"><span></span></div>
                                </div>
                            </div>
                        </div>
                        <!-- Booking Cost Breakdown Section -->
                        <div class="review-booking-details">
                            <h3>Selected Course</h3>
                            <div class="booking-details-row">
                                <div class="course-info">
                                    <div id="review-selected-course">Course: <span></span></div>
                                    <div id="review-course-details">
                                        <span id="review-course-start-date">Start Date: <span></span></span>,
                                        <span id="review-course-duration">Duration: <span></span></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="review-right-column">
                        <div class="booking-details-row coupon-total">
                            <!-- Apply Coupon Section -->
                            <div class="coupon-section">
                                <!-- <label for="coupon_code">Apply a coupon</label> -->
                                <div class="coupons">
                                    <input type="text" id="coupon_code" name="coupon_code" placeholder="Select an available coupon">
                                    <button type="button" id="apply-coupon">Apply Coupon</button>
                                </div>


                                <div id="coupon-message" style="margin-top: 5px; color: green; display: none;"></div>
                                <!-- Available Coupons Section -->
                                <div class="available-coupons" style="margin-top: 15px;">
                                    <ul id="coupon-list">
                                        <!-- Active coupons will be dynamically loaded here -->
                                    </ul>
                                </div>
                            </div>


                            <!-- Total Cost Section -->
                            <div class="total-cost-section">
                                <div class="details-under-total">
                                    <div class="review-booking-row">
                                        <span class="booking-label">Course Price: </span>
                                        <span class="booking-value" id="review-course-price">$ 0.00</span>
                                    </div>
                                    <div class="review-booking-row">
                                        <span class="booking-label">Registration: </span>
                                        <span class="booking-value" id="review-registration-fee">$ 0.00</span>
                                    </div>
                                    <div class="review-booking-row">
                                        <span class="booking-label">Accommodation: </span>
                                        <span class="booking-value" id="review-accommodation-fee">$ 0.00</span>
                                    </div>
                                    <div class="review-booking-row">
                                        <span class="booking-label">Transport: </span>
                                        <span class="booking-value" id="review-transport-cost">$ 0.00</span>
                                    </div>
                                </div>
                                <div class="review-booking-row">
                                    <span class="booking-label"> TOTAL PRICE </span><span id="review-total-cost">$ 0.00</span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


                <div class="button-container">
                    <button type="button" class="prev-button" data-prev-step="3">Back</button>
                    <button type="submit">Submit & Pay</button>
                </div>

            </div>

            <div class="form-step step-5" style="display: none;">
            </div>
        </form>
        <!-- Sidebar Section -->
        <div class="sidebar">

            <div class="booking-details">
                <div class="booking-row">
                    <h4 class="sidebar-title booking-label">Booking Details</h4>
                    <h4 class="booking-value">Amount</h4>
                </div>
                <div class="booking-row" id="booking-selected-course-row">
                    <!-- <span class="booking-label">Selected Course:</span> -->
                    <span id="selected-course" class="booking-label">Course Not Selected</span>
                    <span id="course-price" class="booking-value" data-original-price="0.00">$ 0.00</span>
                </div>
                <div class="booking-row" id="booking-registration-fee-row">
                    <span class="booking-label">Registration Fee</span>
                    <span id="registration-fee" class="booking-value">$ 0.00</span>
                </div>
                <div class="booking-row" id="booking-accommodation-fee-row">
                    <span class="booking-label">Accommodation Fee</span>
                    <span id="accommodation-fee" class="booking-value">$ 0.00</span>
                </div>
                <div class="booking-row" id="booking-transport-cost-row">
                    <span class="booking-label">Transport Cost</span>
                    <span id="transport-cost" class="booking-value">$ 0.00</span>
                </div>
                <div class="booking-row total" id="booking-total-row">
                    <span class="booking-label">Total</span>
                    <span id="total-cost" class="booking-value">$ 0.00</span>
                </div>
            </div>
        </div>
    </div>
    <!-- <div id="coupon-modal" class="coupon-modal" style="display: none;">
        <div class="coupon-modal-content">
            <span class="coupon-close-modal" style="cursor: pointer;">&times;</span>
            <h2>Available Coupons</h2>
            <ul id="coupon-list">
                
            </ul>
        </div>
    </div> -->

</div>