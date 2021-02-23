<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeDefinitionsTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->assertInstanceOf(
			EntityTypeDefinitions::class,
			$this->getService( 'WikibaseClient.EntityTypeDefinitions' )
		);
	}

	public function testRunsHook(): void {
		$this->configureHookContainer( [
			'WikibaseClientEntityTypes' => [ function ( array &$entityTypes ) {
				$entityTypes['test'] = [];
			} ],
		] );

		/** @var EntityTypeDefinitions $entityTypeDefinitions */
		$entityTypeDefinitions = $this->getService( 'WikibaseClient.EntityTypeDefinitions' );

		$entityTypes = $entityTypeDefinitions->getEntityTypes();
		$this->assertContains( 'test', $entityTypes );
	}

}
