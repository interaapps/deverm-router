<?php
namespace de\interaapps\ulole\router;

class Router {
    private $routes;
    private $includeDirectory;
    private $namespace = "\\";
    private $paramsInterceptor;
    
    public function __construct() {
        $this->routes = [];
        $this->includeDirectory = './';
        $this->matchInterceptor = function($matches){
            $body = "";
            if (defined('STDIN'))
                $body = stream_get_contents(STDIN);
            $request = new Request($body, $matches["routeVars"]);
            $response = new Response;
            
            return [$request, $response]; // Return params or false (intercepts)
        };
    }

    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        foreach ($this->routes as $path=>$route) {
            $matches = $this->matches($path);
            if ($matches !== false && isset($route[$requestMethod])) {
                $currentRoute = $route[$requestMethod];
                
                $params = ($this->matchInterceptor)($matches);
                if ($params !== false) {
                    $out = $this->invoke($currentRoute, array_merge($params, $matches['routeVars']));
                    if (is_string($out))
                        echo $out;
                    else if ($out == null) {
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode($out);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function matches($url){
        $request = strtok($_SERVER["REQUEST_URI"], '?');
        $matches = [];

        if(preg_match_all('#^' . $url . '$#', $request, $matches)) {
            $routeVars = [];
            foreach ($matches as $key=>$val) {
                if (isset($val[0]) && $val[0] != $request)
                    $routeVars[$key] = $val[0];
            }
            return [
                'routeVars' => $routeVars,
                'url'       => $url,
                'matches'   => $matches
            ];
        }

        return false;
    }

    public function invoke($route, $params = []){
        if (is_callable($route)) {
            return call_user_func_array($route, $params);
        } else if(
            is_string($route) &&
            substr($route, 0, 1) === '!'
        ) {
            call_user_func(str_replace("!", "", $route));
        } else if (
            is_string($route) &&
            strpos($route, "@") !== false
        ) {
            $parts = explode("@", $route);
            $clazz  = $parts[0];
            if (substr($clazz, 0, 1) !== '\\') {
                $clazz = $this->namespace.$clazz;
            }

            $method = $parts[1];
            
            if (is_callable([$clazz, $method])) {
                if ((new \ReflectionMethod($clazz, $method))->isStatic()) {
                    return call_user_func_array([$clazz, $method], $params);
                } else {
                    return call_user_func_array([new $clazz(), $method], $params);
                }
            }
        } else {
            return (include $this->includeDirectory.'/'.$route);
        }
    }

    public function addRoute($route, $methods, $callable){
        if (!isset($this->routes[$route]))
            $this->routes[$route] = [];
        if (strpos($methods, "|") !== false) {
            foreach (explode("|", $methods) as $method)
                $this->routes[$route][$method] = $callable;
        } else
            $this->routes[$route][$methods] = $callable;

        return $this;
    }

    public function get($route, $callable){
        return $this->addRoute($route, 'GET', $callable);
    }
    public function post($route, $callable){
        return $this->addRoute($route, 'POST', $callable);
    }
    public function put($route, $callable){
        return $this->addRoute($route, 'PUT', $callable);
    }
    public function patch($route, $callable){
        return $this->addRoute($route, 'PATCH', $callable);
    }
    public function delete($route, $callable){
        return $this->addRoute($route, 'DELETE', $callable);
    }

    public function setIncludeDirectory($includeDirectory){
        $this->includeDirectory = $includeDirectory;
        return $this;
    }

    public function setMatchInterceptor($matchInterceptor){
        $this->matchInterceptor = $matchInterceptor;
        return $this;
    }

    public function setNamespace($namespace){
        $this->namespace = $namespace."\\";
        return $this;
    }


  
}
