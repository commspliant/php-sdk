<?php

declare(strict_types=1);

namespace CommsPliant\Tests;

use CommsPliant\APIException;
use CommsPliant\Client;
use CommsPliant\RenderRequest;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testRenderHtmlSuccess(): void
    {
        $client = new Client('ck_test', transport: function (string $url, array $headers, string $body): array {
            $this->assertStringEndsWith('/api/v1/render/html', $url);
            $this->assertContains('X-Api-Key: ck_test', $headers);

            return [
                'status' => 200,
                'headers' => [
                    'Content-Type' => 'text/html',
                    'X-Request-ID' => 'req-123',
                ],
                'body' => '<html>ok</html>',
            ];
        });

        $result = $client->renderHtml(new RenderRequest(
            templateId: '550e8400-e29b-41d4-a716-446655440000',
            variables: ['title' => 'Monthly Report'],
        ));

        $this->assertSame('<html>ok</html>', $result->body);
        $this->assertSame('req-123', $result->requestId);
    }

    public function testRenderPdfSuccess(): void
    {
        $client = new Client('ck_test', transport: fn (): array => [
            'status' => 200,
            'headers' => ['Content-Type' => 'application/pdf'],
            'body' => '%PDF-1.4',
        ]);

        $result = $client->renderPdf(new RenderRequest(
            templateId: '550e8400-e29b-41d4-a716-446655440000',
            variables: [],
        ));

        $this->assertStringStartsWith('%PDF', $result->body);
    }

    public function testMissingTemplateId(): void
    {
        $client = new Client('ck_test');

        $this->expectException(\InvalidArgumentException::class);
        $client->renderHtml(new RenderRequest(templateId: '', variables: []));
    }

    public function testApiError(): void
    {
        $client = new Client('ck_test', transport: fn (): array => [
            'status' => 404,
            'headers' => ['X-Request-ID' => 'req-404'],
            'body' => json_encode(['error' => 'template not found'], JSON_THROW_ON_ERROR),
        ]);

        try {
            $client->renderHtml(new RenderRequest(
                templateId: '550e8400-e29b-41d4-a716-446655440000',
                variables: [],
            ));
            $this->fail('expected APIException');
        } catch (APIException $error) {
            $this->assertSame(404, $error->statusCode);
            $this->assertSame('template not found', $error->getMessage());
            $this->assertSame('req-404', $error->requestId);
        }
    }
}
