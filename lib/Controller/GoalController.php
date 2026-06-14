<?php
namespace OCA\TimeTracker\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\TimeTracker\Db\Goal;
use OCA\TimeTracker\Db\GoalMapper;
use OCA\TimeTracker\Db\ReportItemMapper;

class GoalController extends BaseApiController {
	protected $goalMapper;
	protected $reportItemMapper;

	public function __construct(string $AppName, IRequest $request, string $UserId, GoalMapper $goalMapper, ReportItemMapper $reportItemMapper){
		parent::__construct($AppName, $request, $UserId);
		$this->goalMapper = $goalMapper;
		$this->reportItemMapper = $reportItemMapper;
	}

	/**
	 *
	 * @NoAdminRequired
	 */
	public function create($projectId = null, $hours = null, $interval = 'weekly') {

		if (empty($projectId)) {
			return new JSONResponse(['error' => 'Project is required'], Http::STATUS_BAD_REQUEST);
		}

		$g = $this->goalMapper->findByUserProject($this->userId, $projectId);
		if ($g == null){
			$g = new Goal();
			$g->setProjectId($projectId);
			$g->setUserUid($this->userId);
			$g->setCreatedAt(time());
			$g->setHours($hours);
			$g->setInterval($interval);
			$this->goalMapper->insert($g);
		} else {
			return new JSONResponse(["Error" => "There can be only one goal per project"]);
		}

		return $this->index();
	}

	/**
	 *
	 * @NoAdminRequired
	 */
	public function destroy(int $id) {
		$c = $this->goalMapper->find($id);
		if ($c == null){
			return;
		}
		if ($c->userUid != $this->userId){
			return;
		}
		$this->goalMapper->delete($c);
		return $this->index();
	}

	public function getStartOfWeek($timestamp){
		$date = new \DateTime('@'.$timestamp);
		$weeknumber = $date->format("W");
		$year = $date->format("Y");
		$weekstartdt = new \DateTime();
		$weekstartdt->setTime(0, 0, 0, 0);
		$weekstartdt->setISODate($year, $weeknumber);

		return $weekstartdt;
	}
	public function getStartOfMonth($timestamp){
		$date = new \DateTime('@'.$timestamp);
		$date->modify('first day of this month');
		$date->setTime(0, 0, 0, 0);

		return $date;
	}

	public function getWeeksSince($timestamp){
		$start  = $this->getStartOfWeek($timestamp);
		$end = $this->getStartOfWeek(time());
		$oneWeek = \DateInterval::createFromDateString('1 week');
		$currentWeek = $start;
		$weeks = [];
		while ($currentWeek < $end){
			$weeks[] = $currentWeek->format("Y-m-d");
			$currentWeek->add($oneWeek);
		}
		return $weeks;
	}
	public function getMonthsSince($timestamp){
		$start  = $this->getStartOfMonth($timestamp);
		$end = $this->getStartOfMonth(time());
		$oneMonth = \DateInterval::createFromDateString('1 month');
		$currentMonth = $start;
		$months = [];
		while ($currentMonth < $end){
			$months[] = $currentMonth->format("Y-m");
			$currentMonth->add($oneMonth);
		}
		return $months;
	}
	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */

	public function index(){
		$goals = $this->goalMapper->findAll($this->userId);
		$weekStart = $this->getStartOfWeek(time())->format('Y-m-d');
		$monthStart = $this->getStartOfMonth(time())->format('Y-m');

		$ret = [];
		foreach($goals as $goal){
			$rgoal = [];
			$intervalLower = strtolower($goal->interval);
			if ($intervalLower == 'weekly'){
				$goalWeekStart = $this->getStartOfWeek($goal->createdAt);
				$repItems = $this->reportItemMapper->report($this->userId, $goalWeekStart->getTimestamp(),time(),[$goal->projectId],"","","week","project","",false,0,10000);
				$intervals = $this->getWeeksSince($goalWeekStart->getTimestamp());
			} elseif ($intervalLower == 'monthly'){
				$goalMonthStart = $this->getStartOfMonth($goal->createdAt);
				$repItems = $this->reportItemMapper->report($this->userId, $goalMonthStart->getTimestamp(),time(),[$goal->projectId],"","","month","project","",false,0,10000);
				$intervals = $this->getMonthsSince($goalMonthStart->getTimestamp());
			} else {
				$repItems = [];
				$intervals = [];
			}
			$workedSecondsCurrentPeriod = 0;
			$debtSeconds = 0;
			foreach($intervals as $interval){
				$workedInInterval = 0;
				foreach($repItems as $repItem) {
					if ($intervalLower == 'weekly'){
						if ($interval == $this->getStartOfWeek($repItem->time)->format('Y-m-d')) {
							$workedInInterval += $repItem->totalDuration;
						}
					} elseif ($intervalLower == 'monthly'){
						if ($interval == $this->getStartOfMonth($repItem->time)->format('Y-m')) {
							$workedInInterval += $repItem->totalDuration;
						}
					}
				}
				$debtSeconds += ($goal->hours*3600 - $workedInInterval);
			}

			foreach($repItems as $period){
				if ($intervalLower == 'weekly' && $this->getStartOfWeek($period->time)->format('Y-m-d') == $weekStart){
					$workedSecondsCurrentPeriod += $period->totalDuration;
				} elseif ($intervalLower == 'monthly' && $this->getStartOfMonth($period->time)->format('Y-m') == $monthStart){
					$workedSecondsCurrentPeriod += $period->totalDuration;
				}
			}

			$rgoal = [
				'id' => $goal->id,
    			'userUid' => $goal->userUid,
    			'projectId' => $goal->projectId,
    			'projectName' => $goal->projectName,
    			'hours' => $goal->hours,
    			'interval' => $goal->interval,
				'createdAt'  => $goal->createdAt,
				'workedHoursCurrentPeriod'  => round($workedSecondsCurrentPeriod / 3600, 2),
				'debtHours'  => round($debtSeconds / 3600, 2),
				'remainingHours'  => round($goal->hours - $workedSecondsCurrentPeriod / 3600, 2),
				'totalRemainingHours'  => round(($debtSeconds + $goal->hours *3600 - $workedSecondsCurrentPeriod) / 3600, 2),
			];
			$ret[] = $rgoal;
		}
		return new JSONResponse(["Goals" => json_decode(json_encode($ret), true)]);
	}
}
