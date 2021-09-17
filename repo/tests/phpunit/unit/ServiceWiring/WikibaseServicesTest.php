<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\Lib\SubEntityTypesMapper;
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
			new DatabaseEntitySource(
				'source1',
				'source1',
				[],
				'',
				'',
				'',
				''
			),
			new DatabaseEntitySource(
				'source2',
				'source1',
				[],
				'',
				'',
				'',
				''
			)
		];

		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( $entitySources, new SubEntityTypesMapper( [] ) ) );
		$this->mockService( 'WikibaseRepo.SingleEntitySourceServicesFactory',
			$this->createMock( SingleEntitySourceServicesFactory::class )
		);

		$this->assertInstanceOf(
			MultipleEntitySourceServices::class,
			$this->getService( 'WikibaseRepo.WikibaseServices' )
		);
	}

}
