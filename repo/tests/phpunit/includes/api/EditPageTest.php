<?php

namespace Wikibase\Test\Api;

use Wikibase\Repo\WikibaseRepo;

/**
 * Tests for blocking of direct editing.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group EditPageTest
 * @group BreakingTheSlownessBarrier
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class EditPageTest extends WikibaseApiTestCase {

	/**
	 * @group API
	 */
	function testEditItemDirectly() {
		$content = \Wikibase\ItemContent::newEmpty(); //@todo: do this with all kinds of entities.
		$content->getItem()->setLabel( "en", "EditPageTest" );
		$status = $content->save( "testing", null, EDIT_NEW );

		$this->assertTrue( $status->isOK(), $status->getMessage() ); // sanity check

		$content->getItem()->setLabel( "de", "EditPageTest" );
		$data = $content->getItem()->toArray();
		$text = json_encode( $data );

		// try to update the item with valid data via the edit action
		$this->setExpectedException( 'UsageException' );
		$this->doApiRequestWithToken(
			array(
				'action' => 'edit',
				'pageid' => $content->getTitle()->getArticleID(),
				'text' => $text,
			)
		);

	}

	/**
	 * @group API
	 */
	function testEditTextInItemNamespace() {
		global $wgContentHandlerUseDB;

		$id = new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 1234567 );
		$page = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getWikiPageForId( $id );

		$text = "hallo welt";

		// try to update the item with valid data via the edit action
		try {
			$this->doApiRequestWithToken(
				array(
					'action' => 'edit',
					'title' => $page->getTitle()->getPrefixedText(),
					'contentmodel' => CONTENT_MODEL_WIKITEXT,
					'text' => $text,
				)
			);

			$this->fail( "Saving wikitext to the item namespace should not be possible." );
		} catch ( \UsageException $ex ) {
			//ok, pass
			//print "\n$ex\n";
			$this->assertTrue( true );
		} catch ( \MWException $ex ) {
			if ( !$wgContentHandlerUseDB ) {
				$this->markTestSkipped( 'With $wgContentHandlerUseDB, attempts to use a non-default content modfel will always fail.' );
			} else {
				throw $ex;
			}
		}

	}
}