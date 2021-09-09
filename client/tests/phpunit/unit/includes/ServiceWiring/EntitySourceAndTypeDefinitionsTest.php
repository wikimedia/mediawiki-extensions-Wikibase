<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceAndTypeDefinitionsTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entitySourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
		$entitySourceDefinitions->method( 'getSources' )
			->willReturn( [ NewDatabaseEntitySource::create()->build() ] );

		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$entitySourceDefinitions
		);

		$this->mockService(
			'WikibaseClient.EntityTypeDefinitions',
			$this->createStub( EntityTypeDefinitions::class )
		);

		$this->assertInstanceOf(
			EntitySourceAndTypeDefinitions::class,
			$this->getService( 'WikibaseClient.EntitySourceAndTypeDefinitions' )
		);
	}

}
