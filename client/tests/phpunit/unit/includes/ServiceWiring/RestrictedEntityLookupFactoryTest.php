<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookupFactory;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RestrictedEntityLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'disabledAccessEntityTypes' => [],
				'entityAccessLimit' => 250,
			] )
		);

		$this->mockService(
			'WikibaseClient.EntityLookup',
			$this->createMock( EntityLookup::class )
		);

		$this->assertInstanceOf(
			RestrictedEntityLookupFactory::class,
			$this->getService( 'WikibaseClient.RestrictedEntityLookupFactory' )
		);
	}
}
