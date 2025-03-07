<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Repo\View\ScopedTypeaheadSearchConfig;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ScopedTypeaheadSearchConfigTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHookContainer' );
		$this->mockService( 'WikibaseRepo.LocalEntityNamespaceLookup',
			$this->createMock( EntityNamespaceLookup::class ) );
		$this->mockService( 'WikibaseRepo.EnabledEntityTypesForSearch', [] );
		$config = $this->getService( 'WikibaseRepo.ScopedTypeaheadSearchConfig' );

		$this->assertInstanceOf( ScopedTypeaheadSearchConfig::class, $config );
		$this->assertIsArray( $config->getConfiguration() );
	}

}
