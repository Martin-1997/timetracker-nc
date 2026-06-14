<?php

declare(strict_types=1);

namespace OCA\TimeTracker\Tests\Unit\Controller;

use OCA\TimeTracker\Controller\ClientController;
use OCA\TimeTracker\Db\Client;
use OCA\TimeTracker\Db\ClientMapper;
use OCA\TimeTracker\Db\UserToClient;
use OCA\TimeTracker\Db\UserToClientMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientControllerTest extends TestCase
{
    private string $userId = 'testuser';

    /** @var ClientMapper&MockObject */
    private ClientMapper $clientMapper;

    /** @var UserToClientMapper&MockObject */
    private UserToClientMapper $userToClientMapper;

    private ClientController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientMapper       = $this->createMock(ClientMapper::class);
        $this->userToClientMapper = $this->createMock(UserToClientMapper::class);
        $this->controller = $this->buildController([]);
    }

    private function buildController(array $params): ClientController
    {
        return new ClientController(
            'timetracker',
            new FakeRequest($params),
            $this->userId,
            $this->clientMapper,
            $this->userToClientMapper,
        );
    }

    // =========================================================================
    // create()
    // =========================================================================

    /**
     * create() with an empty name must return before touching any mapper.
     * This also serves as a regression test: if the controller reads the name
     * via $this->request->name (property access) instead of getParam(), the
     * FakeRequest stub returns null, trim(null) throws TypeError in PHP 8.4,
     * and this test fails — catching the bug at test time.
     */
    public function testCreateEmptyNameReturnsEarlyWithoutInsert(): void
    {
        $this->clientMapper->expects($this->never())->method('insert');
        $this->userToClientMapper->expects($this->never())->method('insert');

        $response = $this->buildController(['name' => ''])->create();

        $this->assertNull($response);
    }

    /**
     * create() with a whitespace-only name must also return early.
     */
    public function testCreateWhitespaceNameReturnsEarly(): void
    {
        $this->clientMapper->expects($this->never())->method('insert');

        $response = $this->buildController(['name' => '   '])->create();

        $this->assertNull($response);
    }

    /**
     * create() with a new client name inserts the client and the user-link,
     * then returns the full client list.
     */
    public function testCreateNewClientInsertsAndReturnsClientList(): void
    {
        $this->clientMapper->method('findByName')->willReturn(null);
        $this->clientMapper->method('insert')->willReturnCallback(function (Client $c) {
            $c->id = 1;
        });
        $this->clientMapper->method('findAll')->willReturn([]);

        $this->userToClientMapper->method('findForUserAndClient')->willReturn(null);
        $this->userToClientMapper->expects($this->once())->method('insert');

        $response = $this->buildController(['name' => 'Acme Corp'])->create();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Clients', $response->getData());
    }

    /**
     * create() when the client already exists globally but is not yet in the
     * user's list must skip the client insert and add the user-link only.
     */
    public function testCreateExistingGlobalClientAddsUserLink(): void
    {
        $existing = new Client();
        $existing->id = 7;

        $this->clientMapper->method('findByName')->willReturn($existing);
        $this->clientMapper->expects($this->never())->method('insert');
        $this->clientMapper->method('findAll')->willReturn([]);

        $this->userToClientMapper->method('findForUserAndClient')->willReturn(null);
        $this->userToClientMapper->expects($this->once())->method('insert');

        $response = $this->buildController(['name' => 'Existing Corp'])->create();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Clients', $response->getData());
    }

    /**
     * create() when the client is already in the user's list must return an
     * Error response and perform no inserts.
     */
    public function testCreateDuplicateUserClientReturnsError(): void
    {
        $existing = new Client();
        $existing->id = 3;
        $existingLink = new UserToClient();

        $this->clientMapper->method('findByName')->willReturn($existing);
        $this->clientMapper->expects($this->never())->method('insert');
        $this->userToClientMapper->method('findForUserAndClient')->willReturn($existingLink);
        $this->userToClientMapper->expects($this->never())->method('insert');

        $response = $this->buildController(['name' => 'Existing Corp'])->create();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Error', $response->getData());
    }

    // =========================================================================
    // update()
    // =========================================================================

    /**
     * update() with a valid new name renames the client and returns the list.
     */
    public function testUpdateRenamesClientAndReturnsList(): void
    {
        $client = new Client();
        $client->id = 5;
        $client->name = 'Old Name';

        $this->clientMapper->method('find')->with(5)->willReturn($client);
        $this->clientMapper->method('findByName')->willReturn(null);
        $this->clientMapper->expects($this->once())->method('update');
        $this->clientMapper->method('findAll')->willReturn([]);

        $response = $this->buildController(['name' => 'New Name'])->update(5);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame('New Name', $client->name);
    }

    /**
     * update() when the target name belongs to a different client returns Error.
     */
    public function testUpdateNameCollisionReturnsError(): void
    {
        $client = new Client();
        $client->id = 5;

        $collision = new Client();
        $collision->id = 9;

        $this->clientMapper->method('find')->willReturn($client);
        $this->clientMapper->method('findByName')->willReturn($collision);
        $this->clientMapper->expects($this->never())->method('update');

        $response = $this->buildController(['name' => 'Taken Name'])->update(5);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Error', $response->getData());
    }
}
