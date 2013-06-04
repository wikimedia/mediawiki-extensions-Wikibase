<?php
namespace Wikibase\Test;

use User;
use Wikibase\NamespaceUtils;
use Wikibase\Item;
use Wikibase\EntityId;
use Wikibase\EntityContentFactory;
use Wikibase\ViewItemAction;
use WikiPage;
use Title;

/**
 * Tests for viewing entities.
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
 * @author Daniel Kinzler
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
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
class ViewEntityActionTest extends ActionTestCase {

	public function setup() {
		parent::setup();
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( "Berlin" );

		$action = $this->createAction( "view", $page );
		$this->assertInstanceOf( 'Wikibase\ViewEntityAction', $action );
	}

	public static function provideShow() {
		return array(
			array(
				'Berlin',
				'/Berlin/'
			)
		);
	}

	/**
	 * @dataProvider provideShow
	 */
	public function testShow( $handle, $regex ) {
		$page = $this->getTestItemPage( $handle );
		$action = $this->createAction( "view", $page );

		$action->show();
		$html = $action->getOutput()->getHTML();

		$this->assertRegExp( $regex, $html );
	}

	public function testShow404() {
		$id = new EntityId( Item::ENTITY_TYPE, 1122334455 );
		$page = EntityContentFactory::singleton()->getWikiPageForId( $id );
		$action = $this->createAction( "view", $page );

		/* @var \FauxResponse $response */
		$response = $action->getRequest()->response();
		$response->header( "HTTP/1.1 200 OK" ); // reset

		// $wgSend404Code disabled -----
		$this->setMwGlobals( 'wgSend404Code', false );

		$action->show();
		$this->assertEquals( 200, $response->getStatusCode(), "response code" );

		// $wgSend404Code enabled -----
		$this->setMwGlobals( 'wgSend404Code', true );

		$action->show();
		$this->assertEquals( 404, $response->getStatusCode(), "response code" );
	}

	public function testGetDiffRevision() {
		$testCases = $this->doDiffRevisionEdits();

		foreach( $testCases as $case ) {
			list( $expected, $oldId, $diffValue, $title ) = $case;
			$page = new \WikiPage( $title );
			$viewItemAction = $this->createAction( 'view', $page );

			$revision = $viewItemAction->getDiffRevision( $oldId, $diffValue, $title );
			$this->assertInstanceOf( '\Revision', $revision );

			$id = $revision->getId();
			$this->assertEquals( $expected, $id, 'Retrieved correct diff revision' );
		}
	}

	public function doDiffRevisionEdits() {
		$item = \Wikibase\Item::newEmpty();
		$item->setId( new \Wikibase\EntityId( \Wikibase\Item::ENTITY_TYPE, 847 ) );
		$item->setDescription( 'en', 'Largest city in Germany' );

        $content = new \Wikibase\ItemContent( $item );
		$status = $content->save( 'create' );
		assert( $status->isOK() );
		$revId1 = $content->getWikiPage()->getRevision()->getId();

		$content->getEntity()->setDescription( 'en', 'Capital of Germany' );
		$status = $content->save( 'update' );
		assert( $status->isOK() );
		$revId2 = $content->getWikiPage()->getRevision()->getId();

		$content->getEntity()->setDescription( 'en', 'City in Germany' );
		$status = $content->save( 'update' );
		assert( $status->isOK() );
		$revId3 = $content->getWikiPage()->getRevision()->getId();

		$title = $content->getTitle();

		return array(
			array( $revId2, $revId1, $revId2, $title ),
			array( $revId3, $revId2, 'next', $title ),
			array( $revId2, $revId2, 'prev', $title ),
			array( $revId3, $revId1, 'cur', $title )
		);
	}

}
