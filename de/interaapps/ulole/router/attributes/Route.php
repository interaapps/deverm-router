<?php
namespace de\interaapps\ulole\router\attributes;

#[\Attribute]
class Route
{

    public function __construct(
        public $path,
        public $method = "GET")
    {
    }
}