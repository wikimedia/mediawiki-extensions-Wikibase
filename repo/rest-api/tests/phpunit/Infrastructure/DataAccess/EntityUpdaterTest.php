<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Context\IContextSource;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Status\Status;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Fixtures\CustomEntityId;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\EditEntityStatus;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\AbuseFilterException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\ResourceTooLargeException;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdateFailed;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\Exceptions\EntityUpdatePrevented;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterTest extends TestCase {

	private IContextSource $context;
	private MediaWikiEditEntityFactory $editEntityFactory;
	private LoggerInterface $logger;
	private EditSummaryFormatter $summaryFormatter;
	private PermissionManager $permissionManager;
	private EntityStore $entityStore;

	protected function setUp(): void {
		parent::setUp();

		$this->context = $this->createStub( IContextSource::class );
		$this->context->method( 'getUser' )->willReturn( $this->createStub( User::class ) );
		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->logger = $this->createStub( LoggerInterface::class );
		$this->summaryFormatter = $this->createStub( EditSummaryFormatter::class );
		$this->permissionManager = $this->createStub( PermissionManager::class );
		$this->permissionManager->method( 'userHasRight' )->willReturn( true );
		$this->entityStore = $this->createStub( EntityStore::class );
		$this->entityStore->method( 'assignFreshId' )->willReturnCallback(
			fn( EntityDocument $entity ) => $entity->setId(
				$entity->getType() === Item::ENTITY_TYPE
					? new ItemId( 'Q123' )
					: new NumericPropertyId( 'P123' )
			)
		);
	}

	public function testCreate(): void {
		$entityToCreate = NewItem::withLabel( 'en', 'English Label' )
			->andDescription( 'en', 'English Description' )
			->andStatement( NewStatement::noValueFor( 'P777' ) )
			->build();
		$editMetadata = new EditMetadata( [], false, $this->createStub( EditSummary::class ) );

		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$expectedFormattedSummary = 'FORMATTED SUMMARY';
		$expectedAssignedId = new ItemId( 'Q64' );

		$this->summaryFormatter = $this->createMock( EditSummaryFormatter::class );
		$this->summaryFormatter->expects( $this->once() )
			->method( 'format' )
			->with( $editMetadata->getSummary() )
			->willReturn( $expectedFormattedSummary );

		$editEntity = $this->createMock( EditEntity::class );
		$editEntity->expects( $this->once() )
			->method( 'attemptSave' )
			->with(
				$entityToCreate,
				$expectedFormattedSummary,
				EDIT_NEW,
				false,
				false,
				$editMetadata->getTags()
			)
			->willReturnCallback( function ( Item $item ) use ( $expectedAssignedId, $expectedRevisionId, $expectedRevisionTimestamp ) {
				$this->assertEquals( $expectedAssignedId, $item->getId() );
				$statementId = $item->getStatements()->toArray()[0]->getGuid();
				$this->assertNotNull( $statementId );
				$this->assertStringStartsWith( (string)$item->getId(), $statementId );

				return EditEntityStatus::newGood( [
					'revision' => new EntityRevision( $item->copy(), $expectedRevisionId, $expectedRevisionTimestamp ),
				] );
			} );

		$this->editEntityFactory = $this->createMock( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->expects( $this->once() )
			->method( 'newEditEntity' )
			->with( $this->context, $entityToCreate->getId() )
			->willReturn( $editEntity );

		$this->entityStore = $this->createStub( EntityStore::class );
		$this->entityStore->method( 'assignFreshId' )->willReturnCallback(
			fn( Item $item ) => $item->setId( $expectedAssignedId )
		);

		$entityRevision = $this->newEntityUpdater()->create( $entityToCreate, $editMetadata );

		$this->assertEquals( $entityToCreate, $entityRevision->getEntity() );
		$this->assertSame( $expectedRevisionId, $entityRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $entityRevision->getTimestamp() );
	}

	/**
	 * @dataProvider provideEntityAndEditMetadata
	 */
	public function testUpdate( EntityDocument $entityToUpdate, EditMetadata $editMetadata ): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$expectedRevisionEntity = $entityToUpdate->copy();
		$expectedFormattedSummary = 'FORMATTED SUMMARY';

		$this->summaryFormatter = $this->createMock( EditSummaryFormatter::class );
		$this->summaryFormatter->expects( $this->once() )
			->method( 'format' )
			->with( $editMetadata->getSummary() )
			->willReturn( $expectedFormattedSummary );

		$editEntity = $this->createMock( EditEntity::class );
		$editEntity->expects( $this->once() )
			->method( 'attemptSave' )
			->with(
				$entityToUpdate,
				$expectedFormattedSummary,
				$editMetadata->isBot() ? EDIT_UPDATE | EDIT_FORCE_BOT : EDIT_UPDATE,
				false,
				false,
				$editMetadata->getTags()
			)
			->willReturn(
				EditEntityStatus::newGood( [
					'revision' => new EntityRevision( $expectedRevisionEntity, $expectedRevisionId, $expectedRevisionTimestamp ),
				] )
			);

		$this->editEntityFactory = $this->createMock( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->expects( $this->once() )
			->method( 'newEditEntity' )
			->with( $this->context, $entityToUpdate->getId() )
			->willReturn( $editEntity );

		$entityRevision = $this->newEntityUpdater()->update( $entityToUpdate, $editMetadata );

		$this->assertEquals( $entityToUpdate, $entityRevision->getEntity() );
		$this->assertSame( $expectedRevisionId, $entityRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $entityRevision->getTimestamp() );
	}

	public function provideEntityAndEditMetadata(): array {
		$editMetadata = [
			'bot edit' => [ new EditMetadata( [], true, $this->createStub( EditSummary::class ) ) ],
			'user edit' => [ new EditMetadata( [], false, $this->createStub( EditSummary::class ) ) ],
		];

		$dataSet = [];
		foreach ( $this->provideEntity() as $entityType => $entity ) {
			foreach ( $editMetadata as $metadataType => $metadata ) {
				$dataSet["$entityType with $metadataType"] = array_merge( $entity, $metadata );
			}
		}

		return $dataSet;
	}

	/**
	 * @dataProvider provideEntity
	 */
	public function testGivenSavingFails_throwsGenericException( EntityDocument $entityToUpdate ): void {
		$errorStatus = EditEntityStatus::newFatal( 'failed to save. sad times.' );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $errorStatus );
		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->expectExceptionObject( new EntityUpdateFailed( (string)$errorStatus ) );

		$this->newEntityUpdater()->update( $entityToUpdate, $this->createStub( EditMetadata::class ) );
	}

	/**
	 * @dataProvider provideEntity
	 */
	public function testGivenResourceTooLarge_throwsCorrespondingException( EntityDocument $entity ): void {
		$maxSizeAsBytes = 1024;
		$maxSizeAsKiloBytes = 1;

		$errorStatus = EditEntityStatus::newFatal( 'wikibase-error-entity-too-big', [ 'size' => $maxSizeAsBytes ] );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $errorStatus );

		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->expectExceptionObject( new ResourceTooLargeException( $maxSizeAsKiloBytes ) );
		$this->newEntityUpdater()->update( $entity, $this->createStub( EditMetadata::class ) );
	}

	/**
	 * @dataProvider provideEntity
	 */
	public function testGivenAbuseFilterMatch_throwsCorrespondingException( EntityDocument $entity ): void {
		$filterId = 777;
		$filterDescription = 'bad word rejecting filter';

		$errorStatus = EditEntityStatus::newFatal( 'abusefilter-disallowed', $filterDescription, $filterId );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $errorStatus );

		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->expectExceptionObject( new AbuseFilterException( $filterId, $filterDescription ) );
		$this->newEntityUpdater()->update( $entity, $this->createStub( EditMetadata::class ) );
	}

	/**
	 * @dataProvider provideEntityAndErrorStatus
	 */
	public function testGivenEditPrevented_throwsCorrespondingException(
		EntityDocument $entityToUpdate,
		EditEntityStatus $errorStatus
	): void {
		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $errorStatus );
		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->expectExceptionObject( new EntityUpdatePrevented( (string)$errorStatus ) );

		$this->newEntityUpdater()->update( $entityToUpdate, $this->createStub( EditMetadata::class ) );
	}

	public function provideEntity(): Generator {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$item = NewItem::withId( $itemId )
			->andLabel( 'en', 'English Label' )
			->andDescription( 'en', 'English Description' )
			->andStatement(
				NewStatement::someValueFor( 'P321' )->withGuid( $statementId )->build()
			)->build();
		yield 'item' => [ $item ];

		$propertyId = new NumericPropertyId( 'P123' );
		$statementId = new StatementGuid( $propertyId, 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::someValueFor( 'P321' )->withGuid( $statementId )->build();
		$property = Property::newFromType( 'string' );
		$property->setId( $propertyId );
		$property->setStatements( new StatementList( $statement ) );
		yield 'property' => [ $property ];
	}

	public function provideEntityAndErrorStatus(): array {
		$errorStatuses = [
			"basic 'actionthrottledtext' error" => [ EditEntityStatus::newFatal( 'actionthrottledtext' ) ],
			"wfMessage 'actionthrottledtext' error" => [ EditEntityStatus::newFatal( wfMessage( 'actionthrottledtext' ) ) ],
			"'spam-blacklisted-link' error" => [ EditEntityStatus::newFatal( 'spam-blacklisted-link' ) ],
			"'spam-blacklisted-email' error" => [ EditEntityStatus::newFatal( 'spam-blacklisted-email' ) ],
		];

		$dataSet = [];
		foreach ( $this->provideEntity() as $entityType => $entity ) {
			foreach ( $errorStatuses as $errorStatusType => $errorStatus ) {
				$dataSet["$entityType with $errorStatusType"] = array_merge( $entity, $errorStatus );
			}
		}

		return $dataSet;
	}

	public function testGivenSavingSucceedsWithErrors_logsErrors(): void {
		$entity = new FakeEntityDocument( new CustomEntityId( 'X1' ) );
		$saveStatus = EditEntityStatus::newGood( [
			'revision' => new EntityRevision(
				$entity,
				123,
				'20221111070707'
			),
		] );
		$saveStatus->merge( Status::newFatal( 'saving succeeded but something else went wrong' ) );
		$saveStatus->setOK( true );

		$this->logger = $this->createMock( LoggerInterface::class );
		$this->logger->expects( $this->once() )
			->method( 'warning' )
			->with( (string)$saveStatus );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $saveStatus );

		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->assertInstanceOf(
			EntityRevision::class,
			$this->newEntityUpdater()->update( $entity, $this->createStub( EditMetadata::class ) )
		);
	}

	public function testGivenUserWithoutBotRight_throwsForBotEdit(): void {
		$this->permissionManager = $this->createMock( PermissionManager::class );
		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with( $this->context->getUser(), 'bot' )
			->willReturn( false );

		$this->expectException( RuntimeException::class );

		$this->newEntityUpdater()->update(
			$this->createStub( EntityDocument::class ),
			new EditMetadata( [], true, $this->createStub( EditSummary::class ) )
		);
	}

	private function newEntityUpdater(): EntityUpdater {
		return new EntityUpdater(
			$this->context,
			$this->editEntityFactory,
			$this->logger,
			$this->summaryFormatter,
			$this->permissionManager,
			$this->entityStore,
			new GuidGenerator()
		);
	}

}
