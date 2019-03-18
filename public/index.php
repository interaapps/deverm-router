<?php

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);// */

require "../app/route.php";
require "../devermrouter/route.php";

$router = new router($template, $views_dir);
$router->set($route);
$router->route();
