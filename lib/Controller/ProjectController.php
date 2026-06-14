<?php
declare(strict_types=1);
namespace OCA\TimeTracker\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\TimeTracker\Db\ProjectMapper;
use OCA\TimeTracker\Db\UserToProjectMapper;
use OCA\TimeTracker\Db\TagMapper;
use OCA\TimeTracker\Db\ClientMapper;
use OCA\TimeTracker\Db\WorkIntervalMapper;
use OCA\TimeTracker\Db\WorkIntervalToTagMapper;
use OCA\TimeTracker\Db\Project;
use OCA\TimeTracker\Db\UserToProject;

class ProjectController extends BaseApiController {
    protected $projectMapper;
    protected $userToProjectMapper;
    protected $tagMapper;
    protected $clientMapper;
    protected $workIntervalMapper;
    protected $workIntervalToTagMapper;

    public function __construct(string $AppName, IRequest $request, string $UserId, ProjectMapper $projectMapper, UserToProjectMapper $userToProjectMapper, TagMapper $tagMapper, ClientMapper $clientMapper, WorkIntervalMapper $workIntervalMapper, WorkIntervalToTagMapper $workIntervalToTagMapper) {
        parent::__construct($AppName, $request, $UserId);
        $this->projectMapper = $projectMapper;
        $this->userToProjectMapper = $userToProjectMapper;
        $this->tagMapper = $tagMapper;
        $this->clientMapper = $clientMapper;
        $this->workIntervalMapper = $workIntervalMapper;
        $this->workIntervalToTagMapper = $workIntervalToTagMapper;
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        if ($this->request->view === 'table') {
            $getArchived = 0;
            if (isset($this->request->archived)) {
                $getArchived = $this->request->archived;
            }

            if ($this->isThisAdminUser()) {
                $projects = $this->projectMapper->findAllAdmin($getArchived);
            } else {
                $projects = $this->projectMapper->findAll($this->userId, $getArchived);
            }
            $outProjects = [];
            foreach ($projects as $p) {
                $out = [];
                $out['id'] = $p->id;
                $out['name'] = $p->name;
                $out['locked'] = $p->locked;
                $out['archived'] = $p->archived;
                $out['color'] = $p->color;
                $out['client'] = null;
                $tags = $this->tagMapper->findAllAlowedForProject($p->id);
                $users = array_map(function ($utop) { return $utop->userUid; }, $this->userToProjectMapper->findAllForProject($p->id));
                $out['allowedTags'] = json_decode(json_encode($tags));
                $out['allowedUsers'] = json_decode(json_encode($users));
                if ($p->clientId != null) {
                    $client = $this->clientMapper->find($p->clientId);
                    if ($client != null) {
                        $out['client'] = $client->name;
                        $out['clientId'] = $client->id;
                    }
                }

                $outProjects[] = $out;
            }

            return new JSONResponse(["items" => json_decode(json_encode($outProjects), true), 'total' => count($outProjects)]);
        }

        $projectName = $this->request->term ?? null;

        if ($projectName) {
            $projects = $this->projectMapper->searchByName($this->userId, $projectName);
        } else {
            $projects = $this->projectMapper->findAll($this->userId);
        }
        $parray = json_decode(json_encode($projects), true);
        foreach ($parray as $pi => $pv) {
            if (isset($pv->id)) {
                $tags = $this->tagMapper->findAllAlowedForProject($pv->id);
                $parray[$pi]['allowedtags'] = $tags;
            }
        }
        return new JSONResponse(["Projects" => $parray]);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function create() {
        $name = $this->request->name;
        $clientId = null;
        if (isset($this->request->clientId)) {
            $clientId = $this->request->clientId;
        }
        $color = '#ffffff';
        if (isset($this->request->color) && !empty($this->request->color)) {
            $color = $this->request->color;
        }
        if (trim($name) == '') {
            return;
        }
        $p = $this->projectMapper->findByName($name);
        if ($p == null) {
            $p = new Project();
            $p->setName($name);
            $p->setColor($color);
            $p->setCreatedAt(time());
            $p->setCreatedByUserUid($this->userId);
            $p->setClientId($clientId);
            $this->projectMapper->insert($p);
        } else {
            if ($p->locked && !$this->isThisAdminUser()) {
                return new JSONResponse(["Error" => "This project is locked"]);
            }
        }

        $utop = $this->userToProjectMapper->findForUserAndProject($this->userId, $p);
        if ($utop == null) {
            $utop = new UserToProject();
            $utop->setProjectId($p->id);
            $utop->setUserUid($this->userId);
            $utop->setCreatedAt(time());
            $utop->setAdmin(1);
            $this->userToProjectMapper->insert($utop);
        }
        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     */
    public function update(int $id) {
        $p = $this->projectMapper->find($id);
        if ($p == null) {
            return;
        }
        if (isset($this->request->name)) {
            $name = $this->request->name;
            if (trim($name) != '') {
                $old = $this->projectMapper->findByName($name);
                if ($old != null && $old->id != $id) {
                    return new JSONResponse(["Error" => "A project with this name already exists"]);
                }
                $p->setName($name);
            }
        }
        if (isset($this->request->color)) {
            $color = $this->request->color;
            $p->setColor($color);
        }
        if (isset($this->request->clientId)) {
            $clientId = $this->request->clientId;
            $p->setClientId($clientId);
        }
        if (isset($this->request->locked) && $this->isThisAdminUser()) {
            $locked = $this->request->locked;
            $p->setLocked($locked);
        }

        if (isset($this->request->allowedTags) && $this->isThisAdminUser()) {
            $allowedTags = $this->request->allowedTags;
            $a = explode(',', $allowedTags);
            $this->tagMapper->allowedTags($id, $a);
        }
        if (isset($this->request->allowedUsers) && $this->isThisAdminUser()) {
            $allowedUsers = $this->request->allowedUsers;
            $a = explode(',', $allowedUsers);
            $this->userToProjectMapper->deleteAllForProject($id);
            foreach ($a as $u) {
                if (empty($u))
                    continue;
                $up = new UserToProject();
                $up->setUserUid($u);
                $up->setProjectId($id);
                $up->setAccess(1);
                $up->setCreatedAt(time());
                $this->userToProjectMapper->insert($up);
            }
        }

        if (isset($this->request->archived) && $p->getArchived() != $this->request->archived) {
            if (($this->isThisAdminUser() || $p->createdByUserUid == $this->userId)) {
                $archived = $this->request->archived;
                $p->setArchived($archived);
            } else {
                return new JSONResponse(["Error" => "You cannot archive/unarchive projects created by somebody else"]);
            }
        }

        $this->projectMapper->update($p);

        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     */
    public function destroy(int $id) {
        if (!$this->isThisAdminUser()) {
            return;
        }
        $this->userToProjectMapper->deleteAllForProject($id);
        $wi = $this->workIntervalMapper->findAllForProject($id);
        if ($wi != null) {
            foreach ($wi as $w) {
                $this->workIntervalToTagMapper->deleteAllForWorkInterval($w->id);
            }
        }
        $this->workIntervalMapper->deleteAllForProject($id);
        $this->tagMapper->allowedTags($id, []);
        $this->projectMapper->delete($id);

        return $this->index();
    }
}
