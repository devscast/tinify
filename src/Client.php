<?php

declare(strict_types=1);

namespace Devscast\Tinify;

use Devscast\Tinify\Exception\InvalidUrlException;
use Devscast\Tinify\Exception\NetworkException;
use Devscast\Tinify\Storage\StorageInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
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

    public function fromFile(string $path): Source
    {
        return $this->fromBuffer(file_get_contents($path));
    }

    public function fromBuffer(string $buffer): Source
    {
        $response = $this->upload(['body' => $buffer]);
        return $this->createSourceFromResponse($response);
    }

    public function fromUrl(string $url): Source
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidUrlException($url);
        }

        $response = $this->upload(['json' => ['source' => ['url' => $url]]]);
        return $this->createSourceFromResponse($response);
    }

    public function preserve(Source $source, array $metadata = []): Source
    {
        $response = $this->upload(
            option: ['json' => ['preserve' => $metadata]],
            url: $source->getLocation()
        );
        return $this->createSourceFromResponse($response);
    }

    public function resize(Source $source, string $method, int $width, int $height): Source
    {
        $response = $this->upload(
            option: ['json' => ['resize' => [
                'method' => $method,
                'width' => $width,
                'height' => $height
            ]]],
            url: $source->getLocation()
        );
        return $this->createSourceFromResponse($response);
    }

    public function toCloud(Source $source, string $bucket_path, StorageInterface $storage): Source
    {
        $store = array_merge($storage->getConfiguration(), ['path' => $bucket_path,]);
        $response = $this->upload(
            option: ['json' => ['store' => $store]],
            url: $source->getLocation()
        );
        return $this->createSourceFromResponse($response);
    }

    public function toFile(Source $source, string $path): void
    {
        $source = $this->download($source);
        $source->toFile($path);
    }

    public function toBuffer(Source $source): string
    {
        $source = $this->download($source);
        return $source->toBuffer();
    }

    private function download(Source $source): Source
    {
        if ($source->getLocation() !== null) {
            try {
                $response = $this->http->request('GET', url: $source->getLocation());
                return $this->createSourceFromResponse($response);
            } catch (ClientException $e) {
                $this->createExceptionFromResponse($e->getResponse());
            }
        }
        return $source;
    }

    private function upload(array $option, string $url = '/shrink'): ResponseInterface
    {
        try {
            return $this->http->request('POST', url: $url, options: $option);
        } catch (ClientException $e) {
            $this->createExceptionFromResponse($e->getResponse());
        }
    }

    private function createExceptionFromResponse(ResponseInterface $response): void
    {
        $body = $response->toArray(false);
        throw NetworkException::create(
            message: $body['message'],
            type: $body['error'],
            status: $response->getStatusCode()
        );
    }

    private function createSourceFromResponse(ResponseInterface $response): Source
    {
        return new Source(
            meta: $response->getHeaders(true),
            data: $response->getContent(true)
        );
    }
}
