(function($) {
    'use strict';

    // Enhanced validation
    $('#contact_form').validate({
        rules: {
            con_fname: {
                required: true,
                minlength: 2,
                maxlength: 50
            },
            con_lname: {
                required: true,
                minlength: 2,
                maxlength: 50
            },
            con_phone: {
                required: true,
                minlength: 10,
                maxlength: 15
            },
            con_message: {
                required: false,
                maxlength: 1000
            },
            con_email: {
                required: true,
                email: true,
                maxlength: 100
            }
        },

        messages: {
            con_fname: {
                required: 'First name is required.',
                minlength: 'First name must be at least 2 characters.',
                maxlength: 'First name cannot exceed 50 characters.'
            },
            con_lname: {
                required: 'Last name is required.',
                minlength: 'Last name must be at least 2 characters.',
                maxlength: 'Last name cannot exceed 50 characters.'
            },
            con_phone: {
                required: 'Phone number is required.',
                minlength: 'Phone number must be at least 10 digits.',
                maxlength: 'Phone number cannot exceed 15 digits.'
            },
            con_message: {
                maxlength: 'Message cannot exceed 1000 characters.'
            },
            con_email: {
                required: 'Email is required.',
                email: 'Please enter a valid email address.',
                maxlength: 'Email cannot exceed 100 characters.'
            }
        },

        errorElement: 'div',
        errorClass: 'form-error',
        
        submitHandler: function(form) {
            // Clear previous errors
            $('#error_message').html('').removeClass('contact-confirmation');

            // Check reCAPTCHA
            var captchaResponse = grecaptcha.getResponse();
            if (!captchaResponse || captchaResponse.length === 0) {
                $('#error_message').html('Please complete the reCAPTCHA verification.');
                $('#error_message').addClass('contact-confirmation');
                return false;
            }

            // Get form data
            var formData = {
                con_fname: $('#con_fname').val().trim(),
                con_lname: $('#con_lname').val().trim(),
                con_phone: $('#con_phone').val().trim(),
                con_message: $('#con_message').val().trim(),
                con_email: $('#con_email').val().trim(),
                'g-recaptcha-response': captchaResponse,
                action: 'sendEmail',
                honeypot_field: $('input[name="honeypot_field"]').val(),
                section: 'Contact Page'
            };

            // Update button state
            $('#btn_sent').val('Sending...').prop('disabled', true);

            // Submit via AJAX
            $.ajax({
                type: 'POST',
                url: './php/send_email.php',
                data: formData,
                dataType: 'json',
                timeout: 30000, // 30 second timeout
                
                success: function(result) {
                    console.log('Success response:', result);
                    $('#btn_sent').prop('disabled', false).val('SEND MESSAGE');
                    
                    if (result && result.response === 'success') {
                        $('#contact_form')[0].reset();
                        $('#error_message').html(result.message);
                        $('#error_message').addClass('contact-confirmation');
                        grecaptcha.reset();
                        
                        // Hide message after 5 seconds
                        setTimeout(function() {
                            $('#error_message').fadeOut();
                        }, 5000);
                        
                    } else {
                        var errorMsg = (result && result.message) ? result.message : 'An error occurred. Please try again.';
                        $('#error_message').html(errorMsg);
                        $('#error_message').addClass('contact-confirmation');
                        grecaptcha.reset();
                    }
                },
                
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    
                    $('#btn_sent').prop('disabled', false).val('SEND MESSAGE');
                    
                    var errorMessage = 'An error occurred. Please try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Service not found. Please contact support.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later.';
                    } else if (xhr.responseText) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            // Keep default error message
                        }
                    }
                    
                    $('#error_message').html(errorMessage);
                    $('#error_message').addClass('contact-confirmation');
                    grecaptcha.reset();
                }
            });
            
            return false; // Prevent normal form submission
        }
    });

}(jQuery));
