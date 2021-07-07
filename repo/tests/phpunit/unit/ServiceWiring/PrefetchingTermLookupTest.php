<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
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

		$sourceAndTypeDefinitions = $this->createMock( EntitySourceAndTypeDefinitions::class );
		$sourceAndTypeDefinitions->expects( $this->once() )
			->method( 'getServiceBySourceAndType' )
			->with( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK )
			->willReturn( [
				'some-source' => [ 'some-entity-type' => function () {
					return $this->createStub( PrefetchingTermLookup::class );
				} ]
			] );
		$this->mockService( 'WikibaseRepo.EntitySourceAndTypeDefinitions', $sourceAndTypeDefinitions );

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] )
		);

		$this->assertInstanceOf(
			PrefetchingTermLookup::class,
			$this->getService( 'WikibaseRepo.PrefetchingTermLookup' )
		);
	}
}
