<?php

declare(strict_types=1);

namespace Devscast\Tinify;

/**
 * Class Source
 * @package Devscast\Tinify
 * @author bernard-ng <bernard@devscast.tech>
 */
class Source
{
    public function __construct(private array $meta, private mixed $data)
    {
        $this->meta = array_combine(
            keys: array_keys($meta),
            values: array_column($meta, column_key: 0)
        );
    }

    public function toBuffer(): string
    {
        return $this->data;
    }

    public function toFile($path): int|bool
    {
        return file_put_contents($path, $this->data);
    }

    public function getSize(): int
    {
        return intval($this->meta['content-length']);
    }

    public function getMediaType(): string
    {
        return $this->meta['content-type'];
    }

    public function getWidth(): int
    {
        return intval($this->meta['image-width']);
    }

    public function getHeight(): int
    {
        return intval($this->meta['image-height']);
    }

    public function getLocation(): ?string
    {
        return $this->meta['location'] ?? null;
    }

    public function getCompressionCount(): int
    {
        return $this->meta['compression-count'] ?? 0;
    }
}
