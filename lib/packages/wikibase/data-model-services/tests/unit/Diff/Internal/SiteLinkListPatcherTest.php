<?php

namespace Wikibase\DataModel\Services\Tests\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\Internal\SiteLinkListPatcher;
use Wikibase\DataModel\SiteLinkList;

/**
 * @covers \Wikibase\DataModel\Services\Diff\Internal\SiteLinkListPatcher
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkListPatcherTest extends TestCase {

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
		$links->addNewSiteLink( 'nlwiki', 'baz', [ new ItemId( 'Q42' ) ] );

		$patch = new Diff( [
			'nlwiki' => new Diff( [
				'name'   => new DiffOpChange( 'baz', 'kittens' ),
				'badges' => new Diff(
					[
						new DiffOpRemove( 'Q42' ),
					],
					false
				),
			] ),
			'frwiki' => new Diff( [
				'name'   => new DiffOpAdd( 'Berlin' ),
				'badges' => new Diff(
					[
						new DiffOpAdd( 'Q42' ),
					],
					false
				),
			] ),
		] );

		$expectedLinks = new SiteLinkList();
		$expectedLinks->addNewSiteLink( 'dewiki', 'bar' );
		$expectedLinks->addNewSiteLink( 'nlwiki', 'kittens' );
		$expectedLinks->addNewSiteLink( 'frwiki', 'Berlin', [ new ItemId( 'Q42' ) ] );

		$this->assertLinksResultsFromPatch( $expectedLinks, $links, $patch );
	}

	public function testGivenNoBadges_doesNotWarn() {
		$patcher = new SiteLinkListPatcher();
		$patch = new Diff( [
			'dewiki' => new Diff( [
				'name' => new DiffOpAdd( 'Berlin' ),
			], true ),
		] );
		$siteLinks = $patcher->getPatchedSiteLinkList( new SiteLinkList(), $patch );

		$this->assertCount( 1, $siteLinks );
	}

}
