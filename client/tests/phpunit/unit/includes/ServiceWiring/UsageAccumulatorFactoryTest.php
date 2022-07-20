<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UsageAccumulatorFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.EntityIdParser', new ItemIdParser() );

		$this->mockService( 'WikibaseClient.EntityRevisionLookup',
			$this->createStub( EntityRevisionLookup::class ) );

		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'entityUsageModifierLimits' => [],
			] )
		);

		$this->assertInstanceOf(
			UsageAccumulatorFactory::class,
			$this->getService( 'WikibaseClient.UsageAccumulatorFactory' )
		);
	}
}
