<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTest;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RestrictedEntityLookupTest extends ServiceWiringTest {

	public function TestConstruction() {
		$this->mockService(
			'WikibaseClient.Setting',
			new SettingsArray( [
				'disabledAccessEntityTypes' => [],
				'entityAccessLimit' => 250
			] )
		);
		$this->mockService(
			'WikibaseClient.EntityLookup',
			$this->createMock( EntityLookup::class )
		);
		$this->assertInstanceOf(
			RestrictedEntityLookup::class,
			$this->getService( 'WikibaseClient.RestrictedEntityLookup' )
		);
	}
}
