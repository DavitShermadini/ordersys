<?php
// Smart home router — no HTML, just redirect to the right dashboard
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) redirect('/auth/login.php');
if (isAdmin())     redirect('/admin/index.php');
redirect('/products/index.php');
