<?php
namespace OCA\TimeTracker\Controller;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCA\TimeTracker\Db\WorkIntervalMapper;
use OCA\TimeTracker\Db\WorkInterval;
use OCA\TimeTracker\Db\ProjectMapper;
use OCA\TimeTracker\Db\TagMapper;
use OCA\TimeTracker\Db\Tag;
use OCA\TimeTracker\Db\WorkIntervalToTag;
use OCA\TimeTracker\Db\WorkIntervalToTagMapper;

class TimerController extends BaseApiController {
	protected $workIntervalMapper;
	protected $projectMapper;
	protected $tagMapper;
	protected $workIntervalToTagMapper;
	protected $l10n;

	public function __construct(string $AppName, IRequest $request, string $UserId, IL10N $l10n,
							WorkIntervalMapper $workIntervalMapper, ProjectMapper $projectMapper,
							TagMapper $tagMapper, WorkIntervalToTagMapper $workIntervalToTagMapper){
		parent::__construct($AppName, $request, $UserId);
		$this->l10n = $l10n;
		$this->workIntervalMapper = $workIntervalMapper;
		$this->projectMapper = $projectMapper;
		$this->tagMapper = $tagMapper;
		$this->workIntervalToTagMapper = $workIntervalToTagMapper;
	}

	/**
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$from = $this->request->from;
		$to = $this->request->to;
		$l = $this->workIntervalMapper->findLatestInterval($this->userId, $from, $to);
		$days = [];
		$tzoffset = 0;
		if (isset($this->request->tzoffset)) {
			$tzoffset = -($this->request->tzoffset * 60);
		}
		date_default_timezone_set('UTC');
		foreach ($l as $wi){
			$dt = $this->l10n->l('date', $wi->start+$tzoffset, ['width' => 'medium']);
			if (!isset($days[$dt])){
				$days[$dt] = [];
			}
			if (!isset($days[$dt][$wi->name])){
				$days[$dt][$wi->name] = ['children' => [], 'totalTime' => 0];
			}
			$project = null;
			if ($wi->projectId != null){
				$project = $this->projectMapper->find($wi->projectId);
			}

			$tags = [];
			$wiToTags = $this->workIntervalToTagMapper->findAllForWorkInterval($wi->id);
			foreach($wiToTags as $wiToTag){
				$t = $this->tagMapper->find($wiToTag->tagId);
				if ($t != null)
					$tags[] = $t;
			}

			$wa = ['duration' => $wi->duration,
					'id' => $wi->id,
					'name' =>  $wi->name,
					'details' =>  $wi->details,
					'projectId' =>  $wi->projectId,
					'running' =>  $wi->running,
					'start' => $wi->start,
					'tags' => $tags,
					'userUid' => $wi->userUid,
                    'cost' => $wi->cost,
					'projectName' => ($project === null)?null:$project->name,
					'projectColor' =>  ($project === null)?null:$project->color,
			];
			$days[$dt][$wi->name]['children'][] = $wa;
			$days[$dt][$wi->name]['totalTime'] += $wa['duration'];
		}


		$running = $this->workIntervalMapper->findAllRunning($this->userId);
		return new JSONResponse(["WorkIntervals" => $l, "running" => $running, 'days' => $days, 'now' => time()]);
	}

	/**
	 *
	 * @NoAdminRequired
	 */

	public function start() {
		//$this->endTimer();
		$name = (string)($this->request->getParam('name', ''));
		$projectId = null;
		$projectIdParam = $this->request->getParam('projectId');
		if (!empty($projectIdParam)) {
			$projectId = $projectIdParam;
		}

		$tags = null;
		$tagsParam = $this->request->getParam('tags');
		if (!empty($tagsParam)) {
			$tags = $tagsParam;
		}

		if (strlen($name) > 255){
			return new JSONResponse(["Error" => "Name too long"]);
		}
		$winterval = new WorkInterval();
		$winterval->setStart(time());
		$winterval->setRunning(1);
		$winterval->setName($name);
		$winterval->setUserUid($this->userId);

		// first get tags and project ids from the last work item with the same name
		$lwinterval = $this->workIntervalMapper->findLatestByName($this->userId, $name);
		if ($projectId == null && $lwinterval != null){

			$winterval->setProjectId($lwinterval->projectId);
		}

		if($projectId != null){
			$winterval->setProjectId($projectId);
		}

		$this->workIntervalMapper->insert($winterval);
		if ($tags == null && $lwinterval != null){
			$lastTags = $this->workIntervalToTagMapper->findAllForWorkInterval($lwinterval->id);
			foreach($lastTags as $t){
				$wtot = new WorkIntervalToTag();
				$wtot->setWorkIntervalId($winterval->id);
				$wtot->setTagId($t->tagId);
				$wtot->setCreatedAt(time());
				$this->workIntervalToTagMapper->insert($wtot);
			}

		}

		if ($tags != null){
			$tagsArray  = explode(",", $tags);
			foreach($tagsArray as $t){
				$wtot = new WorkIntervalToTag();
				$wtot->setWorkIntervalId($winterval->id);
				$wtot->setTagId($t);
				$wtot->setCreatedAt(time());
				$this->workIntervalToTagMapper->insert($wtot);
			}

		}




		//echo json_encode((array)$winterval);
		return new JSONResponse(["WorkIntervals" => $winterval, "running" => 1]);

	}


	/**
	 *
	 * @NoAdminRequired
	 */

	public function stop() {
		$name = (string)($this->request->getParam('name', ''));
		if (strlen($name) > 255){
			return new JSONResponse(["Error" => "Name too long"]);
		}

		$running = $this->workIntervalMapper->findAllRunning($this->userId);

		$now = time();
		foreach($running as $r){
			$r->setRunning(0);
			$r->setDuration($now - $r->start);
			if ($name != 'no description')
				$r->setName($name);
			$this->workIntervalMapper->update($r);
		}
		return new JSONResponse(["WorkIntervals" => json_decode(json_encode($running), true)]);
	}


	/**
	 *
	 * @NoAdminRequired
	 */

	public function destroy(int $id) {
		$wi = $this->workIntervalMapper->find($id);
		$this->workIntervalMapper->delete($wi);

		$running = $this->workIntervalMapper->findAllRunning($this->userId);

		return new JSONResponse(["WorkIntervals" => json_decode(json_encode($running), true)]);
	}

	/**
	 *
	 * @NoAdminRequired
	 */

	public function update(int $id) {

		$wi = $this->workIntervalMapper->find($id);

		$name = $this->request->getParam('name');
		if ($name !== null) {
			if (strlen((string)$name) > 255){
				return new JSONResponse(["Error" => "Name too long"]);
			}
			$wi->setName($name);
		}
		$details = $this->request->getParam('details');
		if ($details !== null) {
			if (strlen((string)$details) > 1024){
				return new JSONResponse(["Error" => "Details too long"]);
			}
			$wi->setDetails($details);
		}
		$projectId = $this->request->getParam('projectId');
		if ($projectId !== null) {
			$wi->setProjectId($projectId);
			if ($wi->projectId != null){
				$project = $this->projectMapper->find($wi->projectId);
				$locked = $project->locked;
				if($locked){
					$allowedTags = $this->tagMapper->findAllAlowedForProject($project->id);
					$allowedTagsIds = array_map(function($tag) { return $tag->id;}, $allowedTags);
					$currentTags = $this->workIntervalToTagMapper->findAllForWorkInterval($id);
					$currentTagsIds = array_map(function($witag) { return $witag->tagId;}, $currentTags);
					$newTags = array_intersect($allowedTagsIds,$currentTagsIds);

					$this->workIntervalToTagMapper->deleteAllForWorkInterval($id);
					foreach($newTags as $tag){
						if (empty($tag))
							continue;
						$newWiToTag = new WorkIntervalToTag();
						$newWiToTag->setWorkIntervalId($id);
						$newWiToTag->setTagId($tag);
						$newWiToTag->setCreatedAt(time());
						$this->workIntervalToTagMapper->insert($newWiToTag);

					}

				}

			}
		}

		$tagIdParam = $this->request->getParam('tagId');
		if ($tagIdParam !== null) {
			if (is_array($tagIdParam)){
				$tags = $tagIdParam;
			} else {
				$tags = \explode(",", $tagIdParam);
			}
			$this->workIntervalToTagMapper->deleteAllForWorkInterval($id);
			$project = null;
			$locked = 0;


			foreach($tags as $tag){
				if (empty($tag))
					continue;
				if(!is_numeric($tag)){
					if ($wi->projectId != null){
						$project = $this->projectMapper->find($wi->projectId);
						if($project && $project->locked)
							continue; // don't add new tags to locked projects
					}
					$c = new Tag();
					$c->setName($tag);
					$c->setUserUid($this->userId);
					$c->setCreatedAt(time());
					$this->tagMapper->insert($c);
					$tag = $c->id;
				}
				$newWiToTag = new WorkIntervalToTag();
				$newWiToTag->setWorkIntervalId($id);
				$newWiToTag->setTagId($tag);
				$newWiToTag->setCreatedAt(time());
				$this->workIntervalToTagMapper->insert($newWiToTag);

			}
		}
		$startParam = $this->request->getParam('start');
		if ($startParam !== null) {
			$tzoffset = (int)($this->request->getParam('tzoffset', 0));

			date_default_timezone_set('UTC');
			$dt = \DateTime::createFromFormat("d/m/y H:i", $startParam);
			$dt->setTimeZone(new \DateTimeZone('UTC'));
			$wi->setStart($dt->getTimestamp()+$tzoffset*60);
			$endParam = $this->request->getParam('end');
			$de = \DateTime::createFromFormat("d/m/y H:i", $endParam);
			$de->setTimeZone(new \DateTimeZone('UTC'));
			$wi->setDuration($de->getTimestamp() - $dt->getTimestamp());
		}

		$cost = $this->request->getParam('cost');
		if ($cost !== null) {
			if ($cost === '') {
				$wi->setCost(null);
			} else {
				$cost = str_replace(',', '.', $cost);
				if (is_numeric($cost)) {
					$wi->setCost((int)round($cost * 100));
				}
			}
		}

		$this->workIntervalMapper->update($wi);
		$running = $this->workIntervalMapper->findAllRunning($this->userId);

		return new JSONResponse(["WorkIntervals" => json_decode(json_encode($running), true)]);
	}


	/**
	 *
	 * @NoAdminRequired
	 */

	public function create() {

		$wi = new WorkInterval();
		$wi->setUserUid($this->userId);
		$wi->setRunning(0);

		$name = $this->request->getParam('name');
		if ($name !== null) {
			$wi->setName($name);
		}
		$details = $this->request->getParam('details');
		if ($details !== null) {
			if (strlen((string)$details) > 1024){
				return new JSONResponse(["Error" => "Details too long"]);
			}
			$wi->setDetails($details);
		}
		$projectId = $this->request->getParam('projectId');
		if ($projectId !== null) {
			$wi->setProjectId($projectId);
		}
		$startParam = $this->request->getParam('start');
		if ($startParam !== null) {
			$tzoffset = (int)($this->request->getParam('tzoffset', 0));

			date_default_timezone_set('UTC');
			$dt = \DateTime::createFromFormat("d/m/y H:i", $startParam);
			$dt->setTimeZone(new \DateTimeZone('UTC'));
			$wi->setStart($dt->getTimestamp()+$tzoffset*60);
			$endParam = $this->request->getParam('end');
			$de = \DateTime::createFromFormat("d/m/y H:i", $endParam);
			$de->setTimeZone(new \DateTimeZone('UTC'));
			$wi->setDuration($de->getTimestamp() - $dt->getTimestamp());
		}

		$this->workIntervalMapper->insert($wi);

		$tagIdParam = $this->request->getParam('tagId');
		if ($tagIdParam !== null) {
			$tags = \explode(",", $tagIdParam);
			foreach($tags as $tag){
				if (empty($tag))
					continue;
				$newWiToTag = new WorkIntervalToTag();
				$newWiToTag->setWorkIntervalId($wi->id);
				$newWiToTag->setTagId($tag);
				$newWiToTag->setCreatedAt(time());
				$this->workIntervalToTagMapper->insert($newWiToTag);
			}
		}

		$running = $this->workIntervalMapper->findAllRunning($this->userId);

		return new JSONResponse(["WorkIntervals" => json_decode(json_encode($running), true)]);
	}
}
