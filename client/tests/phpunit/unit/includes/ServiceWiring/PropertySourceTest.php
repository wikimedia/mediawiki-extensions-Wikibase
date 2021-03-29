<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\EntityTypeDefinitions;

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
				new EntitySource(
					'test',
					false,
					array_fill_keys(
						$entityTypeNames,
						[ 'namespaceId' => 42, 'slot' => 'main' ]
					),
					'',
					'',
					'',
					''
				)
			],
			new EntityTypeDefinitions( [] )
		);
	}

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			$this->getEntitySourceDefinitions( [ Property::ENTITY_TYPE ] )
		);

		$this->assertInstanceOf(
			EntitySource::class,
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
