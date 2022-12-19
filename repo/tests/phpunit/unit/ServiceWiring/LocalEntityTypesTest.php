<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
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
		$this->mockService( 'WikibaseRepo.LocalEntitySource',
			new DatabaseEntitySource(
				'local',
				false,
				$localEntityTypes,
				'',
				'wd',
				'',
				''
			) );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( $entityTypeDefinitions ) );
	}

	public function testGetsLocalEntityTypes(): void {
		$this->mockEntityTypes( [
			'foo' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
		],
		[
			'foo' => [],
			'bar' => [],
		] );

		$localEntityTypes = $this->getService( 'WikibaseRepo.LocalEntityTypes' );

		$this->assertContains( 'foo', $localEntityTypes );
		$this->assertNotContains( 'bar', $localEntityTypes );
	}

	public function testGetsLocalSubEntityTypes(): void {
		$this->mockEntityTypes( [
			'foo' => [ 'namespaceId' => 100, 'slot' => SlotRecord::MAIN ],
		],
		[
			'foo' => [
				EntityTypeDefinitions::SUB_ENTITY_TYPES => [
					'bleep',
					'bloop',
				],
			],
			'bar' => [
				EntityTypeDefinitions::SUB_ENTITY_TYPES => [
					'schmeep',
					'schmoop',
				],
			],
		] );

		$localEntityTypes = $this->getService( 'WikibaseRepo.LocalEntityTypes' );

		$this->assertContains( 'bleep', $localEntityTypes );
		$this->assertContains( 'bloop', $localEntityTypes );
		$this->assertNotContains( 'schmeep', $localEntityTypes );
		$this->assertNotContains( 'schmoop', $localEntityTypes );
	}

}
