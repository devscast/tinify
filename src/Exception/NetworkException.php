<?php

declare(strict_types=1);

namespace Devscast\Tinify\Exception;

/**
 * Class NetworkException
 * @package Devscast\Tinify\Exception
 * @author bernard-ng <bernard@devscast.tech>
 */
class NetworkException extends \Exception
{
    public static function create(string $message, string $type, int $status): \Exception
    {
        return new (self::getFqcnFromStatus($status))(
            message: empty($message) ? 'No message was provided' : $message,
            type: $type,
            status: $status
        );
    }

    private static function getFqcnFromStatus(int $status): string
    {
        if ($status == 401 || $status == 429) {
            return AccountException::class;
        } elseif ($status >= 400 && $status <= 499) {
            return ClientException::class;
        } elseif ($status >= 500 && $status <= 599) {
            return ServerException::class;
        }
        return NetworkException::class;
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
