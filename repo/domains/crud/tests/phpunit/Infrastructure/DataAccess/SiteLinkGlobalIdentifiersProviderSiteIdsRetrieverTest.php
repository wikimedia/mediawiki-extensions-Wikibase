<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use MediaWiki\Site\HashSiteStore;
use MediaWikiIntegrationTestCase;
use Site;
use Wikibase\Lib\Tests\FakeCache;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\SiteLinkGlobalIdentifiersProviderSiteIdsRetriever;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\SiteLinkTargetProvider;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\SiteLinkGlobalIdentifiersProviderSiteIdsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkGlobalIdentifiersProviderSiteIdsRetrieverTest extends MediaWikiIntegrationTestCase {

	public function testGetValidSiteIds(): void {
		$site1 = new Site();
		$site1->setGlobalId( 'enwiktionary' );
		$site1->setGroup( 'wiktionary' );

		$site2 = new Site();
		$site2->setGlobalId( 'dewiktionary' );
		$site2->setGroup( 'wiktionary' );

		$site3 = new Site();
		$site3->setGlobalId( 'some-other-site' );
		$site3->setGroup( 'not-a-wikibase-sitelink-group' );

		$siteTargetProvider = new SiteLinkTargetProvider( new HashSiteStore( [ $site1, $site2, $site3 ] ), [ 'wiktionary' ], [] );
		$validSiteIdsRetriever = new SiteLinkGlobalIdentifiersProviderSiteIdsRetriever(
			new SiteLinkGlobalIdentifiersProvider( $siteTargetProvider, new FakeCache(), [ 'wiktionary' ] ),
		);

		$response = $validSiteIdsRetriever->getValidSiteIds();

		$this->assertArrayEquals( [ 'dewiktionary', 'enwiktionary' ], $response );
	}

}
