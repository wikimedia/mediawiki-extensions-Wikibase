<?php

namespace Wikibase\Repo\Tests\Api;

use MWException;
use ApiUsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * Tests for blocking of direct editing.
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 *
 * @group Database
 * @group medium
 */
class EditPageTest extends WikibaseApiTestCase {

	/**
	 * @group API
	 */
	public function testEditItemDirectly() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$store = $wikibaseRepo->getEntityStore();

		$item = new Item(); //@todo: do this with all kinds of entities.
		$item->setLabel( "en", "EditPageTest" );
		$store->saveEntity( $item, 'testing', $GLOBALS['wgUser'], EDIT_NEW );

		$item->setLabel( "de", "EditPageTest" );

		$data = $wikibaseRepo->getStorageEntitySerializer()->serialize( $item );
		$text = json_encode( $data );

		$title = $wikibaseRepo->getEntityTitleLookup()->getTitleForId( $item->getId() );

		// try to update the item with valid data via the edit action
		$this->setExpectedException( ApiUsageException::class );
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
		global $wgContentHandlerUseDB;

		$id = new ItemId( "Q1234567" );
		$title = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $id );
		$page = new WikiPage( $title );

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
		} catch ( MWException $ex ) {
			if ( !$wgContentHandlerUseDB ) {
				$this->markTestSkipped( 'With $wgContentHandlerUseDB, attempts to use a non-default content modfel will always fail.' );
			} else {
				throw $ex;
			}
		}
	}

}
