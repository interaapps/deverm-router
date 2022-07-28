<?php
namespace de\interaapps\ulole\router\attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_CLASS|Attribute::IS_REPEATABLE)]
class With {
    public function __construct(
        public string|array $name
    ) {
        if (is_string($name))
            $this->name = [$name];
    }
}