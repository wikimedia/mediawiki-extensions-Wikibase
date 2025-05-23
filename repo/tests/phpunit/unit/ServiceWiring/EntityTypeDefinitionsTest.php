<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeDefinitionsTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitionsArray', [
			'test' => [],
		] );

		/** @var EntityTypeDefinitions $entityTypeDefinitions */
		$entityTypeDefinitions = $this->getService( 'WikibaseRepo.EntityTypeDefinitions' );

		$entityTypes = $entityTypeDefinitions->getEntityTypes();
		$this->assertContains( 'test', $entityTypes );
	}

}
