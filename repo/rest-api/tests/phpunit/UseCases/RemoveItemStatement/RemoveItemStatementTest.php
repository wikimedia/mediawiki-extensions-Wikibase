<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\RemoveItemStatement;

use CommentStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementIdValidator;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\RemoveItemStatement\RemoveItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 *
 */
class RemoveItemStatementTest extends TestCase {

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $revisionMetadataRetriever;

	/**
	 * @var MockObject|ItemRetriever
	 */
	private $itemRetriever;

	/**
	 * @var MockObject|ItemUpdater
	 */
	private $itemUpdater;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testRemoveStatement_success(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$statement = NewStatement::forProperty( 'P123' )->withGuid( $statementId )->withValue( 'statement value' )->build();
		$item = NewItem::withId( $itemId )->andStatement( $statement )->build();

		$requestData = [
			'$statementId' => $statementId,
			'$editTags' => [ 'some', 'tags' ],
			'$isBot' => false,
			'$comment' => 'statement removed by ' . __method__,
			'$username' => null,
			'$itemId' => $itemId
		];
		$request = $this->newUseCaseRequest( $requestData );

		$newItemRevision = new ItemRevision(
			NewItem::withId( $itemId )->build(),
			'20220809030405',
			322
		);

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->expects( $this->once() )->method( 'getItem' )->willReturn( $item );

		$editMetadata = new EditMetadata(
			$requestData['$editTags'], $requestData['$isBot'], $requestData['$comment']
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $item, $this->equalTo( $editMetadata ) )
			->willReturn( $newItemRevision );

		$response = $this->newUseCase()->execute( $request );
		$this->assertInstanceOf( RemoveItemStatementSuccessResponse::class, $response );
	}

	public function testRemoveStatement_invalidRequest(): void {
		$requestData = [
			'$statementId' => 'INVALID-STATEMENT-ID',
			'$editTags' => [],
			'$isBot' => false,
			'$comment' => null,
			'$username' => null,
			'$itemId' => null
		];

		$response = $this->newUseCase()->execute(
			$this->newUseCaseRequest( $requestData )
		);

		$this->assertInstanceOf( RemoveItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::INVALID_STATEMENT_ID, $response->getCode() );
	}

	private function newUseCase(): RemoveItemStatement {
		$itemIdParser = new ItemIdParser();
		return new RemoveItemStatement(
			new RemoveItemStatementValidator(
				new ItemIdValidator(),
				new StatementIdValidator( $itemIdParser ),
				new EditMetadataValidator( CommentStore::COMMENT_CHARACTER_LIMIT, self::ALLOWED_TAGS )
			),
			$this->revisionMetadataRetriever,
			new StatementGuidParser( $itemIdParser ),
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

	private function newUseCaseRequest( array $requestData ): RemoveItemStatementRequest {
		return new RemoveItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}
}
