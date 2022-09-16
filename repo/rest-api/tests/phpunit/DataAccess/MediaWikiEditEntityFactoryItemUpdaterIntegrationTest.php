<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use RequestContext;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\StatementEditSummary;
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

	public function testUpdate_ItemLabelUpdated(): void {
		$itemToUpdate = new Item();
		$this->saveNewItem( $itemToUpdate );

		$newLabel = 'potato';
		$newLabelLanguageCode = 'en';
		$itemToUpdate->setLabel( $newLabelLanguageCode, $newLabel );
		$editSummary = $this->createMock( EditSummary::class );

		$updater = new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory(),
			new NullLogger(),
			$this->createStub( EditSummaryFormatter::class )
		);
		$newRevision = $updater->update(
			$itemToUpdate,
			new EditMetadata( [], false, $editSummary )
		);

		$this->assertSame(
			$newLabel,
			$newRevision->getItem()->getLabels()->getByLanguage( $newLabelLanguageCode )->getText()
		);
	}

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

		$updater = new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory(),
			new NullLogger(),
			$this->createStub( EditSummaryFormatter::class )
		);
		$newRevision = $updater->update(
			$itemToUpdate,
			new EditMetadata( [], false, StatementEditSummary::newRemoveSummary( null, $statementToRemove ) )
		);

		$this->assertTrue( $newRevision->getItem()->getStatements()->isEmpty() );
	}

	public function testUpdate_replaceStatementOnItem(): void {
		$itemId = 'Q345';
		$statementGuid = new StatementGuid( new ItemId( $itemId ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$oldStatement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementGuid )
			->withValue( 'old statement value' )
			->build();
		$newStatement = NewStatement::forProperty( 'P123' )
			->withGuid( $statementGuid )
			->withValue( 'new statement value' )
			->build();
		$itemToUpdate = NewItem::withId( $itemId )->andStatement( $oldStatement )->build();

		$this->saveNewItem( $itemToUpdate );

		$itemToUpdate->getStatements()->replaceStatement( $statementGuid, $newStatement );

		$updater = new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory(),
			new NullLogger(),
			$this->createStub( EditSummaryFormatter::class )
		);
		$newRevision = $updater->update(
			$itemToUpdate,
			new EditMetadata( [], false, StatementEditSummary::newReplaceSummary( null, $newStatement ) )
		);
		$statementList = $newRevision->getItem()->getStatements();
		$this->assertNotContains( $oldStatement, $statementList );
		$this->assertContains( $newStatement, $statementList );
	}

	private function saveNewItem( Item $item ): void {
		WikibaseRepo::getEntityStore()->saveEntity(
			$item,
			__METHOD__,
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
	}

}
