<?php

declare(strict_types=1);

namespace Devscast\Tinify\Exception;

/**
 * Class InvalidUrlException
 * @package Devscast\Tinify\Exception
 * @author bernard-ng <bernard@devscast.tech>
 */
class InvalidUrlException extends \InvalidArgumentException
{
    public function __construct(string $url)
    {
        parent::__construct(
            message: sprintf('%s is not a valid URL', $url)
        );
    }
}
