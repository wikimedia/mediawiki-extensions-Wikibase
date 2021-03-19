<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupTest extends ServiceWiringTestCase {
	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions( [], new EntityTypeDefinitions( [] ) )
		);

		$this->mockService(
			'WikibaseClient.PrefetchingTermLookupFactory',
			$this->createMock( PrefetchingTermLookupFactory::class )
		);

		$this->assertInstanceOf(
			PrefetchingTermLookup::class,
			$this->getService( 'WikibaseClient.PrefetchingTermLookup' )
		);
	}
}
