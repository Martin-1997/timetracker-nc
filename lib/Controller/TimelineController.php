<?php
namespace OCA\TimeTracker\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCA\TimeTracker\Db\ReportItemMapper;
use OCA\TimeTracker\Db\ProjectMapper;
use OCA\TimeTracker\Db\ClientMapper;
use OCA\TimeTracker\Db\TimelineMapper;
use OCA\TimeTracker\Db\TimelineEntryMapper;
use OCA\TimeTracker\Db\Timeline;
use OCA\TimeTracker\Db\TimelineEntry;

class TimelineController extends BaseApiController {

    protected $l10n;
    protected $timelineMapper;
    protected $timelineEntryMapper;
    protected $reportItemMapper;
    protected $projectMapper;
    protected $clientMapper;

    public function __construct(string $AppName, IRequest $request, string $UserId, IL10N $l10n, TimelineMapper $timelineMapper, TimelineEntryMapper $timelineEntryMapper, ReportItemMapper $reportItemMapper, ProjectMapper $projectMapper, ClientMapper $clientMapper) {
        parent::__construct($AppName, $request, $UserId);
        $this->l10n = $l10n;
        $this->timelineMapper = $timelineMapper;
        $this->timelineEntryMapper = $timelineEntryMapper;
        $this->reportItemMapper = $reportItemMapper;
        $this->projectMapper = $projectMapper;
        $this->clientMapper = $clientMapper;
    }

    private function secondsToTime($seconds): string {
        $s = (int)$seconds;
        $hours = intdiv($s, 3600);
        $mins = intdiv($s % 3600, 60);
        $secs = $s % 60;
        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(){
        $timelines = $this->timelineMapper->findAll($this->userId);
        $parray = json_decode(json_encode($timelines), true);

        return new JSONResponse(["Timelines" => $parray, 'total' => count($parray)]);
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function adminIndex(){
        $timelines = $this->timelineMapper->findLatest();
        $parray = json_decode(json_encode($timelines), true);

        return new JSONResponse(["Timelines" => $parray, 'total' => count($parray)]);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function create(){
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

        $timeline = new Timeline();
        $timeline->setUserUid($this->userId);
        $timeline->setGroup1($this->request->group1);
        $timeline->setGroup2($this->request->group2);
        $timeline->setTimeGroup($this->request->timegroup);
        $timeline->setFilterProjects(implode(', ',$filterProjectId));
        $timeline->setFilterClients(implode(', ',$filterClientId));
        $timeline->setTimeInterval($this->l10n->l('date', $from) . ' - '. $this->l10n->l('date', $to));
        $totalDuration = 0;
        foreach($items as $i){
            $totalDuration += $i->totalDuration;
        }

        $timeline->setTotalDuration($totalDuration);
        $timeline->setCreatedAt(time());
        $timeline->setStatus('pending');
        $this->timelineMapper->insert($timeline);
        foreach($items as $i){
            $te = new TimelineEntry();
            $te->setTimelineId($timeline->id);
            $te->setUserUid($timeline->userUid);
            $te->setName($i->name);
            $te->setProjectName($i->project ? $i->project : "");
            $te->setClientName($i->client ? $i->client : "");
            $te->setTimeInterval($i->time);
            $te->setTotalDuration($i->totalDuration);
            $te->setCreatedAt(time());
            $te->setCost($i->cost);
            $this->timelineEntryMapper->insert($te);

        }
        return new JSONResponse(["Timeline" => json_decode(json_encode($timeline), true)]);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function update(int $id){
        $timeline = $this->timelineMapper->find($id);
        $timeline->setStatus($this->request->status);
        $this->timelineMapper->update($timeline);
        return new JSONResponse(["Timeline" => $timeline]);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function destroy(int $id) {
        $tl = $this->timelineMapper->find($id);
        if ($tl->userUid == $this->userId){
            $this->timelineMapper->delete($tl);
        }
        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function download(int $id){
        $te = $this->timelineEntryMapper->findTimelineEntries($id);
        if (count($te) == 0){ // nothing to send
            exit(0);
        }

        $user = $te[0]->userUid;

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=timeline-'.$user.'-'.$id.'.csv');

        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // output the column headings
        fputcsv($output, array('id', 'User Uid', 'Name', 'Project Name', 'Client Name', 'Time Interval', 'Total Duration'));
        $totalDuration = 0;
        foreach($te as $t){

                fputcsv($output, [$t->id, $t->userUid, $t->name, $t->projectName, $t->clientName, $t->timeInterval, $this->secondsToTime($t->totalDuration)]);
                $totalDuration += $t->totalDuration;
        }
        fputcsv($output, ['TOTAL', '', '', '', '', '', $this->secondsToTime($totalDuration)]);
        fclose($output);
        exit(0);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function email(int $id) {

        $te = $this->timelineEntryMapper->findTimelineEntries($id);
        if (count($te) == 0){ // nothing to send
            exit(0);
        }
        $user = $te[0]->userUid;

        $email = $this->request->email;
        $emails = explode(';',$email);
        $subject = $this->request->subject;
        $content = $this->request->content;

        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=timeline-'.$user.'-'.$id.'.csv');

        // create a file pointer connected to the output stream
        $id = (int)$id;
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR. 'timeline-'.$user.'-'.$id.'.csv';
        $output = fopen($path, "w");
        //$path = stream_get_meta_data($output)['uri'];
        // output the column headings
        fputcsv($output, array('id', 'User Uid', 'Name', 'Project Name', 'Client Name', 'Time Interval', 'Total Duration'));
        $totalDuration = 0;
        foreach($te as $t){

                fputcsv($output, [$t->id, $t->userUid, $t->name, $t->projectName, $t->clientName, $t->timeInterval, $this->secondsToTime($t->totalDuration)]);
                $totalDuration += $t->totalDuration;
        }
        fputcsv($output, ['TOTAL', '', '', '', '', '', $this->secondsToTime($totalDuration)]);



        $mailer = \OC::$server->getMailer();
        $message = $mailer->createMessage();
        $attach = $mailer->createAttachmentFromPath($path);
        $message->setSubject($subject);
        //$message->setTo([$email => 'Recipient']);
        $message->setTo($emails);
        $message->setPlainBody($content);
        //$message->setHtmlBody($content);
        $message->attach($attach);
        $mailer->send($message);

        fclose($output);
        unlink($path);
        return new JSONResponse([]);
    }
}
