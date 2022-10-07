<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for blocking of direct editing.
 *
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 *
 * @group Database
 * @group medium
 * @covers \Wikibase\Repo\RepoHooks::onApiCheckCanExecute
 */
class EditPageTest extends WikibaseApiTestCase {

	/**
	 * @group API
	 */
	public function testEditItemDirectly() {
		$store = $this->getEntityStore();

		$item = new Item(); //@todo: do this with all kinds of entities.
		$item->setLabel( "en", "EditPageTest" );
		$store->saveEntity( $item, 'testing', $this->user, EDIT_NEW );

		$item->setLabel( "de", "EditPageTest" );

		$data = WikibaseRepo::getStorageEntitySerializer()->serialize( $item );
		$text = json_encode( $data );

		$title = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $item->getId() );

		// try to update the item with valid data via the edit action
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken(
			[
				'action' => 'edit',
				'pageid' => $title->getArticleID(),
				'text' => $text,
			]
		);
	}

	/**
	 * @group API
	 */
	public function testEditTextInItemNamespace() {
		$id = new ItemId( "Q1234567" );
		$title = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $id );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );

		$text = "hallo welt";

		// try to update the item with valid data via the edit action
		try {
			$this->doApiRequestWithToken(
				[
					'action' => 'edit',
					'title' => $page->getTitle()->getPrefixedText(),
					'contentmodel' => CONTENT_MODEL_WIKITEXT,
					'text' => $text,
				]
			);

			$this->fail( "Saving wikitext to the item namespace should not be possible." );
		} catch ( ApiUsageException $ex ) {
			$this->assertTrue( true );
		}
	}

}
