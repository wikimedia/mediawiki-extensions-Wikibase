<?php

namespace Wikibase\Test;

use \Wikibase\Item;
use \Wikibase\ItemContent;

/**
 * Tests for the SpecialItemByTitle class.
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
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SpecialEntityDataTest extends SpecialPageTestBase {

	protected function saveItem( Item $item ) {
		$content = ItemContent::newFromItem( $item );
		$content->save( "testing", null, EDIT_NEW );
	}

	protected function newSpecialPage() {
		return new \SpecialEntityData();
	}

	//TODO: test other formats and other entity types.

	public function testExecute() {
		$item = Item::newEmpty();
		$item->setLabel( 'en', 'Raarrr' );
		$this->saveItem( $item );

		list( $output, ) = $this->executeSpecialPage( $item->getId()->getPrefixedId() );
		$this->assertNotEmpty( $output, "is output empty?" );

		//TODO: check response headers

		$data = json_decode( $output, true );
		$this->assertNotEmpty( $output, "json deserialization ok?" );

		$this->assertArrayHasKey( 'id', $data, "`id` field expected" );
		$this->assertEquals( $item->getId()->getPrefixedId(), $data['id'], "does ID match?" );

		$this->assertArrayHasKey( 'labels', $data, "`label` field expected" );
		$this->assertArrayHasKey( 'en', $data['labels'], "`en` field expected" );
		$this->assertArrayHasKey( 'value', $data['labels']['en'], "`value` field expected" );
		$this->assertEquals( $item->getLabel( 'en' ), $data['labels']['en']['value'], "does field value match?" );
	}

}
