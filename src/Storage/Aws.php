<?php

declare(strict_types=1);

namespace Devscast\Tinify\Storage;

/**
 * Class Aws
 * @package Devscast\Tinify\Storage
 * @author bernard-ng <bernard@devscast.tech>
 */
class Aws implements StorageInterface
{
    public function __construct(
        private string $region,
        private string $secret_access_key,
        private string $access_key_id,
        private array $option = []
    ) {
    }

    public function getConfiguration(): array
    {
        return array_merge([
            'service' => 'aws',
            'region' => $this->region,
            'aws_secret_access_key' => $this->secret_access_key,
            'aws_access_key_id' => $this->access_key_id,
        ], $this->option);
    }
}
