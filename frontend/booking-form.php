<div class="booking-form">
    <ul class="tab-list">
        <li class="tab active" data-step="1">Select Course</li>
        <li class="tab" data-step="2">Personal Details</li>
        <li class="tab" data-step="3">Transport</li>
        <li class="tab" data-step="4">Payment</li>
        <li class="tab" data-step="5">Done</li>
    </ul>

    <div class="form-container">
        <form id="booking-form" method="post" action="">
            <!-- Add nonce field here -->
            
            <!-- Step 1: Select Course -->
            <div class="form-step step-1">
                <div class=course-detail-selection>
                    <label for="course">Select Course:</label>
                    <select name="course" id="course" required>
                        <option value="">Select A Course</option>
                    </select>
                </div>

                <div class="course-detail-selection">
                    <label for="start_date">Select Start Date</label>
                    <select name="start_date" id="start-date-wrapper" required>
                        <option value="">Select a start date</option>
                    </select>
                </div>

                <div class=course-detail-selection>
                    <label for="duration">Duration</label>
                    <select name="duration" id="duration-wrapper" required>
                        <option value="">Select duration</option> 
                    </select>
                </div>
                
                <button type="button" class="next-button" data-next-step="2">Next</button>
            </div>

            <!-- Step 2: Personal Details -->
            <div class="form-step step-2" style="display: none;">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <div id="email-message" style="margin-top: 5px; color: green; display: none;"></div>
                <label for="dob">Date of birth:</label>
                <input type="date" id="dob" name="dob" required>
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>
                <label for="city">City:</label>
                <input type="text" id="city" name="city" required>
                <label for="post_code">Post Code/Zip Code:</label>
                <input type="text" id="post_code" name="post_code" required>
                <div class="country-selection">
                    <label for="country">Country:</label>
                    <select id="country" name="country" required>
                        <option value="">Select a country</option>
                    </select>
                </div>
                <label for="contact_no">Phone:</label>
                <input type="text" id="contact_no" name="contact_no" required>
                <div class="nationality-selection">
                    <label for="nationality">Nationality:</label>
                    <select id="nationality" name="nationality" required>
                        <!-- <option value="">Select a nationality</option> -->
                    </select>
                </div>
                <div class="english-level-selection">
                    <label for="english_level">Level of English:</label>
                    <select name="english_level" id="english-level" required>
                        <option value="">Select your level</option>
                        <option value="None">None</option>
                        <option value="Basic">Basic</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advance">Advance</option>
                    </select>
                </div>
                <!-- <div>
                    <label>Are you a new student?</label>
                    <div>
                        <label>
                            <input type="radio" name="existing_user" value="no"> Yes, I am a new student.
                        </label><br>
                        <label>
                            <input type="radio" name="existing_user" value="yes"> No, I am an existing student.
                        </label>
                    </div>
                </div> -->
                
                <button type="button" class="prev-button" data-prev-step="1">Back</button>
                <button type="button" class="next-button" data-next-step="3">Next</button>
            </div>
            <!-- Step 3: Transport Selection -->
            <div class="form-step step-3" style="display: none;">
                <h3>Transport Options</h3>
                <label>
                    <input type="radio" name="transport" value="yes" required> I want transport (<span id="transport-cost-display">$0</span>)
                </label><br>
                <label>
                    <input type="radio" name="transport" value="no" required> I don't want transport
                </label>
                <div>
                    <button type="button" class="prev-button" data-prev-step="2">Back</button>
                    <button type="submit" >Submit & Pay</button>
                     <!-- <button type="submit">Submit</button> -->
                </div>
            </div>
            <!-- Step 4: Payment -->
            <div class="form-step step-4" style="display: none;">
                <h3>Payment Details</h3>
                <div id="payment-element" class="StripeElement"></div> <!-- Stripe Payment Element -->
                <div id="payment-errors" role="alert" style="color: red; margin-top: 10px;"></div>
                <button type="button" class="prev-button" data-prev-step="3">Back</button>
                <button type="button" id='pay-now-button'>Pay Now</button>
            </div>
            <div class="form-step step-5" style="display: none;">
                <!-- <h3>Payment Successful!</h3>
                <p>Thank you for your booking. Your payment was processed successfully.</p>
                <div id="booking-details"></div>
                <button type="button" onclick="window.location.reload();">Back to Home</button> -->
            </div>
        </form>
        <!-- Sidebar Section -->
        <div class="sidebar">
            <h3>Course Details</h3>
            <p><strong>Selected Course:</strong> <span id="selected-course">None</span></p>
            <p><strong>Price:</strong> $<span id="course-price">0.00</span></p>
            <p><strong>Registration Fee:</strong> $<span id="registration-fee">0.00</span></p>
            <p><strong>Accommodation Fee:</strong> $<span id="accommodation-fee">0.00</span></p>
            <p><strong>Transport Cost:</strong> $<span id="transport-cost">0</span></p>
            <p><strong>Total:</strong> $<span id="total-cost">0</span></p>
            <!-- Add any other details you'd like to display here -->
        </div>
    </div>
    
</div>
