<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\Diff\SiteLinkListPatcher;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers Wikibase\DataModel\Entity\Diff\SiteLinkListPatcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_linksAreReturnedAsIs() {
		$links = new SiteLinkList();
		$links->addNewSiteLink( 'en', 'foo' );
		$links->addNewSiteLink( 'de', 'bar' );

		$this->assertLinksResultsFromPatch( $links, $links, new Diff() );
	}

	private function assertLinksResultsFromPatch( SiteLinkList $expected, SiteLinkList $original, Diff $patch ) {
		$patcher = new SiteLinkListPatcher();
		$actual = $patcher->getPatchedSiteLinkList( $original, $patch );

		$this->assertEquals( $expected, $actual );
	}

	public function testPatchesMultipleSiteLinks() {
		$links = new SiteLinkList();
		$links->addNewSiteLink( 'dewiki', 'bar' );
		$links->addNewSiteLink( 'nlwiki', 'baz', array( new ItemId( 'Q42' ) ) );

		$patch = new Diff( array(
			'nlwiki' => new Diff( array(
				'name'   => new DiffOpChange( 'baz', 'kittens' ),
				'badges' => new Diff(
					array(
						new DiffOpRemove( 'Q42' ),
					),
					false
				)
			) ),
			'frwiki' => new Diff( array(
				'name'   => new DiffOpAdd( 'Berlin' ),
				'badges' => new Diff(
					array(
						new DiffOpAdd( 'Q42' ),
					),
					false
				)
			) )
		) );

		$expectedLinks = new SiteLinkList();
		$expectedLinks->addNewSiteLink( 'dewiki', 'bar' );
		$expectedLinks->addNewSiteLink( 'nlwiki', 'kittens' );
		$expectedLinks->addNewSiteLink( 'frwiki', 'Berlin', array( new ItemId( 'Q42' ) ) );

		$this->assertLinksResultsFromPatch( $expectedLinks, $links, $patch );
	}

	public function testGivenNoBadges_doesNotWarn() {
		$patcher = new SiteLinkListPatcher();
		$patch = new Diff( array(
			'dewiki' => new Diff( array(
				'name' => new DiffOpAdd( 'Berlin' )
			), true )
		) );
		$siteLinks = $patcher->getPatchedSiteLinkList( new SiteLinkList(), $patch );

		$this->assertCount( 1, $siteLinks );
	}

}
