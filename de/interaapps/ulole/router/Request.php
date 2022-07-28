<?php

namespace de\interaapps\ulole\router;

class Request {
    private mixed $params = null;
    private array $attributes;

    public function __construct(
        private readonly Router $router,
        private mixed $body,
        private array $routeVars = []
    ) {
        $this->attributes = [];
    }

    public function body() : mixed {
        return $this->body;
    }

    /**
     * @template T
     * @param class-string<T>|null $type
     * @return T
     */
    public function json(string $type = null): mixed {
        return $this->router->getJsonPlus()->fromJson($this->body, $type);
    }

    public function getRouteVar($routeVar) {
        return $this->routeVars[$routeVar];
    }

    public function getParams() {
        if ($this->params === null) {
            $this->params = $_POST;

            if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
                $this->params = (array)$this->router->getJsonPlus()->fromJson(file_get_contents('php://input'), true);
            }
        }

        return $this->params;
    }

    public function getParam($param) {
        return $this->getParams()[$param];
    }

    public function getQuery(string|false $query = false) : mixed {
        if ($query === false)
            return $_GET;
        return $_GET[$query] ?? null;
    }

    public function getUserAgent() : string {
        return $_SERVER["HTTP_USER_AGENT"];
    }

    public function getRemotePort() {
        return $_SERVER["SERVER_NAME"];
    }

    public function getPHPRemoteAddress() : string {
        return $_SERVER["REMOTE_ADDR"];
    }

    public function getRemoteAddress() : string {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

    public function getAcceptedLanguages() : string {
        return $_SERVER["HTTP_ACCEPT_LANGUAGE"];
    }

    public function getHttpCookie() : string {
        return $_SERVER["HTTP_COOKIE"];
    }

    public function getCookie($cookie) : mixed {
        return $_COOKIE[$cookie];
    }

    public function getRequestURI() : string {
        return $_SERVER["REQUEST_URI"];
    }

    public function getServerName() : string {
        return $_SERVER["SERVER_NAME"];
    }

    public function getServerPort() {
        return $_SERVER["SERVER_PORT"];
    }

    public function getHost() : string {
        return $_SERVER["HTTP_HOST"];
    }

    public function attrib(string $key, mixed $value = null) : mixed {
        if ($value !== null) {
            $this->attributes[$key] = $value;
            return $this;
        }
        return $this->attributes[$key];
    }


    public function setAttrib(string $key, mixed $value) : Request {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function setRouteVars(array $routeVars): void {
        $this->routeVars = $routeVars;
    }

    public function setBody(mixed $body): void {
        $this->body = $body;
    }
}
