<?php
// Start the session so that the existing session data becomes accessible.
// This is required before calling session_unset() or session_destroy().
session_start();

// Clear all variables stored in the $_SESSION superglobal array.
session_unset();

// Destroy the session itself on the server — removes the session file and invalidates the session ID.
// session_unset() clears the data; session_destroy() removes the session entirely.
session_destroy();

// Redirect the user to the login page now that they are fully logged out.
header("Location: login.php");
exit(); // Stop further PHP execution after the redirect header is sent.
?>