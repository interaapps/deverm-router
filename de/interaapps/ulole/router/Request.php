<?php
namespace de\interaapps\ulole\router;

class Request {
    private $body;
    private $routeVars;
    private $params = null;
    private $attributes;

    public function __construct($body, $routeVars) {
        $this->body = $body;
        $this->routeVars = $routeVars;
        $this->attributes = [];
    }

    public function body(){
        return $this->body;
    }

    public function json(){
        return json_decode($this->body);
    }

    public function getRouteVar($routeVar){
        return $this->routeVars[$routeVar];
    }

    public function getParams(){
        if ($this->params === null) {
            $this->params = $_POST;

            if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false){
                $this->params = (array) json_decode(file_get_contents('php://input'), true);
            }
        }
        
        return $this->params;
    }

    public function getParam($param){
        return $this->getParams()[$param];
    }

    public function getQuery($query = false){
        if ($query === false)
            return $_GET;
        return $_GET[$query];
    }

    public function getUserAgent() {
        return $_SERVER["HTTP_USER_AGENT"];
    }

    public function getRemotePort() {
        return $_SERVER["SERVER_NAME"];
    }

    public function getPHPRemoteAddress() {
        return $_SERVER["REMOTE_ADDR"];
    }

    public function getRemoteAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ip=$_SERVER['HTTP_CLIENT_IP'];
         elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
         else
            $ip=$_SERVER['REMOTE_ADDR'];
        return $ip; 
    }

    public function getAcceptedLanguages() {
        return $_SERVER["HTTP_ACCEPT_LANGUAGE"];
    }

    public function getHttpCookie() {
        return $_SERVER["HTTP_COOKIE"];
    }

    public function getCookie($cookie){
        return $_COOKIE[$cookie];
    }

    public function getRequestURI() {
        return $_SERVER["REQUEST_URI"];
    }

    public function getServerName() {
        return $_SERVER["SERVER_NAME"];
    }

    public function getServerPort() {
        return $_SERVER["SERVER_PORT"];
    }

    public function getHost() {
        return $_SERVER["HTTP_HOST"];
    }

    public function attrib($key, $value=null){
        if ($value !== null) {
            $this->attributes[$key] = $value;
            return $this;
        }
        return $this->attributes[$key];
    }


    public function setAttrib($key, $value){
        $this->attributes[$key] = $value;
        return $this;
    }

}
