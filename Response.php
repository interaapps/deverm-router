<?php
namespace de\interaapps\ulole\router;

class Response {
    public function json($object){
        header('Content-Type: application/json');
        echo json_encode($object);
        return $this;
    }

    public function setContentType($type) {
        header('Content-Type: '.$type);
        return $this;
    }

    public function setCode($code) {
        http_response_code($code);
        return $this;
    }

    public function setNotFound() {
        header("HTTP/1.0 404 Not Found");
        return $this;
    }

    public function setHeader($header, $value=false) {
        if ($value === false)
            header($header);
        else
            header($header.": ".$value);
        return $this;
    }
    
    public function setHeaders($header) {
        foreach ($header as $name=>$value)
            header($name.": ".$value);
        return $this;
    }

    public function setCookie($name, $value = "", $expires = 0, $path = "", $domain = "", $secure = false, $httponly = false){
        setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
        return $this;
    }

    public function redirect($link, $code=307) {
        @ob_clean();
        http_response_code($code);
        header("Location: ".$link);
        echo "<title>Redirecting to ".$link."</title>";
		echo '<meta http-equiv="refresh" content="0;url='.$link.'">';
		echo "<script>window.location.replace('",$link,"')</script>";
        echo "<a href='".$link."'>CLICK HERE</title>";
        exit();
        return $this;
    }
}
