<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeToRepositoryMappingTest extends ServiceWiringTestCase {

	public function testReturnsMapping(): void {
		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$this->getMockEntitySourceDefinitions( [
				'somesource' => [
					'something',
					'another',
				],
				'another-source' => [
					'anything',
				],
			], [
				'anything' => [ 'subthing' ],
			] )
		);

		$this->assertEquals(
			[
				'something' => [ '' ],
				'another' => [ '' ],
				'anything' => [ '' ],
				'subthing' => [ '' ],
			],
			$this->getService( 'WikibaseRepo.EntityTypeToRepositoryMapping' )
		);
	}

	private function getMockEntitySourceDefinitions(
		array $sourcesToTypes,
		array $typesToSubtypes
	): EntitySourceDefinitions {
		return new EntitySourceDefinitions(
			array_map( function ( string $source, array $types ): DatabaseEntitySource {
				return new DatabaseEntitySource(
					$source,
					false,
					array_fill_keys( $types, [
						'namespaceId' => 0,
						'slot' => SlotRecord::MAIN,
					] ),
					'',
					'',
					'',
					''
				);
			}, array_keys( $sourcesToTypes ), $sourcesToTypes ),
			new SubEntityTypesMapper( $typesToSubtypes )
		);
	}

}
