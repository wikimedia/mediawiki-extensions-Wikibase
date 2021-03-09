<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRdfBuilderFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->assertInstanceOf(
			EntityRdfBuilderFactory::class,
			$this->getService( 'WikibaseRepo.EntityRdfBuilderFactory' )
		);
	}

}
