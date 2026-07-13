<?php
session_start();
$_SESSION = [];
session_destroy();
header('Location: index.php?msg=logout&clerk_signout=1');
exit;
?>
