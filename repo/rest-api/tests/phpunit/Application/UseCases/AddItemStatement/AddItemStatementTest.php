<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddItemStatement;

use CommentStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementResponse;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ReadModelItem;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\Tests\RestApi\Application\Serialization\DeserializerFactory;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemStatementTest extends TestCase {

	use EditMetadataHelper;

	/**
	 * @var MockObject|GetLatestItemRevisionMetadata
	 */
	private $getRevisionMetadata;
	/**
	 * @var MockObject|ItemRetriever
	 */
	private $itemRetriever;
	/**
	 * @var MockObject|ItemUpdater
	 */
	private $itemUpdater;
	/**
	 * @var MockObject|GuidGenerator
	 */
	private $guidGenerator;
	/**
	 * @var MockObject|WikibaseEntityPermissionChecker
	 */
	private $permissionChecker;

	private const ALLOWED_TAGS = [ 'some', 'tags', 'are', 'allowed' ];

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 321, '20201111070707' ] );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->permissionChecker = $this->createStub( WikibaseEntityPermissionChecker::class );
	}

	public function testAddStatement(): void {
		$item = NewItem::withId( 'Q123' )->build();
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$newGuid = new StatementGuid( $item->getId(), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'potato';

		$request = new AddItemStatementRequest(
			$item->getId()->getSerialization(),
			$this->getValidNoValueStatementSerialization(),
			$editTags,
			$isBot,
			$comment,
			null
		);

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->guidGenerator->method( 'newStatementId' )->willReturn( $newGuid );

		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions(),
			new StatementList( NewStatementReadModel::noValueFor( 'P123' )->withGuid( $newGuid )->build() )
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with(
				$this->callback( fn( Item $item ) => $item->getStatements()->getFirstStatementWithGuid( (string)$newGuid ) !== null ),
				$this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION )
			)
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$this->permissionChecker = $this->createStub( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( AddItemStatementResponse::class, $response );
		$this->assertSame(
			$updatedItem->getStatements()->getStatementById( $newGuid ),
			$response->getStatement()
		);
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = 'Q321';
		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new AddItemStatementRequest(
					$itemId,
					$this->getValidNoValueStatementSerialization(),
					[],
					false,
					null,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function testValidationError_throwsUseCaseError(): void {
		try {
			$this->newUseCase()->execute(
				new AddItemStatementRequest( 'X123', [], [], false, null, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
		}
	}

	public function testProtectedItem_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );

		$this->permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		try {
			$request = new AddItemStatementRequest(
				$itemId->getSerialization(),
				$this->getValidNoValueStatementSerialization(),
				[],
				false,
				null,
				null
			);
			$this->newUseCase()->execute( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::PERMISSION_DENIED, $e->getErrorCode() );
		}
	}

	private function newUseCase(): AddItemStatement {
		return new AddItemStatement(
			$this->newValidator(),
			$this->getRevisionMetadata,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->guidGenerator,
			$this->permissionChecker
		);
	}

	private function newValidator(): AddItemStatementValidator {
		return new AddItemStatementValidator(
			new ItemIdValidator(),
			new StatementValidator( DeserializerFactory::newStatementDeserializer(
				$this->createStub( PropertyDataTypeLookup::class )
			) ),
			new EditMetadataValidator(
				CommentStore::COMMENT_CHARACTER_LIMIT,
				self::ALLOWED_TAGS
			)
		);
	}

	private function getValidNoValueStatementSerialization(): array {
		return [
			'property' => [
				'id' => 'P123',
			],
			'value' => [
				'type' => 'novalue',
			],
		];
	}

}
