<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use MediaWiki\Permissions\PermissionManager;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use RequestContext;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiEditEntityFactoryItemUpdaterIntegrationTest extends MediaWikiIntegrationTestCase {

	public function testUpdate_StatementRemovedFromItem(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$statementToRemove = NewStatement::forProperty( 'P123' )
			->withGuid( $statementId )
			->withValue( 'statement value' )
			->build();
		$itemToUpdate = NewItem::withId( $itemId )->andStatement( $statementToRemove )->build();

		$this->saveNewItem( $itemToUpdate );

		$itemToUpdate->getStatements()->removeStatementsWithGuid( $statementId );

		$newRevision = $this->newItemUpdater()->update(
			$itemToUpdate,
			new EditMetadata( [], false, StatementEditSummary::newRemoveSummary( null, $statementToRemove ) )
		);

		$this->assertCount( 0, $newRevision->getItem()->getStatements() );
	}

	public function testUpdate_replaceStatementOnItem(): void {
		$itemId = 'Q345';
		$statementGuid = new StatementGuid( new ItemId( $itemId ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$oldStatement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementGuid )
			->withValue( 'old statement value' )
			->build();
		$newValue = 'new statement value';
		$newStatement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementGuid )
			->withValue( $newValue )
			->build();
		$itemToUpdate = NewItem::withId( $itemId )->andStatement( $oldStatement )->build();

		$this->saveNewItem( $itemToUpdate );

		$itemToUpdate->getStatements()->replaceStatement( $statementGuid, $newStatement );

		$newRevision = $this->newItemUpdater()->update(
			$itemToUpdate,
			new EditMetadata( [], false, StatementEditSummary::newReplaceSummary( null, $newStatement ) )
		);

		$statementList = $newRevision->getItem()->getStatements();
		$this->assertSame(
			$newValue,
			$statementList->getStatementById( $statementGuid )->getMainSnak()->getDataValue()->getValue()
		);
	}

	private function saveNewItem( Item $item ): void {
		WikibaseRepo::getEntityStore()->saveEntity(
			$item,
			__METHOD__,
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
	}

	private function newItemUpdater(): MediaWikiEditEntityFactoryItemUpdater {
		$permissionManager = $this->createStub( PermissionManager::class );
		$permissionManager->method( $this->anything() )->willReturn( true );

		return new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory(),
			new NullLogger(),
			$this->createStub( EditSummaryFormatter::class ),
			$permissionManager,
			new StatementReadModelConverter( new StatementGuidParser( new ItemIdParser() ) )
		);
	}

}
