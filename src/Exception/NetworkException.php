<?php

declare(strict_types=1);

namespace Devscast\Tinify\Exception;

/**
 * Class NetworkException
 * @package Devscast\Tinify\Exception
 * @author bernard-ng <bernard@devscast.tech>
 * @template T
 * @phpstan-template T
 */
class NetworkException extends \Exception
{
    public static function create(string $message, string $type, int $status): self
    {
        $message = empty($message) ? 'No message was provided' : $message;
        return match (true) {
            $status === 401 || $status === 429 => new AccountException($message, $type, $status),
            $status >= 400 && $status <= 499 => new ClientException($message, $type, $status),
            $status >= 500 && $status <= 599 => new ServerException($message, $type, $status),
            default => new NetworkException($message, $type, $status)
        };
    }

    public function __construct(string $message, ?string $type = null, public ?int $status = null)
    {
        if (null !== $this->status) {
            parent::__construct($message . " (HTTP " . $status . "/" . $type . ")");
        } else {
            parent::__construct($message);
        }
    }
}
