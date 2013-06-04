<?php

namespace Wikibase\Test;

use Wikibase\ChangeOpSiteLink;
use Wikibase\ItemContent;
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

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( $this->getSite( 'enwiki' ), 1234 );

		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param Site $linkSite
	 * @param string|null $aliases
	 */
	public function testInvalidConstruct( $linkSite, $linkPage ) {
		$changeOpSiteLink = new ChangeOpSiteLink( $linkSite, $linkPage );
	}

	public function changeOpSiteLinkProvider() {
		$enWiki = $this->getSite( 'enwiki' );
		$deWiki = $this->getSite( 'dewiki' );
		$existingSiteLinks = array( new SiteLink( $deWiki, 'Berlin' ) );
		$enSiteLink = new SiteLink( $enWiki, 'Berlin' );

		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		foreach ( $existingSiteLinks as $siteLink ) {
			$entity->addSiteLink( $siteLink );
		}

		$args = array();
		$args[] = array ( clone $entity, new ChangeOpSiteLink( $enWiki, 'Berlin' ), array_merge( $existingSiteLinks, array ( $enSiteLink ) ) );
		$args[] = array ( clone $entity, new ChangeOpSiteLink( $deWiki, null ), array() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 *
	 * @param Entity $entity
	 * @param ChangeOpSiteLink $changeOpSiteLink
	 * @param string $expectedSiteLinks
	 */
	public function testApply( $entity, $changeOpSiteLink, $expectedSiteLinks ) {
		$changeOpSiteLink->apply( $entity );
		$this->assertEquals(
			SiteLink::siteLinksToArray( $expectedSiteLinks ),
			SiteLink::siteLinksToArray( $entity->getSiteLinks() )
		);
	}

	protected function getSite( $globalId ) {
		$site = new \MediaWikiSite();
		$site->setGlobalId( $globalId );

		return $site;
	}

}
