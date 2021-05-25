<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use HashSiteStore;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkGlobalIdentifiersProviderTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getLocalServerObjectCache' );
		$this->mockService( 'WikibaseRepo.SiteLinkTargetProvider',
			new SiteLinkTargetProvider( new HashSiteStore( [] ) )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'SecretKey' => 'Foo',
			] ) );

		$this->assertInstanceOf(
			SiteLinkGlobalIdentifiersProvider::class,
			$this->getService( 'WikibaseRepo.SiteLinkGlobalIdentifiersProvider' )
		);
	}

}
