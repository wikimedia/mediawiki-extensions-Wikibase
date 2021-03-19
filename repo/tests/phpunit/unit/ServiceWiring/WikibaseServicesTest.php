<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MultipleEntitySourceServices;
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
class WikibaseServicesTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entitySources = [
			new EntitySource(
				'source1',
				'source1',
				[],
				'',
				'',
				'',
				''
			),
			new EntitySource(
				'source2',
				'source1',
				[],
				'',
				'',
				'',
				''
			)
		];

		$entityTypeDefinitions = new EntityTypeDefinitions( [] );

		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			$entityTypeDefinitions );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $entitySources, $entityTypeDefinitions ) );
		$this->mockService( 'WikibaseRepo.SingleEntitySourceServicesFactory',
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->assertInstanceOf(
			MultipleEntitySourceServices::class,
			$this->getService( 'WikibaseRepo.WikibaseServices' )
		);
	}

}
