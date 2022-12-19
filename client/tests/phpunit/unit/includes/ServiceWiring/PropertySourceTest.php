<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertySourceTest extends ServiceWiringTestCase {
	private function getEntitySourceDefinitions( array $entityTypeNames ): EntitySourceDefinitions {
		return new EntitySourceDefinitions(
			[
				new DatabaseEntitySource(
					'test',
					false,
					array_fill_keys(
						$entityTypeNames,
						[ 'namespaceId' => 42, 'slot' => SlotRecord::MAIN ]
					),
					'',
					'',
					'',
					''
				),
			],
			new SubEntityTypesMapper( [] )
		);
	}

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$this->getEntitySourceDefinitions( [ Property::ENTITY_TYPE ] )
		);

		$this->assertInstanceOf(
			DatabaseEntitySource::class,
			$this->getService( 'WikibaseClient.PropertySource' )
		);
	}

	public function testThrowsWhenNoPropertySourceDefined(): void {
		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$this->getEntitySourceDefinitions( [] )
		);

		$this->expectException( 'LogicException' );
		$this->getService( 'WikibaseClient.PropertySource' );
	}

}
