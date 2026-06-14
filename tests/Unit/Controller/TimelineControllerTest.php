<?php

declare(strict_types=1);

namespace OCA\TimeTracker\Tests\Unit\Controller;

use OCA\TimeTracker\Controller\TimelineController;
use OCA\TimeTracker\Db\ReportItemMapper;
use OCA\TimeTracker\Db\ClientMapper;
use OCA\TimeTracker\Db\ProjectMapper;
use OCA\TimeTracker\Db\TimelineMapper;
use OCA\TimeTracker\Db\TimelineEntryMapper;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

class TimelineControllerTest extends TestCase
{
    private string $userId = 'testuser';

    private TimelineController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $request              = $this->createMock(IRequest::class);
        $l10n                 = $this->createMock(IL10N::class);
        $timelineMapper       = $this->createMock(TimelineMapper::class);
        $timelineEntryMapper  = $this->createMock(TimelineEntryMapper::class);
        $reportItemMapper     = $this->createMock(ReportItemMapper::class);
        $projectMapper        = $this->createMock(ProjectMapper::class);
        $clientMapper         = $this->createMock(ClientMapper::class);

        $this->controller = new TimelineController(
            'timetracker',
            $request,
            $this->userId,
            $l10n,
            $timelineMapper,
            $timelineEntryMapper,
            $reportItemMapper,
            $projectMapper,
            $clientMapper
        );
    }

    // =========================================================================
    // secondsToTime() — private helper tested via reflection
    // =========================================================================

    /**
     * secondsToTime() formats seconds as HH:MM:SS.
     */
    public function testSecondsToTimeFormatsCorrectly(): void
    {
        $method = new \ReflectionMethod(TimelineController::class, 'secondsToTime');
        $method->setAccessible(true);

        $this->assertSame('00:00:00', $method->invoke($this->controller, 0));
        $this->assertSame('00:01:00', $method->invoke($this->controller, 60));
        $this->assertSame('01:00:00', $method->invoke($this->controller, 3600));
        $this->assertSame('01:30:45', $method->invoke($this->controller, 5445));
        $this->assertSame('100:00:00', $method->invoke($this->controller, 360000));
    }
}
