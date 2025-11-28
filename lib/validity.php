<?php
// validation.php - Form validation helpers

// Check if an email is valid
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Check if a field is empty
function is_not_empty($value) {
    return !empty(trim($value)); // Trim spaces and check if it's not empty
}

// Check if a string length is within a given range
function is_length_between($value, $min, $max) {
    $length = strlen($value);
    return $length >= $min && $length <= $max;
}
?>
