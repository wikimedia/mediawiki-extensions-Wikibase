<?php

namespace Wikibase\Test;

use Site;
use Wikibase\ChangeOpSiteLink;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use InvalidArgumentException;
use Wikibase\SiteLink;

/**
 * @covers Wikibase\ChangeOpSiteLink
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpSiteLinkTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage ) {
		new ChangeOpSiteLink( $siteId, $linkPage );
	}

	public function invalidConstructorProvider() {
		$argLists = array();

		$argLists[] = array( 'enwiki', 1234 );
		$argLists[] = array( 1234, 'Berlin' );

		return $argLists;
	}

	public function changeOpSiteLinkProvider() {
		$existingSiteLinks = array( new SimpleSiteLink( 'dewiki', 'Berlin' ) );
		$enSiteLink = new SimpleSiteLink( 'enwiki', 'Berlin' );

		$item = Item::newEmpty();

		foreach ( $existingSiteLinks as $siteLink ) {
			$item->addSimpleSiteLink( $siteLink );
		}

		$args = array();
		$args[] = array ( clone $item, new ChangeOpSiteLink( 'enwiki', 'Berlin' ), array_merge( $existingSiteLinks, array ( $enSiteLink ) ) );
		$args[] = array ( clone $item, new ChangeOpSiteLink( 'dewiki', null ), array() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 *
	 * @param Item $entity
	 * @param ChangeOpSiteLink $changeOpSiteLink
	 * @param SimpleSiteLink[] $expectedSiteLinks
	 */
	public function testApply( Item $entity, ChangeOpSiteLink $changeOpSiteLink, array $expectedSiteLinks ) {
		$changeOpSiteLink->apply( $entity );

		$this->assertEquals(
			$expectedSiteLinks,
			$entity->getSimpleSiteLinks()
		);
	}

}
