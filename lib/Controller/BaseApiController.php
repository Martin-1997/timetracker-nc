<?php
declare(strict_types=1);
namespace OCA\TimeTracker\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;

abstract class BaseApiController extends Controller {
    protected string $userId;

    public function __construct(string $AppName, IRequest $request, string $UserId) {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
    }

    protected function isThisAdminUser(): bool {
        return \OC_User::isAdminUser(\OC_User::getUser());
    }
}
