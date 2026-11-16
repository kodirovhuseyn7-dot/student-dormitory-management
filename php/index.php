<?php
require_once __DIR__.'/includes/functions.php';
if (!empty($_SESSION['user'])) redirect('dashboard.php');
redirect('login.php');
