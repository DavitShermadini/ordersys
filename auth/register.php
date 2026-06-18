<?php
// Public registration is disabled — accounts are created by admin
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
redirect('/auth/login.php');
