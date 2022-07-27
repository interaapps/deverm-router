<?php
namespace de\interaapps\ulole\router\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParam {
    public function __construct(
        public string|null $name = null
    ) {
    }
}