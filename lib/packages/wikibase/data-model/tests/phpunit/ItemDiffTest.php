<?php
namespace Wikibase\Test;
use Wikibase\SiteLink;
use Wikibase\Item;

/**
 * Tests for the Wikibase\ItemDiff class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */

class ItemDiffTest extends EntityDiffOldTest {

	public static function provideApplyData() {
		$originalTests = parent::generateApplyData( \Wikibase\Item::ENTITY_TYPE );
		$tests = array();

		// add link ------------------------------
		$a = Item::newEmpty();
		$a->addSiteLink( SiteLink::newFromText( 'enwiki', 'Test' ) );

		$b = $a->copy();
		$b->addSiteLink( SiteLink::newFromText(  'dewiki', 'Test' ) );

		$tests[] = array( $a, $b );

		// remove link
		$a = Item::newEmpty();
		$a->addSiteLink( SiteLink::newFromText(  'enwiki', 'Test' ), 'set' );
		$a->addSiteLink( SiteLink::newFromText(  'dewiki', 'Test' ), 'set' );

		$b = $a->copy();
		$b->removeSiteLink( 'enwiki' );

		$tests[] = array( $a, $b );

		// change link
		$a = Item::newEmpty();
		$a->addSiteLink( SiteLink::newFromText(  'enwiki', 'Test' ), 'set' );

		$b = $a->copy();
		$b->addSiteLink( SiteLink::newFromText(  'enwiki', 'Test!!!' ), 'set' );

		$tests[] = array( $a, $b );

		return array_merge( $originalTests, $tests );
	}

	/**
	 * @dataProvider provideApplyData
	 */
	public function testApply( \Wikibase\Entity $a, \Wikibase\Entity $b ) {
		parent::testApply( $a, $b );

		$a->patch( $a->getDiff( $b ) );

		$this->assertArrayEquals( $a->getLabels(), $b->getLabels() );
		$this->assertArrayEquals( $a->getDescriptions(), $b->getDescriptions() );
		$this->assertArrayEquals( $a->getAllAliases(), $b->getAllAliases() );
		$this->assertArrayEquals( SiteLink::siteLinksToArray( $a->getSiteLinks() ), SiteLink::siteLinksToArray( $b->getSiteLinks() ) );
	}

}
