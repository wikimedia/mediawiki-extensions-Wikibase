<?php

namespace Wikibase\Test;
use ApiTestCase;
use Wikibase\Settings as Settings;

/**
 * Tests for blocking of direct editing.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group ApiEditPageTest
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
class ApiEditPageTest extends ApiModifyItemBase {

	/**
	 * @group API
	 */
	function testEditItemDirectly() {
		$content = \Wikibase\ItemContent::newEmpty();
		$content->getItem()->setLabel( "en", "Test" );
		$status = $content->save( "testing", null, EDIT_NEW );

		$this->assertTrue( $status->isOK(), $status->getMessage() ); // sanity check

		$this->login();
		$token = $this->getItemToken();

		$content->getItem()->setLabel( "de", "Test" );
		$data = $content->getItem()->toArray();
		$text = json_encode( $data );

		// try to update the item with valid data via the edit action
		try {
			$this->doApiRequest(
				array(
					'action' => 'edit',
					'pageid' => $content->getTitle()->getArticleID(),
					'text' => $text,
					'token' => $token,
				)
			);

			$this->fail( "Updating an items should not be possible via action=edit" );
		} catch ( \UsageException $ex ) {
			//ok, pass
			//print "\n$ex\n";
		}

	}

	/**
	 * @group API
	 */
	function testEditTextInItemNamespace() {
		global $wgContentHandlerUseDB;

		$handler = \ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		$page = $handler->getWikiPageForId( 1234567 );

		$this->login();
		$token = $this->getItemToken();

		$text = "hallo welt";

		// try to update the item with valid data via the edit action
		try {
			$this->doApiRequest(
				array(
					'action' => 'edit',
					'title' => $page->getTitle()->getPrefixedText(),
					'contentmodel' => CONTENT_MODEL_WIKITEXT,
					'text' => $text,
					'token' => $token,
				)
			);

			$this->fail( "Saving wikitext to the item namespace should not be possible." );
		} catch ( \UsageException $ex ) {
			//ok, pass
			//print "\n$ex\n";
		} catch ( \MWException $ex ) {
			if ( !$wgContentHandlerUseDB ) {
				$this->markTestSkipped( 'With $wgContentHandlerUseDB, attempts to use a non-default content modfel will always fail.' );
			} else {
				throw $ex;
			}
		}

	}
}