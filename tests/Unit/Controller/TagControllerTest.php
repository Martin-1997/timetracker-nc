<?php

declare(strict_types=1);

namespace OCA\TimeTracker\Tests\Unit\Controller;

use OCA\TimeTracker\Controller\TagController;
use OCA\TimeTracker\Db\Tag;
use OCA\TimeTracker\Db\TagMapper;
use OCA\TimeTracker\Db\WorkIntervalMapper;
use OCA\TimeTracker\Db\WorkIntervalToTagMapper;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TagControllerTest extends TestCase
{
    private string $userId = 'testuser';

    /** @var TagMapper&MockObject */
    private TagMapper $tagMapper;

    /** @var WorkIntervalMapper&MockObject */
    private WorkIntervalMapper $workIntervalMapper;

    /** @var WorkIntervalToTagMapper&MockObject */
    private WorkIntervalToTagMapper $workIntervalToTagMapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tagMapper              = $this->createMock(TagMapper::class);
        $this->workIntervalMapper     = $this->createMock(WorkIntervalMapper::class);
        $this->workIntervalToTagMapper = $this->createMock(WorkIntervalToTagMapper::class);
    }

    private function buildController(array $params): TagController
    {
        return new TagController(
            'timetracker',
            new FakeRequest($params),
            $this->userId,
            $this->tagMapper,
            $this->workIntervalMapper,
            $this->workIntervalToTagMapper,
        );
    }

    // =========================================================================
    // create()
    // =========================================================================

    /**
     * create() with an empty name must return the tag list without inserting.
     * Regression guard: if the controller uses $this->request->name instead of
     * getParam(), FakeRequest returns null, trim(null) throws TypeError in
     * PHP 8.4, and this test fails — exposing the regression immediately.
     */
    public function testCreateEmptyNameReturnsListWithoutInsert(): void
    {
        $this->tagMapper->expects($this->never())->method('insert');
        $this->tagMapper->method('findAll')->willReturn([]);

        $response = $this->buildController(['name' => ''])->create();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Tags', $response->getData());
    }

    /**
     * create() with a new name inserts a Tag and returns the updated tag list.
     */
    public function testCreateNewTagInsertsAndReturnsList(): void
    {
        $this->tagMapper->method('findByNameUser')->willReturn(null);
        $this->tagMapper->expects($this->once())->method('insert')
            ->willReturnCallback(function (Tag $t) { $t->id = 1; });
        $this->tagMapper->method('findAll')->willReturn([]);

        $response = $this->buildController(['name' => 'backend'])->create();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Tags', $response->getData());
    }

    /**
     * create() with a name that already exists for this user returns an Error.
     */
    public function testCreateDuplicateTagReturnsError(): void
    {
        $existing = new Tag();
        $existing->id = 3;

        $this->tagMapper->method('findByNameUser')->willReturn($existing);
        $this->tagMapper->expects($this->never())->method('insert');

        $response = $this->buildController(['name' => 'backend'])->create();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Error', $response->getData());
    }

    // =========================================================================
    // update()
    // =========================================================================

    /**
     * update() with an empty name returns early (null).
     */
    public function testUpdateEmptyNameReturnsEarly(): void
    {
        $this->tagMapper->expects($this->never())->method('update');

        $response = $this->buildController(['name' => ''])->update(1);

        $this->assertNull($response);
    }

    /**
     * update() renames the tag when no name collision exists.
     */
    public function testUpdateRenamesTagAndReturnsList(): void
    {
        $tag = new Tag();
        $tag->id = 4;
        $tag->name = 'old';

        $this->tagMapper->method('find')->with(4)->willReturn($tag);
        $this->tagMapper->method('findByNameUser')->willReturn(null);
        $this->tagMapper->expects($this->once())->method('update');
        $this->tagMapper->method('findAll')->willReturn([]);

        $response = $this->buildController(['name' => 'new'])->update(4);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame('new', $tag->name);
    }

    /**
     * update() returns Error when the new name belongs to a different tag.
     */
    public function testUpdateNameCollisionReturnsError(): void
    {
        $tag = new Tag();
        $tag->id = 4;

        $collision = new Tag();
        $collision->id = 9;

        $this->tagMapper->method('find')->willReturn($tag);
        $this->tagMapper->method('findByNameUser')->willReturn($collision);
        $this->tagMapper->expects($this->never())->method('update');

        $response = $this->buildController(['name' => 'taken'])->update(4);

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertArrayHasKey('Error', $response->getData());
    }

    // =========================================================================
    // destroy()
    // =========================================================================

    /**
     * destroy() deletes the tag and its work-interval links.
     */
    public function testDestroyDeletesTagAndLinks(): void
    {
        $tag = new Tag();
        $tag->id = 6;

        $this->tagMapper->method('find')->with(6)->willReturn($tag);
        $this->tagMapper->expects($this->once())->method('delete')->with($tag);
        $this->workIntervalToTagMapper->expects($this->once())->method('deleteAllForTag')->with(6);
        $this->tagMapper->method('findAll')->willReturn([]);

        $response = $this->buildController([])->destroy(6);

        $this->assertInstanceOf(JSONResponse::class, $response);
    }
}
