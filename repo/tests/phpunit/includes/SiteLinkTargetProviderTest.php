<?php

namespace Wikibase\Tests\Repo;

use HashSiteStore;
use PHPUnit_Framework_TestCase;
use Site;
use SiteStore;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @covers Wikibase\Repo\SiteLinkTargetProvider
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Marius Hoch < hoo@online.de >
 * @author Thiemo Mättig
 */
class SiteLinkTargetProviderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getSiteListProvider
	 */
	public function testGetSiteList(
		array $groups,
		array $specialGroups,
		array $expectedGlobalIds
	) {
		$provider = new SiteLinkTargetProvider( $this->getSiteStore(), $specialGroups );
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
				[ 'dawiki', 'eswiki' ]
			],
			[
				[ 'species' ], [], [ 'specieswiki' ] ],
			[
				[ 'wikiquote' ],
				[],
				[ 'eswikiquote' ]
			],
			[
				[ 'qwerty' ],
				[],
				[]
			],
			[
				[ 'wikipedia', 'species' ],
				[],
				[ 'dawiki', 'eswiki', 'specieswiki' ]
			],
			[
				[ 'wikipedia', 'wikiquote' ],
				[],
				[ 'dawiki', 'eswiki', 'eswikiquote' ]
			],
			[
				[ 'special' ],
				[ 'species' ],
				[ 'specieswiki' ]
			],
			[
				[ 'wikipedia' ],
				[ 'species' ],
				[ 'dawiki', 'eswiki' ]
			],
			[
				[ 'special', 'wikipedia' ],
				[ 'species', 'wikiquote' ],
				[ 'dawiki', 'eswiki', 'eswikiquote', 'specieswiki' ]
			],
			[
				[],
				[ 'wikipedia' ],
				[]
			],
			[
				[],
				[],
				[]
			],
		];
	}

	/**
	 * @return SiteStore
	 */
	private function getSiteStore() {
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
