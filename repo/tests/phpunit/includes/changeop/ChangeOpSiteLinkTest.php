<?php

namespace Wikibase\Test;

use Site;
use Wikibase\ChangeOpSiteLink;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use InvalidArgumentException;

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
 * @author Michał Łazowik
 */
class ChangeOpSiteLinkTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testConstructorWithInvalidArguments( $siteId, $linkPage, $badges = null ) {
		new ChangeOpSiteLink( $siteId, $linkPage, $badges );
	}

	public function invalidConstructorProvider() {
		$argLists = array();

		$argLists[] = array( 'enwiki', 1234 );
		$argLists[] = array( 1234, 'Berlin' );
		$argLists[] = array( 'enwiki', 'Berlin', 'Nyan Certified' );

		return $argLists;
	}

	public function changeOpSiteLinkProvider() {
		$deSiteLink = new SimpleSiteLink( 'dewiki', 'Berlin' );
		$enSiteLink = new SimpleSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q149' ) ) );
		$plSiteLink = new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$existingSiteLinks = array(
			$deSiteLink,
			$plSiteLink
		);

		$item = Item::newEmpty();

		foreach ( $existingSiteLinks as $siteLink ) {
			$item->addSimpleSiteLink( $siteLink );
		}

		$args = array();

		// adding sitelink with badges
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'enwiki', 'Berlin', array( 'Q149' ) ),
			array_merge( $existingSiteLinks, array ( $enSiteLink ) )
		);

		// deleting sitelink
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'dewiki', null ),
			array( $plSiteLink )
		);

		// setting badges on existing sitelink
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'plwiki', 'Berlin', array( 'Q42', 'Q149' ) ),
			array(
				$deSiteLink,
				new SimpleSiteLink( 'plwiki', 'Berlin', array( new ItemId( 'Q42' ), new ItemId( 'Q149' ) ) )
			)
		);

		// changing sitelink without modifying badges
		$args[] = array(
			clone $item,
			new ChangeOpSiteLink( 'plwiki', 'Test' ),
			array(
				$deSiteLink,
				new SimpleSiteLink( 'plwiki', 'Test', array( new ItemId( 'Q42' ) ) )
			)
		);

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
