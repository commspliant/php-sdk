<?php

declare(strict_types=1);

namespace CommsPliant;

final class Client
{
    private const DEFAULT_BASE_URL = 'https://api.commspliant.com';

    /** @param callable(string, array<int, string>, string): array{status:int,headers:array<string,string>,body:string}|null $transport */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly bool $useBearerAuth = false,
        private readonly int $timeoutSeconds = 60,
        private readonly mixed $transport = null,
    ) {
        if (trim($this->apiKey) === '') {
            throw new \InvalidArgumentException('api key is required');
        }
    }

    public function renderHtml(RenderRequest $request): RenderResult
    {
        return $this->postRender('/api/v1/render/html', $request);
    }

    public function renderPdf(RenderRequest $request): RenderResult
    {
        return $this->postRender('/api/v1/render/pdf', $request);
    }

    private function postRender(string $path, RenderRequest $request): RenderResult
    {
        $this->validateRenderRequest($request);

        $payload = [
            'templateId' => $request->templateId,
            'variables' => $request->variables,
        ];
        if ($request->templateVersionId !== null && $request->templateVersionId !== '') {
            $payload['templateVersionId'] = $request->templateVersionId;
        }

        $url = rtrim($this->baseUrl, '/') . $path;
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        $headers = ['Content-Type: application/json'];
        if ($this->useBearerAuth) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        } else {
            $headers[] = 'X-Api-Key: ' . $this->apiKey;
        }

        if ($this->transport !== null) {
            $transportResponse = ($this->transport)($url, $headers, $body);
            $statusCode = $transportResponse['status'];
            $parsedHeaders = array_change_key_case($transportResponse['headers'], CASE_LOWER);
            $responseBody = $transportResponse['body'];
        } else {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
            ]);

            $rawResponse = curl_exec($ch);
            if ($rawResponse === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException('request failed: ' . $error);
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            $rawHeaders = substr($rawResponse, 0, $headerSize);
            $responseBody = substr($rawResponse, $headerSize);
            $parsedHeaders = $this->parseHeaders($rawHeaders);
        }

        $requestId = $parsedHeaders['x-request-id'] ?? '';

        if ($statusCode < 200 || $statusCode >= 300) {
            throw $this->parseAPIError($statusCode, $responseBody, $requestId);
        }

        return new RenderResult(
            body: $responseBody,
            contentType: $parsedHeaders['content-type'] ?? '',
            requestId: $requestId,
            contentDisposition: $parsedHeaders['content-disposition'] ?? null,
        );
    }

    private function validateRenderRequest(RenderRequest $request): void
    {
        if (trim($request->templateId) === '') {
            throw new \InvalidArgumentException('templateId is required');
        }
        if ($request->variables === null) {
            throw new \InvalidArgumentException('variables is required');
        }
    }

    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        foreach (explode("\r\n", $rawHeaders) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }
        return $headers;
    }

    private function parseAPIError(int $statusCode, string $body, string $requestId): APIException
    {
        if ($body === '') {
            return new APIException($statusCode, 'request failed', requestId: $requestId);
        }

        try {
            $parsed = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            return new APIException(
                statusCode: $statusCode,
                message: $parsed['error'] ?? 'request failed',
                errorCode: $parsed['code'] ?? null,
                details: $parsed['details'] ?? null,
                requestId: $requestId,
            );
        } catch (\JsonException) {
            return new APIException($statusCode, $body, requestId: $requestId);
        }
    }
}
