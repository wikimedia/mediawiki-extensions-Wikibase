<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$this->createMock( EntitySourceDefinitions::class )
		);

		$this->mockService(
			'WikibaseClient.SingleEntitySourceServicesFactory',
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->assertInstanceOf(
			PrefetchingTermLookupFactory::class,
			$this->getService( 'WikibaseClient.PrefetchingTermLookupFactory' )
		);
	}

}
