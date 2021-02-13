<?php
namespace de\interaapps\ulole\router\attributes;

#[\Attribute]
class Controller
{
    public $pathPrefix;

    public function __construct($pathPrefix = "")
    {
        $this->pathPrefix = $pathPrefix;
    }
}