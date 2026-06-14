<?php
declare(strict_types=1);
namespace OCA\TimeTracker\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\TimeTracker\Db\TagMapper;
use OCA\TimeTracker\Db\WorkIntervalMapper;
use OCA\TimeTracker\Db\WorkIntervalToTagMapper;
use OCA\TimeTracker\Db\Tag;

class TagController extends BaseApiController {
    protected $tagMapper;
    protected $workIntervalMapper;
    protected $workIntervalToTagMapper;

    public function __construct(string $AppName, IRequest $request, string $UserId, TagMapper $tagMapper, WorkIntervalMapper $workIntervalMapper, WorkIntervalToTagMapper $workIntervalToTagMapper) {
        parent::__construct($AppName, $request, $UserId);
        $this->tagMapper = $tagMapper;
        $this->workIntervalMapper = $workIntervalMapper;
        $this->workIntervalToTagMapper = $workIntervalToTagMapper;
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $q = $this->request->q;
        $tags = $this->tagMapper->findAll($this->userId);
        if ($q != null) {
            $filteredTags = [];
            foreach ($tags as $t) {
                if (stripos($t->name, $q) !== FALSE) {
                    $filteredTags[] = $t;
                }
            }
            $tags = $filteredTags;
        }
        return new JSONResponse(["Tags" => json_decode(json_encode($tags), true)]);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function create() {
        $name = $this->request->name;

        $c = $this->tagMapper->findByNameUser($name, $this->userId);
        if ($c == null && (trim($name) != '')) {
            $c = new Tag();
            $c->setName($name);
            $c->setUserUid($this->userId);
            $c->setCreatedAt(time());
            $this->tagMapper->insert($c);
        } else if ($c != null) {
            return new JSONResponse(["Error" => "This tag name already exists"]);
        }

        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     */
    public function update(int $id) {
        $name = $this->request->name;
        if (trim($name) == '') {
            return;
        }
        $c = $this->tagMapper->find($id);
        if ($c == null) {
            return;
        }
        $old = $this->tagMapper->findByNameUser($name, $this->userId);
        if ($old != null && $old->id != $id) {
            return new JSONResponse(["Error" => "This tag name already exists"]);
        }
        $c->setName($name);
        $this->tagMapper->update($c);

        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     */
    public function destroy(int $id) {
        $c = $this->tagMapper->find($id);
        if ($c == null) {
            return;
        }
        $this->tagMapper->delete($c);
        $this->workIntervalToTagMapper->deleteAllForTag($id);
        return $this->index();
    }
}
