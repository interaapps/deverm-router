<?php


/*

"/"          =   Homepage
"@__404__@"  =   Page not found

(Do not use duplicated keys!)

*/

$views_dir = "../views/";
$templates_dir = "../views/templates/";


$route = [
  "/"                      =>   "homepage.php",
  "/about"                 =>   "about.php",
  "/custom/[getit][url]"   =>   "customtest.php",
  "@__404__@"              =>   "404.php"
];
