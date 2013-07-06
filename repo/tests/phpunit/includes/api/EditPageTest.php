<?php

namespace Wikibase\Test\Api;
use ApiTestCase;

/**
 * Tests for blocking of direct editing.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
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
class EditPageTest extends ModifyEntityTestBase {

	/**
	 * @group API
	 */
	function testEditItemDirectly() {
		$content = \Wikibase\ItemContent::newEmpty(); //@todo: do this with all kinds of entities.
		$content->getItem()->setLabel( "en", "Test" );
		$status = $content->save( "testing", null, EDIT_NEW );

		$this->assertTrue( $status->isOK(), $status->getMessage() ); // sanity check

		$this->login();
		$token = $this->getEditToken();

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

		$id = new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 1234567 );
		$page = \Wikibase\EntityContentFactory::singleton()->getWikiPageForId( $id );

		$this->login();
		$token = $this->getEditToken();

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