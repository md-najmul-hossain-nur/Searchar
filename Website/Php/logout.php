<?php
session_start();

if (isset($_SESSION['role'])) {
    $currentRole = $_SESSION['role'];
    if (isset($_SESSION['active_roles'][$currentRole])) {
        unset($_SESSION['active_roles'][$currentRole]);
    }
    unset($_SESSION['role']);
    unset($_SESSION['user_id']);
}

// If no active roles left at all, destroy completely
if (empty($_SESSION['active_roles'])) {
    $_SESSION = [];
    session_destroy();
}
?>
<script>
alert("You have been logged out.");
setTimeout(() => {
    window.location.href = '../Html/login.html';
}, 500);
</script>
