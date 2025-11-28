<?php


session_start(); // It starts a session where variables local to the session will be stored
function require_login() {
    if (empty($_SESSION['uid'])) {
        header("Location: /research_management/public/login.php");
        exit();
    }
} // I will use this function somewhere else to check if the user needs to be logged in.


function is_logged_in() {
    return (isset($_SESSION["uid"])) ;
}

function logout() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: /research_management/public/login.php");
    exit();
}
?>
