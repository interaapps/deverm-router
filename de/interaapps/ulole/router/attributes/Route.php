<?php

namespace de\interaapps\ulole\router\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route {
    public function __construct(
        public string|array $path = "",
        public $method = "GET"
    ) {
    }
}