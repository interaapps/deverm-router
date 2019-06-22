<?php

/*               DEVERM-ROUTER 2.0
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);// */
chdir('..');
require "app/route.php";
require "devermrouter/Router.php";

// Autoloads the controllers
Router::autoload("app/controller");

$router = new Router($views_dir, $templates_dir);
$router->set($route);
$router->route();
