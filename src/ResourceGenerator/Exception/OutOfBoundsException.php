<?php

/**
 * @see       https://github.com/mezzio/mezzio-hal for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-hal/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Hal\ResourceGenerator\Exception;

use OutOfBoundsException as BaseOutOfBoundsException;

class OutOfBoundsException extends BaseOutOfBoundsException implements ExceptionInterface
{
}
