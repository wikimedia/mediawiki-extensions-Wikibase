<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\AddItemStatement;

use CommentStore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataTypeFactoryValueTypeLookup;
use Wikibase\Repo\RestApi\Infrastructure\DataValuesValueDeserializer;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementErrorResponse;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\UseCases\ErrorResponse;
use Wikibase\Repo\RestApi\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Validation\StatementValidator;
use Wikibase\Repo\Tests\RestApi\Domain\Model\EditMetadataHelper;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemStatementTest extends TestCase {

	use EditMetadataHelper;

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

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->permissionChecker = $this->createStub( WikibaseEntityPermissionChecker::class );
	}

	public function testAddStatement(): void {
		$item = NewItem::withId( 'Q123' )->build();
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$newGuid = $item->getId() . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
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
		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->guidGenerator->method( 'newGuid' )->willReturn( $newGuid );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with( $item, $this->expectEquivalentMetadata( $editTags, $isBot, $comment, EditSummary::ADD_ACTION ) )
			->willReturn( new ItemRevision( $item, $modificationTimestamp, $postModificationRevisionId ) );

		$this->permissionChecker = $this->createStub( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->method( 'canEdit' )->willReturn( true );

		$useCase = $this->newUseCase();

		$response = $useCase->execute( $request );

		$this->assertInstanceOf( AddItemStatementSuccessResponse::class, $response );
		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $newGuid ) );
		$this->assertSame( $newGuid, $response->getStatement()->getGuid() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testGivenItemNotFound_returnsErrorResponse(): void {
		$itemId = 'Q321';

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::itemNotFound() );

		$response = $this->newUseCase()->execute(
			new AddItemStatementRequest(
				$itemId,
				$this->getValidNoValueStatementSerialization(),
				[],
				false,
				null,
				null
			)
		);

		$this->assertInstanceOf( AddItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::ITEM_NOT_FOUND, $response->getCode() );
		$this->assertStringContainsString( $itemId, $response->getMessage() );
	}

	public function testValidationError_returnsErrorResponse(): void {
		$request = new AddItemStatementRequest( 'X123', [], [], false, null, null );

		$response = $this->newUseCase()->execute( $request );
		$this->assertInstanceOf( AddItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::INVALID_ITEM_ID, $response->getCode() );
	}

	public function testRedirect(): void {
		$redirectSource = 'Q321';
		$redirectTarget = 'Q123';

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::redirect( new ItemId( $redirectTarget ) ) );

		$response = $this->newUseCase()->execute(
			new AddItemStatementRequest(
				$redirectSource,
				$this->getValidNoValueStatementSerialization(),
				[],
				false,
				null,
				null
			)
		);

		$this->assertInstanceOf( AddItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::ITEM_REDIRECTED, $response->getCode() );
		$this->assertStringContainsString( $redirectTarget, $response->getMessage() );
	}

	public function testProtectedItem_returnsErrorResponse(): void {
		$itemId = new ItemId( 'Q123' );
		$request = new AddItemStatementRequest(
			$itemId->getSerialization(),
			$this->getValidNoValueStatementSerialization(),
			[],
			false,
			null,
			null
		);
		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->permissionChecker = $this->createMock( WikibaseEntityPermissionChecker::class );
		$this->permissionChecker->expects( $this->once() )
			->method( 'canEdit' )
			->with( User::newAnonymous(), $itemId )
			->willReturn( false );

		$response = $this->newUseCase()->execute( $request );
		$this->assertInstanceOf( AddItemStatementErrorResponse::class, $response );
		$this->assertSame( ErrorResponse::PERMISSION_DENIED, $response->getCode() );
	}

	private function newUseCase(): AddItemStatement {
		return new AddItemStatement(
			$this->newValidator(),
			$this->revisionMetadataRetriever,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->guidGenerator,
			$this->permissionChecker
		);
	}

	private function newValidator(): AddItemStatementValidator {
		$entityIdParser = WikibaseRepo::getEntityIdParser();
		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			$entityIdParser,
			$this->createStub( PropertyDataTypeLookup::class ),
			new DataValuesValueDeserializer(
				new DataTypeFactoryValueTypeLookup( WikibaseRepo::getDataTypeFactory() ),
				$entityIdParser,
				WikibaseRepo::getDataValueDeserializer(),
				WikibaseRepo::getDataTypeValidatorFactory()
			)
		);

		$statementDeserializer = new StatementDeserializer(
			$propertyValuePairDeserializer,
			new ReferenceDeserializer( $propertyValuePairDeserializer )
		);

		return new AddItemStatementValidator(
			new ItemIdValidator(),
			new StatementValidator( $statementDeserializer ),
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
