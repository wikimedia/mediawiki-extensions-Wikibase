<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class OtherProjectsSitesProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'testwiki',
				'specialSiteLinkGroups' => [],
			] )
		);

		$this->assertInstanceOf(
			OtherProjectsSitesProvider::class,
			$this->getService( 'WikibaseClient.OtherProjectsSitesProvider' )
		);
	}

}
