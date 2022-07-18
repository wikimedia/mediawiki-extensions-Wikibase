<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use MediaWikiIntegrationTestCase;
use RequestContext;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;
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

		$updater = new MediaWikiEditEntityFactoryItemUpdater(
			RequestContext::getMain(),
			WikibaseRepo::getEditEntityFactory()
		);
		$newRevision = $updater->update( $itemToUpdate, new EditMetadata( [], false, null ) );

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
			WikibaseRepo::getEditEntityFactory()
		);
		$newRevision = $updater->update( $itemToUpdate, new EditMetadata( [], false, null ) );

		$this->assertTrue( $newRevision->getItem()->getStatements()->isEmpty() );
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
