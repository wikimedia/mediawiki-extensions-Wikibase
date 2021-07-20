<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Store\ClientStore;
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

		$mockClientStore = $this->createMock( ClientStore::class );
		$mockClientStore->expects( $this->once() )->method( 'getEntityRevisionLookup' )
			->willReturn( $this->createStub( EntityRevisionLookup::class ) );
		$this->mockService( 'WikibaseClient.Store', $mockClientStore );

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
