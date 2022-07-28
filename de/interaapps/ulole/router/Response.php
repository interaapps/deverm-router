<?php

namespace de\interaapps\ulole\router;

class Response {

    public function __construct(
        private readonly Router $router,
    ) {
    }

    public function json(mixed $object): string {
        header('Content-Type: application/json');
        echo $this->router->getJsonPlus()->toJson($object);
        return $this;
    }

    public function setContentType(string $type): Response {
        $this->setHeader('Content-Type: ', $type);
        return $this;
    }

    public function setCode(int $code): Response {
        http_response_code($code);
        return $this;
    }

    public function setNotFound(): Response {
        header("HTTP/1.0 404 Not Found");
        return $this;
    }

    public function setHeader(string $header, mixed $value = false): Response {
        if ($value === false)
            header($header);
        else
            header($header . ": " . $value);
        return $this;
    }

    public function setHeaders(array $header): Response {
        foreach ($header as $name => $value)
            header($name . ": " . $value);
        return $this;
    }

    public function setCookie(string $name, string $value = "", int $expiresOrOptions = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false): Response {
        setcookie($name, $value, $expiresOrOptions, $path, $domain, $secure, $httponly);
        return $this;
    }

    public function redirect($link, $code = 307): void {
        @ob_clean();
        http_response_code($code);
        header("Location: " . $link);
        echo "<title>Redirecting to " . $link . "</title>";
        echo '<meta http-equiv="refresh" content="0;url=' . $link . '">';
        echo "<script>window.location.replace('", $link, "')</script>";
        echo "<a href='" . $link . "'>CLICK HERE</title>";
        exit();
    }
}
