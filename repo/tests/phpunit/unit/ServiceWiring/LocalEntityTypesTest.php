<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\InvalidArgumentException;
use Wikibase\DataAccess\EntitySource;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalEntityTypesTest extends ServiceWiringTestCase {

	private function mockEntityTypes( array $localEntityTypes, array $entityTypeDefinitions ): void {
		$this->serviceContainer
			->method( 'get' )
			->willReturnCallback( function ( string $id ) use ( $localEntityTypes, $entityTypeDefinitions ) {
				switch ( $id ) {
					case 'WikibaseRepo.LocalEntitySource':
						return new EntitySource(
							'local',
							false,
							$localEntityTypes,
							'',
							'wd',
							'',
							''
						);

					case 'WikibaseRepo.EntityTypeDefinitions':
						return new EntityTypeDefinitions( $entityTypeDefinitions );

					default:
						throw new InvalidArgumentException( "Unexpected service name: $id" );
				}
			} );
	}

	public function testGetsLocalEntityTypes(): void {
		$this->mockEntityTypes( [
			'foo' => [ 'namespaceId' => 100, 'slot' => 'main' ]
		],
		[
			'foo' => [],
			'bar' => []
		] );

		$localEntityTypes = $this->getService( 'WikibaseRepo.LocalEntityTypes' );

		$this->assertContains( 'foo', $localEntityTypes );
		$this->assertNotContains( 'bar', $localEntityTypes );
	}

	public function testGetsLocalSubEntityTypes(): void {
		$this->mockEntityTypes( [
			'foo' => [ 'namespaceId' => 100, 'slot' => 'main' ]
		],
		[
			'foo' => [
				EntityTypeDefinitions::SUB_ENTITY_TYPES => [
					'bleep',
					'bloop'
				]
			],
			'bar' => [
				EntityTypeDefinitions::SUB_ENTITY_TYPES => [
					'schmeep',
					'schmoop'
				]
			]
		] );

		$localEntityTypes = $this->getService( 'WikibaseRepo.LocalEntityTypes' );

		$this->assertContains( 'bleep', $localEntityTypes );
		$this->assertContains( 'bloop', $localEntityTypes );
		$this->assertNotContains( 'schmeep', $localEntityTypes );
		$this->assertNotContains( 'schmoop', $localEntityTypes );
	}

}
