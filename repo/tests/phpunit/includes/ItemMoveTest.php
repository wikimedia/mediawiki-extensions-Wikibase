<?php

namespace Wikibase\Test;

use Wikibase\NamespaceUtils;
use \Wikibase\ItemContent;
use WikiPage, Title, WikitextContent;

/**
 * Tests prevention of moving pages in and out of the data NS.
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
 * @group Wikibase
 * @group WikibaseItem
 * @group ItemMoveTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 */
class ItemMoveTest extends \MediaWikiTestCase {

	//@todo: make this a baseclass to use with all types of entities.

	/**
	 * @var ItemContent
	 */
	protected $itemContent;

	/**
	 * @var WikiPage
	 */
	protected $page;

	/**
	 * This is to set up the environment
	 */
	public function setUp() {
		if ( defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot run ItemMoveTest when the client is hooked up to the repo' );
		}

		parent::setUp();

		$this->itemContent = ItemContent::newEmpty();
		$this->itemContent->save( '', null, EDIT_NEW );

		$title = Title::newFromText( 'wbmovetest', $this->getDefaultWikitextNS() );
		$this->page =  new WikiPage( $title );
		$this->page->doEditContent( new WikitextContent( 'foobar' ), 'test' );
	}

	/**
	 * Tests @see WikibaseItem::getIdForSiteLink
	 */
	public function testMovePrevention() {
		// Moving a regular page into data NS onto an existing item
		$title = $this->itemContent->getTitle();
		$this->assertInstanceOf( 'Title', $title ); // sanity check

		$this->assertFalse( $this->page->getTitle()->moveTo( $title ) === true );

		// Moving a regular page into data NS to an invalid location
		$title = Title::newFromText( $this->page->getTitle()->getText(),
			NamespaceUtils::getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM ) ); //@todo: test other types of entities too!
		$this->assertFalse( $this->page->getTitle()->moveTo( $title ) === true );

		// Moving a regular page into data NS to an empty (but valid) location
		$title = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getTitle();
		$this->assertFalse( $this->page->getTitle()->moveTo( $title ) === true );

		// Moving item page out of data NS onto an existing page
		$title = $this->page->getTitle();
		$this->assertFalse( $this->itemContent->getTitle()->moveTo( $title ) === true );

		// Moving item page out of data NS onto a non-existing page
		$title = Title::newFromText( 'wbmovetestitem' );
		$this->assertFalse( $this->itemContent->getTitle()->moveTo( $title ) === true );

		// Moving item to an invalid location in the data NS
		$title = Title::newFromText( $this->page->getTitle()->getText(),
			NamespaceUtils::getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM ) );
		$this->assertFalse( $this->itemContent->getTitle()->moveTo( $title ) === true );

		// Moving item to an valid location in the data NS
		$title = ItemContent::newFromArray( array( 'entity' => 'q42' ) )->getTitle();
		$this->assertFalse( $this->itemContent->getTitle()->moveTo( $title ) === true );
	}

}

