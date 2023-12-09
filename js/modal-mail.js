document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        $('#exampleModalLong').modal('show');
    }, 60000);
});


$(document).ready(function() {
    $("#btn_sent").click(function(event) {
        event.preventDefault(); // Prevent the default form submission action

        // Submit the form using AJAX
        $.ajax({
            type: "POST",
            url: "../php/send_email_modal.php",
            data: $("#contact_form").serialize(), // Serialize form data for submission
            success: function(data) {
                // Handle success, maybe show a message or whatever you'd like
                console.log('Form submitted successfully.');

                // Close the modal after form submission
                $('#exampleModalLong').modal('hide');
            },
            error: function(error) {
                // Handle error
                console.log('Error:', error);
            }
        });
    });
});

function validatePhoneNumber(input) {
        // Remove any non-numeric characters from the input
        input.value = input.value.replace(/\D/g, '');
    }