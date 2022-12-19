<?php

namespace Wikibase\Repo\Tests;

use HashSiteStore;
use Site;
use SiteLookup;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @covers \Wikibase\Repo\SiteLinkTargetProvider
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkTargetProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider getSiteListProvider
	 */
	public function testGetSiteList(
		array $groups,
		array $specialGroups,
		array $expectedGlobalIds
	) {
		$provider = new SiteLinkTargetProvider( $this->getSiteLookup(), $specialGroups );
		$siteList = $provider->getSiteList( $groups );

		$globalIds = [];
		/** @var Site $site */
		foreach ( $siteList as $site ) {
			$globalIds[] = $site->getGlobalId();
		}
		$this->assertSame( $expectedGlobalIds, $globalIds );
	}

	public function getSiteListProvider() {
		return [
			[
				[ 'wikipedia' ],
				[],
				[ 'dawiki', 'eswiki' ],
			],
			[
				[ 'species' ], [], [ 'specieswiki' ] ],
			[
				[ 'wikiquote' ],
				[],
				[ 'eswikiquote' ],
			],
			[
				[ 'qwerty' ],
				[],
				[],
			],
			[
				[ 'wikipedia', 'species' ],
				[],
				[ 'dawiki', 'eswiki', 'specieswiki' ],
			],
			[
				[ 'wikipedia', 'wikiquote' ],
				[],
				[ 'dawiki', 'eswiki', 'eswikiquote' ],
			],
			[
				[ 'special' ],
				[ 'species' ],
				[ 'specieswiki' ],
			],
			[
				[ 'wikipedia' ],
				[ 'species' ],
				[ 'dawiki', 'eswiki' ],
			],
			[
				[ 'special', 'wikipedia' ],
				[ 'species', 'wikiquote' ],
				[ 'dawiki', 'eswiki', 'eswikiquote', 'specieswiki' ],
			],
			[
				[],
				[ 'wikipedia' ],
				[],
			],
			[
				[],
				[],
				[],
			],
		];
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
		return new HashSiteStore( [
			$this->newSite( 'dawiki', 'wikipedia' ),
			$this->newSite( 'eswiki', 'wikipedia' ),
			$this->newSite( 'eswikiquote', 'wikiquote' ),
			$this->newSite( 'specieswiki', 'species' ),
		] );
	}

	private function newSite( $globalId, $group ) {
		$site = new Site();
		$site->setGlobalId( $globalId );
		$site->setGroup( $group );
		return $site;
	}

}
