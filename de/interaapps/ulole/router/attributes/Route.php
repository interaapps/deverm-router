<?php
namespace de\interaapps\ulole\router\attributes;

#[\Attribute]
class Route
{
    public $path;
    public $method = "GET";

    public function __construct($path, $method = "GET")
    {
        $this->path = $path;
        $this->method = $method;
    }
}