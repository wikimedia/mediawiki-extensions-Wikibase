<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Permissions\PermissionManager;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use RequestContext;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterIntegrationTest extends MediaWikiIntegrationTestCase {

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

	public function provideStatementIdAndEntityWithStatement(): Generator {
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
			$permissionManager
		);
	}

}
