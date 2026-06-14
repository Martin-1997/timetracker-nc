<?php
declare(strict_types=1);
namespace OCA\TimeTracker\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCA\TimeTracker\Db\ClientMapper;
use OCA\TimeTracker\Db\UserToClientMapper;
use OCA\TimeTracker\Db\Client;
use OCA\TimeTracker\Db\UserToClient;

class ClientController extends BaseApiController {
    protected $clientMapper;
    protected $userToClientMapper;

    public function __construct(string $AppName, IRequest $request, string $UserId, ClientMapper $clientMapper, UserToClientMapper $userToClientMapper) {
        parent::__construct($AppName, $request, $UserId);
        $this->clientMapper = $clientMapper;
        $this->userToClientMapper = $userToClientMapper;
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $clientName = $this->request->term ?? null;

        if ($clientName) {
            $clients = $this->clientMapper->searchByName($this->userId, $clientName);
        } else {
            $clients = $this->clientMapper->findAll($this->userId);
        }

        return new JSONResponse(["Clients" => json_decode(json_encode($clients), true)]);
    }

    /**
     *
     * @NoAdminRequired
     */
    public function create() {
        $name = (string)($this->request->getParam('name', ''));
        if (trim($name) == '') {
            return;
        }

        $c = $this->clientMapper->findByName($name);
        if ($c == null) {
            $c = new Client();
            $c->setName($name);
            $c->setCreatedAt(time());
            $this->clientMapper->insert($c);
        }

        $utoc = $this->userToClientMapper->findForUserAndClient($this->userId, $c);
        if ($utoc == null) {
            $utoc = new UserToClient();
            $utoc->setClientId($c->id);
            $utoc->setUserUid($this->userId);
            $utoc->setCreatedAt(time());
            $utoc->setAdmin(1);
            $this->userToClientMapper->insert($utoc);
        } else {
            return new JSONResponse(["Error" => "This client is already in your list"]);
        }
        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     */
    public function update(int $id) {
        $name = (string)($this->request->getParam('name', ''));
        $c = $this->clientMapper->find($id);
        if ($c == null) {
            return;
        }
        if (trim($name) != '') {
            $old = $this->clientMapper->findByName($name);
            if ($old == null || $old->id == $id) {
                $c->setName($name);
                $this->clientMapper->update($c);
            } else {
                return new JSONResponse(["Error" => "This client already exists"]);
            }
        }
        return $this->index();
    }

    /**
     *
     * @NoAdminRequired
     */
    public function destroy(int $id) {
        $c = new Client();
        $c->setId($id);
        $utoc = $this->userToClientMapper->findForUserAndClient($this->userId, $c);

        if ($utoc != null) {
            $this->userToClientMapper->delete($utoc);
        }
        return $this->index();
    }
}
