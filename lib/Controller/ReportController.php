<?php
namespace OCA\TimeTracker\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\TimeTracker\Db\ReportItemMapper;
use OCA\TimeTracker\Db\ProjectMapper;
use OCA\TimeTracker\Db\ClientMapper;

class ReportController extends BaseApiController {

    protected $reportItemMapper;
    protected $projectMapper;
    protected $clientMapper;

    public function __construct(string $AppName, IRequest $request, string $UserId, ReportItemMapper $reportItemMapper, ProjectMapper $projectMapper, ClientMapper $clientMapper) {
        parent::__construct($AppName, $request, $UserId);
        $this->reportItemMapper = $reportItemMapper;
        $this->projectMapper = $projectMapper;
        $this->clientMapper = $clientMapper;
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $name = $this->request->name;
        $from = $this->request->from;
        $timegroup = $this->request->timegroup;
        $to = $this->request->to;
        if (isset($this->request->filterProjectId) && !empty($this->request->filterProjectId)){
            $filterProjectId = explode(",",$this->request->filterProjectId);
        } else {
            $filterProjectId = [];
        }
        if (isset($this->request->filterClientId) && !empty($this->request->filterClientId)){
            $filterClientId = explode(",",$this->request->filterClientId);
        } else {
            $filterClientId = [];
        }

        if ($name == ''){
            $name = $this->userId;
        }


        if(!$this->isThisAdminUser()){
            $allowedClients =  $this->clientMapper->findAll($this->userId);
            $allowedClientsId = array_map(function($client){ return $client->id;}, $allowedClients );
            if(empty($filterClientId)){
                $filterClientId = $allowedClientsId;
                $filterClientId[] = null; // allow null clientid
            } else {
                $filterClientId = array_intersect($filterClientId, $allowedClientsId);
            }
            $allowedProjects =  $this->projectMapper->findAll($this->userId);
            $allowedProjectsId = array_map(function($project){ return $project->id;}, $allowedProjects );
            if(empty($filterProjectId)){
                $filterProjectId = $allowedProjectsId;
                $filterProjectId[] = null; // allow null projectId
            } else {
                $filterProjectId = array_intersect($filterProjectId, $allowedProjectsId);
            }

        }

        $filterTagId = [];
        $groupOn1 = $this->request->group1;
        $groupOn2 = $this->request->group2;
        $items = $this->reportItemMapper->report($name, $from, $to, $filterProjectId, $filterClientId, $filterTagId, $timegroup, $groupOn1, $groupOn2, $this->isThisAdminUser(), 0, 1000);
        return new JSONResponse(["items" => json_decode(json_encode($items), true), 'total' => count($items)]);
    }
}
