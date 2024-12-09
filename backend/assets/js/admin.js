document.addEventListener("DOMContentLoaded", function () {
  const deleteButtons = document.querySelectorAll(".delete-course");
  console.log(ajaxurl);
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();
      const courseId = this.getAttribute("data-course-id"); // Get the course ID from the button's data attribute
      console.log(courseId);
      if (confirm("Are you sure you want to delete this course?")) {
        // Send AJAX request to delete course
        jQuery.ajax({
          url: ajaxurl, // This is automatically defined in WordPress
          type: "POST",
          data: {
            action: "delete_course", // This matches the action in PHP
            course_id: courseId,
          },
          success: function (response) {
            if (response.success) {
              alert("Course deleted successfully!");
              location.reload(); // Refresh the page
            } else {
              alert("Error: " + response.data.message);
            }
          },
          error: function (error) {
            alert("An error occurred. Please try again.");
          },
        });
      }
    });
  });
});
