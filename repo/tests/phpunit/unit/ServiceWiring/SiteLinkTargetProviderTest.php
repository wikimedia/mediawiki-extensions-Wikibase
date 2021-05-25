<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkTargetProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' );
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'specialSiteLinkGroups' => [],
			] ) );

		$this->assertInstanceOf(
			SiteLinkTargetProvider::class,
			$this->getService( 'WikibaseRepo.SiteLinkTargetProvider' )
		);
	}

}
