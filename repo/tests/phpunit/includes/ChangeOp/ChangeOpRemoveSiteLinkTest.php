<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\ChangeOp\ChangeOpRemoveSiteLink;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpRemoveSiteLink
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveSiteLinkTest extends TestCase {

	public function changeOpSiteLinkProvider() {
		$deSiteLink = new SiteLink( 'dewiki', 'Berlin' );
		$plSiteLink = new SiteLink( 'plwiki', 'Berlin', [ new ItemId( 'Q42' ) ] );

		$existingSiteLinks = [
			$deSiteLink,
			$plSiteLink,
		];

		$args = [];

		// deleting sitelink
		$args[] = [
			$existingSiteLinks,
			new ChangeOpRemoveSiteLink( 'dewiki' ),
			[ $plSiteLink ],
		];

		return $args;
	}

	/**
	 * @dataProvider changeOpSiteLinkProvider
	 */
	public function testApply( array $existingSiteLinks, ChangeOpRemoveSiteLink $changeOpRemoveSiteLink, array $expectedSiteLinks ) {
		$item = new Item();
		$item->setSiteLinkList( new SiteLinkList( $existingSiteLinks ) );

		$changeOpResult = $changeOpRemoveSiteLink->apply( $item );

		$this->assertEquals(
			$expectedSiteLinks,
			array_values( $item->getSiteLinkList()->toArray() )
		);
		$this->assertTrue( $changeOpResult->isEntityChanged() );
	}

	public function testGivenAttemptToRemoveNonExistentSiteLink_applyIndicatesNoChange() {
		$changeOp = new ChangeOpRemoveSiteLink( 'enwiki' );

		$changeOpResult = $changeOp->apply( new Item() );

		$this->assertFalse( $changeOpResult->isEntityChanged() );
	}
}
