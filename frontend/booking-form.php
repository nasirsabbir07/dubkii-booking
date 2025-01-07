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
                <div class="review-container">
                    <div class="review-left-column">
                        <!-- User Details Section -->
                        <p>Please review your booking and move on to payment. Thank you.</p>
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
                                <h3 class="coupon-section-header">Apply coupon or gift card</h3>
                                <div style="margin-block: 10px; border-top:1px dashed red"></div>
                                <div class="coupons">
                                    <input type="text" id="coupon_code" name="coupon_code" placeholder="Coupon/Gift Card">
                                    <button type="button" id="apply-coupon" class="apply-coupon-btn">Apply</button>
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
                                <div class="review-booking-row">
                                    <span class="booking-label review-total-label"> Total price </span><span id="review-total-cost">$ 0.00</span>
                                </div>
                                <div style="margin-block: 10px; border-top:1px dashed red"></div>
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
                                    <div class="review-booking-row review-discount-row" style="display: none;">
                                        <span class="booking-label">Discount: </span>
                                        <span class="booking-value" id="review-discount-amount">-$ 0.00</span>
                                    </div>
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
                <div class="success-page">
                    <div class="success-icon">
                        <svg width="100px" height="100px" viewBox="0 0 117 117" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <g fill="none" fill-rule="evenodd" stroke="none" stroke-width="1">
                                <g fill-rule="nonzero">
                                    <path d="M34.5,55.1 C32.9,53.5 30.3,53.5 28.7,55.1 C27.1,56.7 27.1,59.3 28.7,60.9 L47.6,79.8 C48.4,80.6 49.4,81 50.5,81 C50.6,81 50.6,81 50.7,81 C51.8,80.9 52.9,80.4 53.7,79.5 L101,22.8 C102.4,21.1 102.2,18.5 100.5,17 C98.8,15.6 96.2,15.8 94.7,17.5 L50.2,70.8 L34.5,55.1 Z" fill="#17AB13" />
                                    <path d="M89.1,9.3 C66.1,-5.1 36.6,-1.7 17.4,17.5 C-5.2,40.1 -5.2,77 17.4,99.6 C28.7,110.9 43.6,116.6 58.4,116.6 C73.2,116.6 88.1,110.9 99.4,99.6 C118.7,80.3 122,50.7 107.5,27.7 C106.3,25.8 103.8,25.2 101.9,26.4 C100,27.6 99.4,30.1 100.6,32 C113.1,51.8 110.2,77.2 93.6,93.8 C74.2,113.2 42.5,113.2 23.1,93.8 C3.7,74.4 3.7,42.7 23.1,23.3 C39.7,6.8 65,3.9 84.8,16.2 C86.7,17.4 89.2,16.8 90.4,14.9 C91.6,13 91,10.5 89.1,9.3 Z" fill="#4A4A4A" />
                                </g>
                            </g>
                        </svg>
                    </div>
                    <h2>Payment Successful!</h2>
                    <p>Thank you for your booking.</p>
                    <div class="button-container">
                        <button class="reload">Done</button>
                    </div>
                </div>
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
    <!-- Coupon Success Modal -->
    <div id="coupon-modal" class="modal">
        <div class="modal-content">
            <h4 class="header">Coupon Applied!</h4>
            <p id="coupon-modal-message"></p>
        </div>
        <div class="modal-footer">
            <button id="close-modal" class="btn">Close</button>
        </div>
    </div>
    <!-- Coupon Details Modal -->
    <div id="coupon-details-modal" style="display: none;">
        <div class="modal-content">
            <span id="modal-close" class="close">&times;</span>
            <div id="coupon-details-content">
                <!-- Dynamic coupon details will be inserted here -->
            </div>
        </div>
    </div>
    <!-- Modal Overlay -->
    <div id="modal-overlay" class="overlay"></div>
</div>