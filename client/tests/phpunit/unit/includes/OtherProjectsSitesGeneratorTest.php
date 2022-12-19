<?php

namespace Wikibase\Client\Tests\Unit;

use HashSiteStore;
use MediaWikiCoversValidator;
use Site;
use SiteLookup;
use Wikibase\Client\OtherProjectsSitesGenerator;

/**
 * @covers \Wikibase\Client\OtherProjectsSitesGenerator
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 * @group WikibaseIntegration
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch < hoo@online.de >
 */
class OtherProjectsSitesGeneratorTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	/**
	 * @dataProvider otherProjectSitesProvider
	 */
	public function testOtherProjectSiteIds(
		array $supportedSites,
		$localSiteId,
		array $expectedSiteIds
	) {
		$otherProjectsSitesProvider = new OtherProjectsSitesGenerator(
			$this->getSiteLookupMock(),
			$localSiteId,
			[ 'wikidata' ]
		);

		$this->assertEquals(
			$expectedSiteIds,
			$otherProjectsSitesProvider->getOtherProjectsSiteIds( $supportedSites )
		);
	}

	public function otherProjectSitesProvider() {
		return [
			'Same language' => [
				[ 'wikipedia', 'wikisource' ],
				'frwikisource',
				[ 'frwiki' ],
			],
			'Same language + only one in group' => [
				[ 'wikipedia', 'wikisource', 'commons' ],
				'frwikisource',
				[ 'frwiki', 'commonswiki' ],
			],
			'Only one in group' => [
				[ 'wikipedia', 'wikisource', 'commons' ],
				'eswiki',
				[ 'commonswiki' ],
			],
			'Special group' => [
				[ 'wikipedia', 'wikisource', 'special' ],
				'eswiki',
				[ 'wikidatawiki' ],
			],
			'Special group + language' => [
				[ 'wikipedia', 'wikisource', 'special' ],
				'frwiki',
				[ 'frwikisource', 'wikidatawiki' ],
			],
			'No other sites' => [
				[ 'wikipedia', 'wikisource' ],
				'eswiki',
				[],
			],
		];
	}

	public function testOtherProjectSiteIds_unknownSite() {
		$otherProjectsSitesProvider = new OtherProjectsSitesGenerator(
			$this->getSiteLookupMock(),
			'kittenswiki',
			[ 'wikidata' ]
		);

		// getOtherProjectsSiteIds does wfWarn in case it's being called with a siteid
		// it doesn't know about. That's fine, we can just ignore that.
		$result = @$otherProjectsSitesProvider->getOtherProjectsSiteIds( [
			'wikipedia',
			'wikisource',
		] );

		$this->assertSame( [], $result );
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookupMock() {
		$sites = [];

		$site = new Site();
		$site->setGlobalId( 'foo' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'bar' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'enwiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'frwiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'frwikisource' );
		$site->setGroup( 'wikisource' );
		$site->setLanguageCode( 'fr' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'nlwikisource' );
		$site->setGroup( 'wikisource' );
		$site->setLanguageCode( 'nl' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'eswiki' );
		$site->setGroup( 'wikipedia' );
		$site->setLanguageCode( 'es' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'commonswiki' );
		$site->setGroup( 'commons' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		$site = new Site();
		$site->setGlobalId( 'wikidatawiki' );
		$site->setGroup( 'wikidata' );
		$site->setLanguageCode( 'en' );
		$sites[] = $site;

		return new HashSiteStore( $sites );
	}

}
