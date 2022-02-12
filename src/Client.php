<?php

declare(strict_types=1);

namespace Devscast\Tinify;

use Devscast\Tinify\Exception\InvalidUrlException;
use Devscast\Tinify\Exception\NetworkException;
use Devscast\Tinify\Storage\StorageInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class Client
 * @package Devscast\Tinify
 * @author bernard-ng <bernard@devscast.tech>
 */
class Client
{
    private HttpClientInterface $http;

    public function __construct(string $token, ?string $proxy = null)
    {
        $this->http = new RetryableHttpClient(
            client: HttpClient::createForBaseUri(
                baseUri: 'https://api.tinify.com',
                defaultOptions: [
                    'auth_basic' => $token,
                    'proxy' => $proxy
                ]
            ),
            strategy: new GenericRetryStrategy(delayMs: 500),
            maxRetries: 3
        );
    }

    /**
     * @throws NetworkException
     */
    public function fromFile(string $path): Source
    {
        return $this->fromBuffer((string)file_get_contents($path));
    }

    /**
     * @throws NetworkException
     */
    public function fromBuffer(string $buffer): Source
    {
        $response = $this->upload(['body' => $buffer]);
        return $this->createSourceFromResponse($response);
    }

    /**
     * @throws NetworkException
     */
    public function fromUrl(string $url): Source
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException($url);
        }

        $response = $this->upload(['json' => ['source' => ['url' => $url]]]);
        return $this->createSourceFromResponse($response);
    }

    /**
     * @throws NetworkException
     */
    public function preserve(Source $source, array $metadata = []): Source
    {
        $response = $this->upload(
            option: ['json' => ['preserve' => $metadata]],
            url: (string)$source->getLocation()
        );
        return $this->createSourceFromResponse($response);
    }

    /**
     * @throws NetworkException
     */
    public function resize(Source $source, string $method, int $width, int $height): Source
    {
        $response = $this->upload(
            option: ['json' => ['resize' => [
                'method' => $method,
                'width' => $width,
                'height' => $height
            ]]],
            url: (string)$source->getLocation()
        );
        return $this->createSourceFromResponse($response);
    }

    /**
     * @throws NetworkException
     */
    public function toCloud(Source $source, string $bucket_path, StorageInterface $storage): Source
    {
        $store = array_merge($storage->getConfiguration(), ['path' => $bucket_path,]);
        $response = $this->upload(
            option: ['json' => ['store' => $store]],
            url: (string)$source->getLocation()
        );
        return $this->createSourceFromResponse($response);
    }

    /**
     * @throws NetworkException
     */
    public function toFile(Source $source, string $path): void
    {
        $source = $this->download($source);
        $source->toFile($path);
    }

    /**
     * @throws NetworkException
     */
    public function toBuffer(Source $source): mixed
    {
        $source = $this->download($source);
        return $source->toBuffer();
    }

    /**
     * @throws NetworkException
     */
    private function download(Source $source): Source
    {
        if ($source->getLocation() !== null) {
            try {
                $response = $this->http->request('GET', url: $source->getLocation());
                return $this->createSourceFromResponse($response);
            } catch (\Throwable $e) {
                $this->createExceptionFromResponse($e);
            }
        }
        return $source;
    }

    /**
     * @throws NetworkException
     */
    private function upload(array $option, string $url = '/shrink'): ResponseInterface
    {
        try {
            return $this->http->request('POST', url: $url, options: $option);
        } catch (\Throwable $e) {
            $this->createExceptionFromResponse($e);
        }
    }

    /**
     * @throws NetworkException
     * @return never
     */
    private function createExceptionFromResponse(\Throwable $exception): void
    {
        if ($exception instanceof HttpExceptionInterface) {
            try {
                $response = $exception->getResponse();
                $body = $response->toArray(throw: false);
                throw NetworkException::create(
                    message: $body['message'],
                    type: $body['error'],
                    status: $response->getStatusCode()
                );
            } catch (\Throwable $exception) {
                throw new NetworkException($exception->getMessage());
            }
        } else {
            throw new NetworkException($exception->getMessage());
        }
    }

    /**
     * @throws NetworkException
     */
    private function createSourceFromResponse(ResponseInterface $response): Source
    {
        try {
            return new Source(
                meta: $response->getHeaders(true),
                data: $response->getContent(true)
            );
        } catch (\Throwable $e) {
            $this->createExceptionFromResponse($e);
        }
    }
}
