<?php

declare(strict_types=1);

namespace OCA\TimeTracker\Tests\Unit\Controller;

use OCP\IRequest;

/**
 * Test double for IRequest that mirrors Nextcloud 34's JSON-body access semantics.
 *
 * In the real Request class, __get() for unknown property names reads from
 * $items['parameters'], which is built at construction time from
 * $_GET + $_POST(form-encoded) + urlParams — JSON bodies are NEVER included.
 * JSON body parameters are only accessible via getParam(), which calls
 * getContent() to lazily parse the body.
 *
 * Consequence: $this->request->name is null for JSON POST bodies, whereas
 * $this->request->getParam('name') returns the correct value.
 *
 * This stub enforces that distinction:
 *   - __get() and __isset() always return null/false (no params array fallback).
 *   - getParam() reads from the params array passed to the constructor.
 *
 * Any controller that still uses $this->request->propertyName for body params
 * will receive null in tests and the test will fail, catching the regression
 * at write time rather than at runtime.
 */
final class FakeRequest implements IRequest
{
    public function __construct(private array $params = []) {}

    /** Body params are NOT accessible as properties — return null. */
    public function __get(string $name): mixed
    {
        return null;
    }

    /** Body params are NOT discoverable via isset() — return false. */
    public function __isset(string $name): bool
    {
        return false;
    }

    public function getParam(string $key, $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getParams(): array { return $this->params; }
    public function getHeader(string $name): string { return ''; }
    public function getMethod(): string { return 'GET'; }
    public function getUploadedFile(string $key) { return null; }
    public function getEnv(string $key) { return null; }
    public function getCookie(string $key) { return null; }
    public function passesCSRFCheck(): bool { return true; }
    public function passesStrictCookieCheck(): bool { return true; }
    public function passesLaxCookieCheck(): bool { return true; }
    public function getId(): string { return 'test-request-id'; }
    public function getRemoteAddress(): string { return '127.0.0.1'; }
    public function getServerProtocol(): string { return 'https'; }
    public function getHttpProtocol(): string { return 'https'; }
    public function getRequestUri(): string { return '/'; }
    public function getRawPathInfo(): string { return '/'; }
    public function getPathInfo() { return '/'; }
    public function getScriptName(): string { return 'index.php'; }
    public function isUserAgent(array $agent): bool { return false; }
    public function getInsecureServerHost(): string { return 'localhost'; }
    public function getServerHost(): string { return 'localhost'; }
    public function throwDecodingExceptionIfAny(): void {}
    public function getFormat(): ?string { return null; }
}
