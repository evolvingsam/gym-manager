<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\Session;

Session::start();
Session::destroy();
header("Location: login.php");
exit;