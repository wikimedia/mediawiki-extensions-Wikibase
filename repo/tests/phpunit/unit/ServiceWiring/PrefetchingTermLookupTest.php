<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

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
			'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) )
		);

		$this->mockService(
			'WikibaseRepo.PrefetchingTermLookupFactory',
			$this->createMock( PrefetchingTermLookupFactory::class )
		);

		$this->assertInstanceOf(
			PrefetchingTermLookup::class,
			$this->getService( 'WikibaseRepo.PrefetchingTermLookup' )
		);
	}
}
