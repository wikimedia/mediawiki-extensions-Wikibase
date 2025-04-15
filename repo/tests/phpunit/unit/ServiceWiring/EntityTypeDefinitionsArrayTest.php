<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeDefinitionsArrayTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.HookRunner',
			$this->createMock( WikibaseRepoHookRunner::class ) );
		$this->assertIsArray( $this->getService( 'WikibaseRepo.EntityTypeDefinitionsArray' ) );
	}

	public function testRunsHook(): void {
		$this->configureHookRunner( [
			'WikibaseRepoEntityTypes' => [ function ( array &$entityTypes ) {
				$entityTypes['test'] = [];
			} ],
		] );

		$entityTypeDefinitions = $this->getService( 'WikibaseRepo.EntityTypeDefinitionsArray' );

		$this->assertArrayHasKey( 'test', $entityTypeDefinitions );
	}

}
