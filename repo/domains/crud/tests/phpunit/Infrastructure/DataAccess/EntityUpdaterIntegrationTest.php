<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Context\RequestContext;
use MediaWiki\Permissions\PermissionManager;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Crud\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\Domains\Crud\WbCrud;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterIntegrationTest extends MediaWikiIntegrationTestCase {

	public function testCreateItem(): void {
		$newRevision = $this->newEntityUpdater()->create(
			NewItem::withLabel( 'en', 'New Item' )->build(),
			$this->createStub( EditMetadata::class )
		);

		$createdItem = $newRevision->getEntity();
		$this->assertNotEmpty( $createdItem->getId() );

		$this->assertEquals(
			$newRevision->getEntity(),
			WbCrud::getItemDataRetriever()->getItemWriteModel( $createdItem->getId() ),
		);
	}

	public function testCreateItemWithStatement(): void {
		$propertyId = new NumericPropertyId( 'P123' );
		$newRevision = $this->newEntityUpdater()->create(
			NewItem::withLabel( 'en', 'New Item' )
				->andStatement( NewStatement::noValueFor( $propertyId ) )
				->build(),
			$this->createStub( EditMetadata::class )
		);

		/** @var Item $createdItem */
		$createdItem = $newRevision->getEntity();
		$this->assertNotNull( $createdItem->getId() );

		$statementId = $createdItem->getStatements()->toArray()[0]->getGuid();
		$this->assertNotNull( $statementId );
		$this->assertStringStartsWith( (string)$createdItem->getId(), $statementId );

		$this->assertEquals(
			$newRevision->getEntity(),
			WbCrud::getItemDataRetriever()->getItemWriteModel( $createdItem->getId() ),
		);
	}

	/**
	 * @dataProvider provideStatementIdAndEntityWithStatement
	 */
	public function testUpdate_removeStatementFromEntity( StatementGuid $statementId, StatementListProvidingEntity $entityToUpdate ): void {
		$this->saveNewEntity( $entityToUpdate );

		$entityToUpdate->getStatements()->removeStatementsWithGuid( (string)$statementId );

		$newRevision = $this->newEntityUpdater()->update( $entityToUpdate, $this->createStub( EditMetadata::class ) );

		$this->assertCount( 0, $newRevision->getEntity()->getStatements() );
	}

	/**
	 * @dataProvider provideStatementIdAndEntityWithStatement
	 */
	public function testUpdate_replaceStatementOnEntity( StatementGuid $statementId, StatementListProvidingEntity $entityToUpdate ): void {
		$newValue = 'new statement value';
		$newStatement = NewStatement::forProperty( 'P321' )
			->withGuid( $statementId )
			->withValue( $newValue )
			->build();

		$this->saveNewEntity( $entityToUpdate );

		$entityToUpdate->getStatements()->replaceStatement( $statementId, $newStatement );

		$newRevision = $this->newEntityUpdater()->update( $entityToUpdate, $this->createStub( EditMetadata::class ) );

		$statementList = $newRevision->getEntity()->getStatements();
		$this->assertSame(
			$newValue,
			$statementList->getFirstStatementWithGuid( (string)$statementId )->getMainSnak()->getDataValue()->getValue()
		);
	}

	/**
	 * @dataProvider provideStatementIdAndEntityWithStatement
	 */
	public function testUpdate_addStatementToEntity( StatementGuid $statementId, StatementListProvidingEntity $entityToUpdate ): void {
		$newValue = 'new statement value';
		$newStatement = NewStatement::forProperty( 'P321' )->withValue( $newValue )->build();

		$this->saveNewEntity( $entityToUpdate );

		$entityToUpdate->getStatements()->addStatement( $newStatement );

		$newRevision = $this->newEntityUpdater()->update( $entityToUpdate, $this->createStub( EditMetadata::class ) );

		$this->assertSame( $entityToUpdate->getId(), $newRevision->getEntity()->getId() );
		$statements = $newRevision->getEntity()->getStatements()->toArray();
		$this->assertSame( $statements[0], $newRevision->getEntity()->getStatements()->getFirstStatementWithGuid( "$statementId" ) );
		$this->assertStringStartsWith( $newRevision->getEntity()->getId()->getSerialization(), $statements[1]->getGuid() );
		$this->assertSame( $newValue, $statements[1]->getMainSnak()->getDataValue()->getValue() );
	}

	public static function provideStatementIdAndEntityWithStatement(): Generator {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::forProperty( 'P321' )
			->withGuid( $statementId )
			->withValue( 'a statement value' )
			->build();
		yield 'item with statement' => [ $statementId, NewItem::withStatement( $statement )->build() ];

		$statementId = new StatementGuid( new NumericPropertyId( 'P123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$statement = NewStatement::forProperty( 'P321' )
			->withGuid( $statementId )
			->withValue( 'a statement value' )
			->build();
		$property = Property::newFromType( 'string' );
		$property->setStatements( new StatementList( $statement ) );
		yield 'property with statement' => [ $statementId, $property ];
	}

	private function saveNewEntity( EntityDocument $entity ): void {
		WikibaseRepo::getEntityStore()->saveEntity(
			$entity,
			__METHOD__,
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
	}

	private function newEntityUpdater(): EntityUpdater {
		$permissionManager = $this->createStub( PermissionManager::class );
		$permissionManager->method( $this->anything() )->willReturn( true );

		return new EntityUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory(),
			new NullLogger(),
			$this->createStub( EditSummaryFormatter::class ),
			$permissionManager,
			WikibaseRepo::getEntityStore(),
			new GuidGenerator(),
			new SettingsArray()
		);
	}

}
