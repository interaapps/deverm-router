<?php
namespace de\interaapps\ulole\router\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class RouteVar {
    public function __construct(
        public string $name
    ) {
    }
}