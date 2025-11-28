<?php
// flash.php - Flash message handling

// Function to set flash message
function set_flash($message, $type = 'success') {
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type]; // Store message in session
}// We set this after a work has been done successfully.
//set_flash("Researcher added successfully!");
//In next page we set get_flash() to display the message. 

// Function to get and display flash message
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        echo "<div class='flash {$flash['type']}'>{$flash['message']}</div>"; // Display message
        unset($_SESSION['flash_message']); // Remove the flash message after displaying it
    }
}
?>
