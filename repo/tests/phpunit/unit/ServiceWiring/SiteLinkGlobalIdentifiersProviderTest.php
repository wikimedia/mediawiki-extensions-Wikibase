<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Config\HashConfig;
use MediaWiki\MainConfigNames;
use MediaWiki\Site\HashSiteStore;
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
			->willReturn( new HashConfig( [ MainConfigNames::SecretKey => 'Foo' ] ) );

		$this->assertInstanceOf(
			SiteLinkGlobalIdentifiersProvider::class,
			$this->getService( 'WikibaseRepo.SiteLinkGlobalIdentifiersProvider' )
		);
	}

}
