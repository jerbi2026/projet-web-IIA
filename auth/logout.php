<?php

require_once '../config/auth.php';

logout();

// Rediriger vers le login avec message
header('Location: login.php?msg=logout');
exit;