<?php


class router {

  public $route;
  public $views_dir;
  public $templates;
  public $GetOrPost;

  function setRequestMethods($arr) {
    foreach ($arr as $k1=>$v1) {
      $this->GetOrPost[$k1] = $v1;
      echo $GetOrPost[$k1];
    }
  }

  function addNested($array, $path="") {
    foreach($array as $v1 => $v2) {
      if (is_array($v2)) {
        $this->addNested($v2, $path.$v1."/");
      } else {
        // echo $path.$v1;
        $this->route["/".$path.$v1] = $v2;
        //array_push($this->route, "/".$path.$v1, $v2);
      }
    }
  }

  function __construct($template, $views_dir, $route=[]) {
    $this->route     =  $route;
    $this->template  =  $template;
    $this->views_dir =  $views_dir;
  }

  function set($array) {
    $this->route = $array;
  }

  function route() {
    $route     =  $this->route;
    $template  =  $this->template;
    $views_dir =  $this->views_dir;

    $error404 = false;
    $request = str_replace("?".get_string_between($_SERVER['REQUEST_URI'], "?", ""), "", $_SERVER['REQUEST_URI']);
    $genrequest = $request;

    $method = $_SERVER['REQUEST_METHOD'];

    foreach($route as $url=>$view) {

      $urlconv = $url;

      if(array_key_exists($request, $route)) {
        if ($url == $request) {
            if($method==='POST' && isset($this->GetOrPost[$request]["post"]))
              load($view ,$views_dir.$this->GetOrPost[$request]["post"]);
            elseif($method==='DELETE' && isset($this->GetOrPost[$request]["delete"]))
              load($view,  $views_dir.$this->GetOrPost[$request]["delete"]);
            elseif($method==='PUT' && isset($this->GetOrPost[$request]["put"]))
              load($view,  $views_dir.$this->GetOrPost[$request]["put"]);
            elseif($method==='CONNECT' && isset($this->GetOrPost[$request]["connect"]))
              load($view,  $views_dir.$this->GetOrPost[$request]["connect"]);
            elseif($method==='TRACE' && isset($this->GetOrPost[$request]["trace"]))
              load($view,  $views_dir.$this->GetOrPost[$request]["trace"]);
            elseif($method==='OPTIONS' && isset($this->GetOrPost[$request]["options"]))
              load($view, $views_dir.$this->GetOrPost[$request]["options"]);
            else
              load($view, $views_dir.$view);
          //  echo "hi";
          return 0;
        }

      } elseif (strpos($urlconv, "[") && strpos($urlconv, "]")) {
    //  echo str_replace($repurl."/","",get_string_between($request, "/", ""));

        $uurl = str_replace("[".get_string_between($url, "[", ""), "", $url);
        $rrrequest = str_replace($uurl, "", $request);

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




}





function load($view, $require) {
  if (strpos($view, "!") !== false) {
    call_user_func(get_string_between($view, "!", "@").'::'.get_string_between($view, "@", ""));
  } else {
    require $require;
  }
}



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
