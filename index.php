<?php
// Redirect root to public entry point
header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/public/index.php');
exit;
