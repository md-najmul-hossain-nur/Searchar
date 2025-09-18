<?php
session_start();
$_SESSION = [];
session_destroy();
?>

<script>
alert("You have been logged out.");
setTimeout(() => {
    window.location.href = '../Html/login.html';
}, 500);
</script>
