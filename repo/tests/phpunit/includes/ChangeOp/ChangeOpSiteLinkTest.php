<?php

namespace Wikibase\Test;

use Site;
use Wikibase\ChangeOp\ChangeOpSiteLink;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use InvalidArgumentException;
use Wikibase\SiteLink;

/**
 * @covers Wikibase\ChangeOp\ChangeOpSiteLink
 *
 * @since 0.4
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
