<?php

declare(strict_types=1);

namespace OCA\TimeTracker\Tests\Unit\Controller;

use OCA\TimeTracker\Controller\GoalController;
use OCA\TimeTracker\Db\Goal;
use OCA\TimeTracker\Db\GoalMapper;
use OCA\TimeTracker\Db\ReportItem;
use OCA\TimeTracker\Db\ReportItemMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GoalControllerTest extends TestCase
{
    private string $userId = 'testuser';

    /** @var IRequest&MockObject */
    private IRequest $request;

    /** @var ReportItemMapper&MockObject */
    private ReportItemMapper $reportItemMapper;

    /** @var GoalMapper&MockObject */
    private GoalMapper $goalMapper;

    private GoalController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createRequestStub();

        $this->reportItemMapper = $this->createMock(ReportItemMapper::class);
        $this->goalMapper       = $this->createMock(GoalMapper::class);

        $this->controller = new GoalController('timetracker', $this->request, $this->userId, $this->goalMapper, $this->reportItemMapper);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createRequestStub(array $params = []): IRequest
    {
        return new FakeRequest($params);
    }

    /**
     * Helper: create a Goal entity with common fields preset.
     */
    private function makeGoal(int $id, int $projectId, int $hours, string $interval, int $createdAt): Goal
    {
        $g = new Goal();
        $g->id = $id;
        $g->userUid = $this->userId;
        $g->projectId = $projectId;
        $g->projectName = 'Project ' . $projectId;
        $g->hours = $hours;
        $g->interval = $interval;
        $g->createdAt = $createdAt;
        return $g;
    }

    /**
     * Helper: create a ReportItem with a Unix timestamp for time.
     */
    private function makeReportItem(int $time, int $totalDuration): ReportItem
    {
        $r = new ReportItem();
        $r->time = $time;
        $r->totalDuration = $totalDuration;
        $r->name = 'entry';
        $r->project = null;
        $r->client = null;
        return $r;
    }

    // =========================================================================
    // 1.  Goals helper methods
    // =========================================================================

    /**
     * getStartOfWeek() must return the ISO Monday of the given timestamp's week.
     *
     * 2024-01-15 (Monday) → week start is 2024-01-15
     * 2024-01-17 (Wednesday) → week start is 2024-01-15
     * 2024-01-21 (Sunday) → week start is 2024-01-15
     */
    public function testGetStartOfWeekReturnsMonday(): void
    {
        // Monday
        $monday = strtotime('2024-01-15 12:00:00 UTC');
        $dt = $this->controller->getStartOfWeek($monday);
        $this->assertSame('2024-01-15', $dt->format('Y-m-d'), 'Monday should be its own week start');

        // Wednesday mid-week
        $wednesday = strtotime('2024-01-17 09:00:00 UTC');
        $dt = $this->controller->getStartOfWeek($wednesday);
        $this->assertSame('2024-01-15', $dt->format('Y-m-d'), 'Wednesday should resolve to Monday of that week');

        // Sunday (last day of ISO week)
        $sunday = strtotime('2024-01-21 23:59:59 UTC');
        $dt = $this->controller->getStartOfWeek($sunday);
        $this->assertSame('2024-01-15', $dt->format('Y-m-d'), 'Sunday should still resolve to Monday of same ISO week');
    }

    /**
     * getStartOfWeek() time component must be zeroed.
     */
    public function testGetStartOfWeekTimeMidnight(): void
    {
        $ts = strtotime('2024-03-20 18:45:30 UTC');
        $dt = $this->controller->getStartOfWeek($ts);
        $this->assertSame('00:00:00', $dt->format('H:i:s'));
    }

    /**
     * getStartOfMonth() must return the first day of the given timestamp's month
     * with time zeroed.
     */
    public function testGetStartOfMonthReturnsFirstDay(): void
    {
        $ts = strtotime('2024-07-19 14:30:00 UTC');
        $dt = $this->controller->getStartOfMonth($ts);
        $this->assertSame('2024-07-01', $dt->format('Y-m-d'));
        $this->assertSame('00:00:00', $dt->format('H:i:s'));
    }

    /**
     * getStartOfMonth() on the first day of the month should return that day.
     */
    public function testGetStartOfMonthOnFirstDayIsIdempotent(): void
    {
        $ts = strtotime('2024-01-01 00:00:01 UTC');
        $dt = $this->controller->getStartOfMonth($ts);
        $this->assertSame('2024-01-01', $dt->format('Y-m-d'));
    }

    /**
     * getWeeksSince() must return one entry per completed week between the
     * given start week and the current week (exclusive).
     *
     * We use a fixed timestamp four weeks ago (relative to now) so the count
     * can be checked deterministically.
     */
    public function testGetWeeksSinceCountsCompletedWeeks(): void
    {
        // Find the Monday four full weeks ago (from the current week's Monday).
        $nowWeekStart = $this->controller->getStartOfWeek(time());
        $fourWeeksAgoTs = $nowWeekStart->getTimestamp() - (4 * 7 * 24 * 3600);

        $weeks = $this->controller->getWeeksSince($fourWeeksAgoTs);

        // Should be exactly 4 completed weeks (current week is excluded).
        $this->assertCount(4, $weeks, 'Should return exactly 4 past weeks');
        // Each entry is a Y-m-d string representing a Monday.
        foreach ($weeks as $w) {
            $dt = new \DateTime($w);
            $this->assertSame('1', $dt->format('N'), "Week entry '{$w}' should be a Monday (ISO day 1)");
        }
    }

    /**
     * getWeeksSince() with a timestamp inside the current week returns empty.
     */
    public function testGetWeeksSinceCurrentWeekReturnsEmpty(): void
    {
        // A timestamp in the current week's Monday (start of current week)
        $nowWeekStart = $this->controller->getStartOfWeek(time());
        $weeks = $this->controller->getWeeksSince($nowWeekStart->getTimestamp());
        $this->assertSame([], $weeks);
    }

    /**
     * getMonthsSince() must return one entry per completed calendar month
     * between the start and the current month (exclusive).
     */
    public function testGetMonthsSinceCountsCompletedMonths(): void
    {
        $nowMonthStart = $this->controller->getStartOfMonth(time());
        // Go back 3 months
        $threeMonthsAgo = (clone $nowMonthStart)->modify('-3 months');

        $months = $this->controller->getMonthsSince($threeMonthsAgo->getTimestamp());

        $this->assertCount(3, $months, 'Should return exactly 3 past months');
        foreach ($months as $m) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $m, "Month entry '{$m}' should be Y-m format");
        }
    }

    /**
     * getMonthsSince() with a timestamp in the current month returns empty.
     */
    public function testGetMonthsSinceCurrentMonthReturnsEmpty(): void
    {
        $nowMonthStart = $this->controller->getStartOfMonth(time());
        $months = $this->controller->getMonthsSince($nowMonthStart->getTimestamp());
        $this->assertSame([], $months);
    }

    // =========================================================================
    // 2.  getGoals() — business logic
    // =========================================================================

    /**
     * getGoals() with no goals returns an empty Goals array.
     */
    public function testGetGoalsReturnsEmptyWhenNoGoals(): void
    {
        $this->goalMapper->method('findAll')->willReturn([]);
        $response = $this->controller->index();
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame([], $response->getData()['Goals']);
    }

    /**
     * getGoals() with a weekly goal and no worked hours:
     * - workedHoursCurrentPeriod must be 0
     * - remainingHours must equal the goal hours
     * - debtHours covers all past (completed) weeks × goal hours
     */
    public function testGetGoalsWeeklyNoWork(): void
    {
        // Create the goal 2 complete weeks before the current week's Monday.
        $nowWeekStart = $this->controller->getStartOfWeek(time());
        $twoWeeksAgoTs = $nowWeekStart->getTimestamp() - (2 * 7 * 24 * 3600);

        $goal = $this->makeGoal(1, 42, 8, 'weekly', $twoWeeksAgoTs);

        $this->goalMapper->method('findAll')->willReturn([$goal]);
        // Report returns no items — no work logged.
        $this->reportItemMapper->method('report')->willReturn([]);

        $response = $this->controller->index();
        $data = $response->getData()['Goals'];

        $this->assertCount(1, $data);
        $rgoal = $data[0];

        $this->assertEquals(0, $rgoal['workedHoursCurrentPeriod']);
        // Note: round(8 - 0/3600, 2) returns int 8 in PHP when no division
        // produces a fractional result, so we use assertEquals not assertSame.
        $this->assertEquals(8, $rgoal['remainingHours']);
        // 2 past weeks × 8 hours = 16 hours debt
        // PHP's round() returns int when there is no fractional part.
        $this->assertEquals(16, $rgoal['debtHours']);
        // totalRemaining = debt + goal hours for current period - worked current
        // = 16 + 8 - 0 = 24
        $this->assertEquals(24, $rgoal['totalRemainingHours']);
    }

    /**
     * getGoals() with a weekly goal where some hours were worked this week.
     * Verifies workedHoursCurrentPeriod and remainingHours are computed correctly.
     */
    public function testGetGoalsWeeklyWithWorkThisWeek(): void
    {
        $nowWeekStart = $this->controller->getStartOfWeek(time());
        // Goal created exactly at the start of the current week (no past weeks).
        $goal = $this->makeGoal(1, 5, 10, 'weekly', $nowWeekStart->getTimestamp());

        $this->goalMapper->method('findAll')->willReturn([$goal]);

        // A report item in the current week: 18000 seconds = 5 hours.
        $item = $this->makeReportItem($nowWeekStart->getTimestamp() + 3600, 18000);
        $this->reportItemMapper->method('report')->willReturn([$item]);

        $response = $this->controller->index();
        $data = $response->getData()['Goals'];
        $rgoal = $data[0];

        // 18000 / 3600 = 5 (integer division in PHP), round returns int 5
        $this->assertEquals(5, $rgoal['workedHoursCurrentPeriod']);
        // 10 - 5 = 5 (integer); round returns int 5
        $this->assertEquals(5, $rgoal['remainingHours']); // 10 - 5
        $this->assertEquals(0, $rgoal['debtHours']);      // no past weeks
        $this->assertEquals(5, $rgoal['totalRemainingHours']); // 0 + 10 - 5
    }

    /**
     * Regression test: getGoals() must be case-insensitive for the interval
     * field.  Before the fix, 'Weekly' (capital W) was not matched and the
     * reportItemMapper was never called.
     */
    public function testGetGoalsWeeklyIntervalCaseInsensitive(): void
    {
        $nowWeekStart = $this->controller->getStartOfWeek(time());
        // Use 'Weekly' with capital W — the original bug.
        $goal = $this->makeGoal(1, 7, 4, 'Weekly', $nowWeekStart->getTimestamp());

        $this->goalMapper->method('findAll')->willReturn([$goal]);
        $this->reportItemMapper->method('report')->willReturn([]);

        // Without the strtolower fix this would return interval/report [] and
        // debtHours/workedHours would be absent or wrong. Verify the response
        // is a valid Goals array (not an empty fallback).
        $response = $this->controller->index();
        $data = $response->getData()['Goals'];

        $this->assertCount(1, $data);
        // workedHoursCurrentPeriod should be 0 (int or 0.0 float), not absent
        $this->assertArrayHasKey('workedHoursCurrentPeriod', $data[0]);
        $this->assertEquals(0, $data[0]['workedHoursCurrentPeriod']);
    }

    /**
     * Regression test: monthly interval is also case-insensitive ('Monthly').
     */
    public function testGetGoalsMonthlyIntervalCaseInsensitive(): void
    {
        $nowMonthStart = $this->controller->getStartOfMonth(time());
        $goal = $this->makeGoal(1, 9, 40, 'Monthly', $nowMonthStart->getTimestamp());

        $this->goalMapper->method('findAll')->willReturn([$goal]);
        $this->reportItemMapper->method('report')->willReturn([]);

        $response = $this->controller->index();
        $data = $response->getData()['Goals'];
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('workedHoursCurrentPeriod', $data[0]);
        $this->assertEquals(0, $data[0]['workedHoursCurrentPeriod']);
    }

    /**
     * An unrecognised interval string produces zero worked/debt hours (safe
     * default: both $repItems and $intervals end up empty).
     */
    public function testGetGoalsUnknownIntervalIsNoop(): void
    {
        $goal = $this->makeGoal(1, 3, 8, 'biweekly', time());
        $this->goalMapper->method('findAll')->willReturn([$goal]);
        // report() must NOT be called because the interval is unknown.
        $this->reportItemMapper->expects($this->never())->method('report');

        $response = $this->controller->index();
        $data = $response->getData()['Goals'];
        $this->assertCount(1, $data);
        $this->assertEquals(0, $data[0]['workedHoursCurrentPeriod']);
        $this->assertEquals(0, $data[0]['debtHours']);
    }

    /**
     * getGoals() returns expected static fields alongside the calculated ones.
     */
    public function testGetGoalsReturnsStaticFields(): void
    {
        $nowWeekStart = $this->controller->getStartOfWeek(time());
        $goal = $this->makeGoal(42, 99, 20, 'weekly', $nowWeekStart->getTimestamp());

        $this->goalMapper->method('findAll')->willReturn([$goal]);
        $this->reportItemMapper->method('report')->willReturn([]);

        $response = $this->controller->index();
        $rgoal = $response->getData()['Goals'][0];

        $this->assertSame(42, $rgoal['id']);
        $this->assertSame($this->userId, $rgoal['userUid']);
        $this->assertSame(99, $rgoal['projectId']);
        $this->assertSame(20, $rgoal['hours']);
        $this->assertSame('weekly', $rgoal['interval']);
    }
}
