<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
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

	protected function setUp(): void {
		parent::setUp();

		$this->context = $this->createStub( IContextSource::class );
		$this->context->method( 'getUser' )->willReturn( $this->createStub( User::class ) );
		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->logger = $this->createStub( LoggerInterface::class );
		$this->summaryFormatter = $this->createStub( EditSummaryFormatter::class );
		$this->permissionManager = $this->createStub( PermissionManager::class );
		$this->permissionManager->method( 'userHasRight' )->willReturn( true );
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
				Status::newGood( [
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
		$errorStatus = Status::newFatal( 'failed to save. sad times.' );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $errorStatus );
		$this->editEntityFactory = $this->createStub( MediaWikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->expectExceptionObject( new EntityUpdateFailed( (string)$errorStatus ) );

		$this->newEntityUpdater()->update( $entityToUpdate, $this->createStub( EditMetadata::class ) );
	}

	/**
	 * @dataProvider provideEntityAndErrorStatus
	 */
	public function testGivenEditPrevented_throwsCorrespondingException( EntityDocument $entityToUpdate, Status $errorStatus ): void {
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
			"basic 'actionthrottledtext' error" => [ Status::newFatal( 'actionthrottledtext' ) ],
			"wfMessage 'actionthrottledtext' error" => [ Status::newFatal( wfMessage( 'actionthrottledtext' ) ) ],
			"'abusefilter-disallowed' error" => [ Status::newFatal( 'abusefilter-disallowed' ) ],
			"'spam-blacklisted-link' error" => [ Status::newFatal( 'spam-blacklisted-link' ) ],
			"'spam-blacklisted-email' error" => [ Status::newFatal( 'spam-blacklisted-email' ) ],
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
		$saveStatus = Status::newGood( [
			'revision' => new EntityRevision( new FakeEntityDocument(), 123, '20221111070707' ),
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
			$this->newEntityUpdater()->update(
				$this->createStub( EntityDocument::class ),
				$this->createStub( EditMetadata::class )
			)
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
			$this->permissionManager
		);
	}

}
