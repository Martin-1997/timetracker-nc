<?php

declare(strict_types=1);

namespace OCA\TimeTracker\Tests\Unit\Controller;

use OCA\TimeTracker\Controller\TimerController;
use OCA\TimeTracker\Db\ProjectMapper;
use OCA\TimeTracker\Db\TagMapper;
use OCA\TimeTracker\Db\WorkIntervalMapper;
use OCA\TimeTracker\Db\WorkIntervalToTagMapper;
use OCA\TimeTracker\Db\WorkInterval;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TimerController.
 *
 * All database-layer dependencies are replaced with PHPUnit mocks so these
 * tests run without a Nextcloud instance or a database connection.
 *
 * Test coverage areas:
 *  - start() name validation and WorkInterval assembly
 *  - stop() duration calculation and running-flag flip
 *  - destroy() delegation to mapper
 *  - update() cost string-to-cents conversion
 */
class TimerControllerTest extends TestCase
{
    private string $userId = 'testuser';

    /** @var IRequest&MockObject */
    private IRequest $request;

    /** @var IL10N&MockObject */
    private IL10N $l10n;

    /** @var WorkIntervalMapper&MockObject */
    private WorkIntervalMapper $workIntervalMapper;

    /** @var ProjectMapper&MockObject */
    private ProjectMapper $projectMapper;

    /** @var TagMapper&MockObject */
    private TagMapper $tagMapper;

    /** @var WorkIntervalToTagMapper&MockObject */
    private WorkIntervalToTagMapper $workIntervalToTagMapper;

    private TimerController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a stub IRequest that supports property-style access used by
        // the controller (e.g. $this->request->name, $this->request->tags).
        // We use a custom anonymous class so __get / __isset can be overridden
        // per test without breaking the interface contract.
        $this->request = $this->createRequestStub();

        $this->l10n = $this->createMock(IL10N::class);

        $this->workIntervalMapper     = $this->createMock(WorkIntervalMapper::class);
        $this->projectMapper          = $this->createMock(ProjectMapper::class);
        $this->tagMapper              = $this->createMock(TagMapper::class);
        $this->workIntervalToTagMapper = $this->createMock(WorkIntervalToTagMapper::class);

        $this->controller = new TimerController(
            'timetracker',
            $this->request,
            $this->userId,
            $this->l10n,
            $this->workIntervalMapper,
            $this->projectMapper,
            $this->tagMapper,
            $this->workIntervalToTagMapper
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a configurable IRequest stub that exposes named parameters via the
     * magic __get interface used throughout TimerController.
     *
     * @param array<string, mixed> $params Parameters to expose as properties.
     */
    private function createRequestStub(array $params = []): IRequest
    {
        return new class($params) implements IRequest {
            public function __construct(private array $params) {}

            public function __get(string $name): mixed
            {
                return $this->params[$name] ?? null;
            }

            public function __isset(string $name): bool
            {
                return isset($this->params[$name]);
            }

            // --- IRequest interface stubs (unused in unit tests) ---
            public function getHeader(string $name): string { return ''; }
            public function getParam(string $key, $default = null) { return $this->params[$key] ?? $default; }
            public function getParams(): array { return $this->params; }
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
        };
    }

    /**
     * Rebuild the controller under test with a fresh IRequest stub that
     * exposes the given parameters.
     */
    private function rebuildControllerWithParams(array $params): void
    {
        $this->request = $this->createRequestStub($params);
        $this->controller = new TimerController(
            'timetracker',
            $this->request,
            $this->userId,
            $this->l10n,
            $this->workIntervalMapper,
            $this->projectMapper,
            $this->tagMapper,
            $this->workIntervalToTagMapper
        );
    }

    // =========================================================================
    // 3.  start()
    // =========================================================================

    /**
     * start() rejects names longer than 255 characters.
     */
    public function testStartRejectsLongName(): void
    {
        $longName = str_repeat('a', 256);

        $this->workIntervalMapper->expects($this->never())->method('insert');

        $this->rebuildControllerWithParams(['name' => $longName]);
        $response = $this->controller->start();
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Error', $response->getData());
    }

    /**
     * start() with a valid name calls mapper->insert() and returns a
     * running WorkInterval.
     */
    public function testStartInsertsWorkInterval(): void
    {
        $name = 'My task';

        // findLatestByName returns null → no inherited project/tags
        $this->workIntervalMapper->method('findLatestByName')->willReturn(null);

        $capturedInterval = null;
        $this->workIntervalMapper
            ->expects($this->once())
            ->method('insert')
            ->willReturnCallback(function (WorkInterval $wi) use (&$capturedInterval) {
                $wi->id = 123;
                $capturedInterval = $wi;
            });

        // No tags from request
        $this->workIntervalToTagMapper->expects($this->never())->method('insert');

        $this->rebuildControllerWithParams(['name' => $name]);
        $response = $this->controller->start();
        $data = $response->getData();

        $this->assertSame(1, $capturedInterval->running);
        $this->assertSame($name, $capturedInterval->name);
        $this->assertSame($this->userId, $capturedInterval->userUid);
        $this->assertSame(1, $data['running']);
    }

    /**
     * start() inherits projectId from the last interval with the same name
     * when no projectId is provided in the request.
     */
    public function testStartInheritsProjectIdFromPreviousInterval(): void
    {
        $lastInterval = new WorkInterval();
        $lastInterval->projectId = 77;

        $this->workIntervalMapper->method('findLatestByName')->willReturn($lastInterval);

        $capturedInterval = null;
        $this->workIntervalMapper
            ->method('insert')
            ->willReturnCallback(function (WorkInterval $wi) use (&$capturedInterval) {
                $wi->id = 1;
                $capturedInterval = $wi;
            });

        // findAllForWorkInterval returns empty → no tags to copy
        $this->workIntervalToTagMapper->method('findAllForWorkInterval')->willReturn([]);

        $this->rebuildControllerWithParams(['name' => 'task']);
        $this->controller->start();
        $this->assertSame(77, $capturedInterval->projectId);
    }

    /**
     * start() reads name from request body params, no URL decoding needed.
     * Body params with special characters are passed as-is.
     */
    public function testStartReadsNameFromRequestParams(): void
    {
        $name = 'My Task & More';

        $this->workIntervalMapper->method('findLatestByName')->willReturn(null);

        $capturedInterval = null;
        $this->workIntervalMapper
            ->method('insert')
            ->willReturnCallback(function (WorkInterval $wi) use (&$capturedInterval) {
                $wi->id = 1;
                $capturedInterval = $wi;
            });

        $this->rebuildControllerWithParams(['name' => $name]);
        $this->controller->start();
        $this->assertSame($name, $capturedInterval->name);
    }

    // =========================================================================
    // 4.  stop()
    // =========================================================================

    /**
     * stop() rejects names longer than 255 characters.
     */
    public function testStopRejectsLongName(): void
    {
        $longName = str_repeat('z', 256);
        $this->workIntervalMapper->expects($this->never())->method('update');

        $this->rebuildControllerWithParams(['name' => $longName]);
        $response = $this->controller->stop();
        $this->assertArrayHasKey('Error', $response->getData());
    }

    /**
     * stop() sets running = 0, calculates duration, and updates each
     * running interval.
     */
    public function testStopStopsAllRunningIntervals(): void
    {
        $wi = new WorkInterval();
        $wi->id = 10;
        $wi->start = time() - 3600; // started 1 hour ago
        $wi->name = 'old description';

        $this->workIntervalMapper->method('findAllRunning')->willReturn([$wi]);

        $capturedUpdate = null;
        $this->workIntervalMapper
            ->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (WorkInterval $w) use (&$capturedUpdate) {
                $capturedUpdate = $w;
            });

        $this->rebuildControllerWithParams(['name' => 'new description']);
        $this->controller->stop();

        $this->assertSame(0, $capturedUpdate->running);
        // Duration should be approximately 3600 seconds (allow a few seconds slack).
        $this->assertGreaterThanOrEqual(3595, $capturedUpdate->duration);
        $this->assertLessThanOrEqual(3605, $capturedUpdate->duration);
        // Name is updated when not 'no description'.
        $this->assertSame('new description', $capturedUpdate->name);
    }

    /**
     * stop() preserves the existing name when the supplied name is exactly
     * 'no description'.
     */
    public function testStopPreservesNameWhenNoDescription(): void
    {
        $wi = new WorkInterval();
        $wi->id = 11;
        $wi->start = time() - 60;
        $wi->name = 'My original task';

        $this->workIntervalMapper->method('findAllRunning')->willReturn([$wi]);

        $capturedUpdate = null;
        $this->workIntervalMapper
            ->method('update')
            ->willReturnCallback(function (WorkInterval $w) use (&$capturedUpdate) {
                $capturedUpdate = $w;
            });

        $this->rebuildControllerWithParams(['name' => 'no description']);
        $this->controller->stop();
        $this->assertSame('My original task', $capturedUpdate->name);
    }

    // =========================================================================
    // 5.  destroy()
    // =========================================================================

    /**
     * destroy() must call mapper->find() then mapper->delete()
     * with the retrieved entity.
     */
    public function testDestroyCallsDelete(): void
    {
        $wi = new WorkInterval();
        $wi->id = 55;

        $this->workIntervalMapper->method('find')->with(55)->willReturn($wi);
        $this->workIntervalMapper->expects($this->once())->method('delete')->with($wi);
        $this->workIntervalMapper->method('findAllRunning')->willReturn([]);

        $response = $this->controller->destroy(55);
        $this->assertInstanceOf(JSONResponse::class, $response);
    }

    // =========================================================================
    // 6.  update() — cost handling
    // =========================================================================

    /**
     * update() converts a cost string with a comma decimal separator
     * to cents (integer × 100).
     */
    public function testUpdateCostCommaConvertedToCents(): void
    {
        $wi = new WorkInterval();
        $wi->id = 20;
        $this->workIntervalMapper->method('find')->willReturn($wi);
        $this->workIntervalMapper->method('findAllRunning')->willReturn([]);

        $capturedUpdate = null;
        $this->workIntervalMapper
            ->method('update')
            ->willReturnCallback(function (WorkInterval $w) use (&$capturedUpdate) {
                $capturedUpdate = $w;
            });

        $this->rebuildControllerWithParams(['cost' => '12,50']);

        $response = $this->controller->update(20);
        $this->assertInstanceOf(JSONResponse::class, $response);
        // 12.50 EUR → 1250 cents
        $this->assertSame(1250, $capturedUpdate->cost);
    }

    /**
     * update() handles a dot as decimal separator normally.
     */
    public function testUpdateCostDotConvertedToCents(): void
    {
        $wi = new WorkInterval();
        $wi->id = 21;
        $this->workIntervalMapper->method('find')->willReturn($wi);
        $this->workIntervalMapper->method('findAllRunning')->willReturn([]);

        $capturedUpdate = null;
        $this->workIntervalMapper
            ->method('update')
            ->willReturnCallback(function (WorkInterval $w) use (&$capturedUpdate) {
                $capturedUpdate = $w;
            });

        $this->rebuildControllerWithParams(['cost' => '99.99']);

        $this->controller->update(21);
        $this->assertSame(9999, $capturedUpdate->cost);
    }

    /**
     * update() sets cost to null when the cost field is empty string.
     */
    public function testUpdateCostEmptyStringSetsNull(): void
    {
        $wi = new WorkInterval();
        $wi->id = 22;
        $wi->cost = 5000;
        $this->workIntervalMapper->method('find')->willReturn($wi);
        $this->workIntervalMapper->method('findAllRunning')->willReturn([]);

        $capturedUpdate = null;
        $this->workIntervalMapper
            ->method('update')
            ->willReturnCallback(function (WorkInterval $w) use (&$capturedUpdate) {
                $capturedUpdate = $w;
            });

        $this->rebuildControllerWithParams(['cost' => '']);

        $this->controller->update(22);
        $this->assertNull($capturedUpdate->cost);
    }

    /**
     * update() silently ignores a non-numeric cost string (does not
     * call setCost at all, so the original value is preserved).
     */
    public function testUpdateNonNumericCostIsIgnored(): void
    {
        $wi = new WorkInterval();
        $wi->id = 23;
        $wi->cost = 200; // existing cost
        $this->workIntervalMapper->method('find')->willReturn($wi);
        $this->workIntervalMapper->method('findAllRunning')->willReturn([]);

        $capturedUpdate = null;
        $this->workIntervalMapper
            ->method('update')
            ->willReturnCallback(function (WorkInterval $w) use (&$capturedUpdate) {
                $capturedUpdate = $w;
            });

        $this->rebuildControllerWithParams(['cost' => 'abc']);

        $this->controller->update(23);
        // The original cost should be preserved since 'abc' is not numeric.
        $this->assertSame(200, $capturedUpdate->cost);
    }

    /**
     * update() rejects names longer than 255 characters and returns
     * an Error response without calling update().
     */
    public function testUpdateRejectsLongName(): void
    {
        $wi = new WorkInterval();
        $wi->id = 30;
        $this->workIntervalMapper->method('find')->willReturn($wi);

        $this->workIntervalMapper->expects($this->never())->method('update');

        $this->rebuildControllerWithParams(['name' => str_repeat('x', 256)]);

        $response = $this->controller->update(30);
        $this->assertArrayHasKey('Error', $response->getData());
    }

    /**
     * update() rejects details longer than 1024 characters.
     */
    public function testUpdateRejectsLongDetails(): void
    {
        $wi = new WorkInterval();
        $wi->id = 31;
        $this->workIntervalMapper->method('find')->willReturn($wi);

        $this->workIntervalMapper->expects($this->never())->method('update');

        $this->rebuildControllerWithParams(['details' => str_repeat('x', 1025)]);

        $response = $this->controller->update(31);
        $this->assertArrayHasKey('Error', $response->getData());
    }
}
