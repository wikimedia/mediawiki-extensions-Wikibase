<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

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
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$this->createMock( EntitySourceDefinitions::class )
		);

		$this->mockService(
			'WikibaseRepo.SingleEntitySourceServicesFactory',
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->assertInstanceOf(
			PrefetchingTermLookupFactory::class,
			$this->getService( 'WikibaseRepo.PrefetchingTermLookupFactory' )
		);
	}

}
