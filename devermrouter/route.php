<?php
global $template, $route, $views_dir, $templates_dir;

function tmpl($template_name) {
  global $templates_dir;
  include $templates_dir.$template_name.".php";
}


function between_as_array($str) {
  $fasdf =[];
  foreach(str_split($str) as $l) {
    if ($l=="/") {
      $repafd = get_string_between($str, "/", "");
      array_push($fasdf, $repafd);
      $str=str_replace("[".$repafd."]","",$str);
    }
  }
  return $fasdf;
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    if ($end=="") {
      return substr($string, $ini, strlen($string));
    }
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function route() {
  global $template, $route, $views_dir;
  $error404 = false;
  $request = $_SERVER['REDIRECT_URL'];
  $genrequest = $_SERVER['REDIRECT_URL'];
  foreach($route as $url=>$view) {

    $urlconv = $url;

    if(array_key_exists($request, $route)) {
      if ($url == $request) {
        require $views_dir.$view;
        return 0;
      }

    } elseif (strpos($urlconv, "[") && strpos($urlconv, "]")) {

      
      if ((substr_count($request, "/")-1) == (substr_count($urlconv, "["))) {
        $repurl = $urlconv;
        foreach(between_as_array($urlconv) as $v1=> $v2) {
          $between = get_string_between($repurl, "[","]");
          $repurl = str_replace( "[".$between."]", "", $repurl);
        }
        $between = get_string_between($repurl, "[","]");
        $repurl = str_replace("[".$between."]", "", $repurl);
        $repurl = str_replace("/","",$repurl);
        if (strpos($request, $repurl) !== false) {

          $_ROUTEVAR = [];
          foreach (getArguments($url, "/".str_replace($repurl."/","",get_string_between($request, "/", ""))) as $v11=>$v22) {
            $_ROUTEVAR[$v11] = $v22;
          }
          $genrequest = $url;
          require $views_dir.$view;
          return 1;
        }
      }
    }
  }
  if (!array_key_exists($genrequest, $route))
    $error404 = true;
  if($error404) {
    require $views_dir.$route["@__404__@"];
    return 404;
  }
}
function getArguments($str, $url2) {
    $fasdf = [];
    foreach(str_split($str) as $l) {
      if ($l=="[") {
        $repafd = get_string_between($str, "[", "]");
        array_push($fasdf, $repafd);
        $str=str_replace("[".$repafd."]","",$str);
      }
    }
    $url = $url2;
    $fasdf2 = [];
    foreach (str_split($url) as $l) {
      if ($l=="/") {
        if (substr_count($url, "/")!=1)
          $repafd2 = get_string_between($url, "/", "/");
        else $repafd2 = get_string_between($url, "/", "");
        array_push($fasdf2, $repafd2);
        $url = str_replace("/".$repafd2, "",$url);
      }
    }

    $arr = [];
    foreach($fasdf as $v=>$b) {
      $arr[$b]=$fasdf2[$v];
    }

    return $arr;
}
