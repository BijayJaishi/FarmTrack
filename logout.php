<?php
require_once __DIR__ . '/php/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {      // logout is a state change, so POST + CSRF only
    csrf_check();
    logout_user();
    session_start();
    flash('You have been logged out.');
}
redirect('login.php');