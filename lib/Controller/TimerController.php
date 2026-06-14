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
		$projectId = null;
		$name = $this->request->name;
		if (isset($this->request->projectId) && (!empty($this->request->projectId))){
			$projectId = $this->request->projectId;
		}

		$tags = null;
		if (isset($this->request->tags) && (!empty($this->request->tags))){
			$tags = $this->request->tags;
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
		$name = $this->request->name;
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

		if (isset($this->request->name)) {
			if (strlen($this->request->name) > 255){
				return new JSONResponse(["Error" => "Name too long"]);
			}
			$wi->setName($this->request->name);
		}
		if (isset($this->request->details)) {
			if (strlen($this->request->details) > 1024){
				return new JSONResponse(["Error" => "Details too long"]);
			}
			$wi->setDetails($this->request->details);
		}
		if (isset($this->request->projectId)) {
			$wi->setProjectId($this->request->projectId);
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

		 if (isset($this->request->tagId)) {
			 if (is_array($this->request->tagId)){
				$tags = $this->request->tagId;
			 } else {

				 $tags = \explode(",", $this->request->tagId);
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
				//var_dump($newWiToTag);
				$this->workIntervalToTagMapper->insert($newWiToTag);

			}
		 }
		 if (isset($this->request->start)) {
			$tzoffset = 0;
			if (isset($this->request->tzoffset)) {
				$tzoffset = $this->request->tzoffset;
			}

			 date_default_timezone_set('UTC');
			 $dt = \DateTime::createFromFormat ( "d/m/y H:i",$this->request->start);
			 $dt->setTimeZone(new \DateTimeZone('UTC'));
			 $wi->setStart($dt->getTimestamp()+$tzoffset*60);
			 $de = \DateTime::createFromFormat ( "d/m/y H:i",$this->request->end);
			 $de->setTimeZone(new \DateTimeZone('UTC'));
			 $wi->setDuration($de->getTimestamp() - $dt->getTimestamp());
		 }

		if (isset($this->request->cost)) {
			$cost = $this->request->cost;
			if ($cost === '' || $cost === null) {
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

		if (isset($this->request->name)) {
			$wi->setName($this->request->name);
		}
		if (isset($this->request->details)) {
			if (strlen($this->request->details) > 1024){
				return new JSONResponse(["Error" => "Details too long"]);
			}
			$wi->setDetails($this->request->details);
		}
		if (isset($this->request->projectId)) {
			$wi->setProjectId($this->request->projectId);
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
		 if (isset($this->request->tagId)) {
			$tags = \explode(",", $this->request->tagId);
			$this->workIntervalToTagMapper->deleteAllForWorkInterval($id);
			$project = null;
			$locked = 0;


			foreach($tags as $tag){
				if (empty($tag))
					continue;
				$newWiToTag = new WorkIntervalToTag();
				$newWiToTag->setWorkIntervalId($id);
				$newWiToTag->setTagId($tag);
				$newWiToTag->setCreatedAt(time());
				//var_dump($newWiToTag);
				$this->workIntervalToTagMapper->insert($newWiToTag);

			}
		 }
		 if (isset($this->request->start)) {
			$tzoffset = 0;
			if (isset($this->request->tzoffset)) {
				$tzoffset = $this->request->tzoffset;
			}

			 date_default_timezone_set('UTC');
			 $dt = \DateTime::createFromFormat ( "d/m/y H:i",$this->request->start);
			 $dt->setTimeZone(new \DateTimeZone('UTC'));
			 $wi->setStart($dt->getTimestamp()+$tzoffset*60);
			 $de = \DateTime::createFromFormat ( "d/m/y H:i",$this->request->end);
			 $de->setTimeZone(new \DateTimeZone('UTC'));
			 $wi->setDuration($de->getTimestamp() - $dt->getTimestamp());
		 }

		$this->workIntervalMapper->insert($wi);

		$running = $this->workIntervalMapper->findAllRunning($this->userId);

		return new JSONResponse(["WorkIntervals" => json_decode(json_encode($running), true)]);
	}
}
