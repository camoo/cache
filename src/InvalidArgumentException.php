<?php
namespace Camoo\Cache;

use Camoo\Cache\Exception\AppCacheException as Exception;
use Psr\SimpleCache\InvalidArgumentException as InterfaceInvalidArgument;

/**
 * Exception raised when cache keys are invalid.
 */
class InvalidArgumentException extends Exception implements InterfaceInvalidArgument
{
}
