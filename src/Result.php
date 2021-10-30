<?php

declare(strict_types=1);

namespace Devscast\Tinify;

/**
 * Class Result
 * @package Devscast\Tinify
 * @author bernard-ng <bernard@devscast.tech>
 */
class Result
{
    public function __construct(protected array $meta, protected string $data)
    {
    }
}
