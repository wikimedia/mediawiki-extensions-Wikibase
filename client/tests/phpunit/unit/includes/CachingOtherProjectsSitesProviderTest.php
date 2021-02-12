<?php

namespace Wikibase\Client\Tests\Unit;

use HashBagOStuff;
use Wikibase\Client\CachingOtherProjectsSitesProvider;
use Wikibase\Client\OtherProjectsSitesProvider;

/**
 * @covers \Wikibase\Client\CachingOtherProjectsSitesProvider
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class CachingOtherProjectsSitesProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return OtherProjectsSitesProvider
	 */
	private function getOtherProjectsSitesProvider() {
		$otherProjectsSitesProvider = $this->createMock( OtherProjectsSitesProvider::class );

		$otherProjectsSitesProvider->expects( $this->once() )
			->method( 'getOtherProjectsSiteIds' )
			->willReturn( [ 'dewikivoyage', 'commons' ] );

		return $otherProjectsSitesProvider;
	}

	public function testOtherProjectSiteIds() {
		$cachingOtherProjectsSitesProvider = new CachingOtherProjectsSitesProvider(
			$this->getOtherProjectsSitesProvider(),
			new HashBagOStuff(),
			100
		);

		$this->assertEquals(
			[ 'dewikivoyage', 'commons' ],
			$cachingOtherProjectsSitesProvider->getOtherProjectsSiteIds( [ 'wikivoyage', 'commons' ] )
		);

		// Call this again... self::getOtherProjectsSitesProvider makes sure we only compute
		// the value once.
		$this->assertEquals(
			[ 'dewikivoyage', 'commons' ],
			$cachingOtherProjectsSitesProvider->getOtherProjectsSiteIds( [ 'wikivoyage', 'commons' ] )
		);
	}

}
