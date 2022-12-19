<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->assertInstanceOf(
			EntityFactory::class,
			$this->getService( 'WikibaseRepo.EntityFactory' )
		);
	}

	public function testInstantiation(): void {
		$fakeEntity = $this->createMock( EntityDocument::class );

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'something' => [
					EntityTypeDefinitions::ENTITY_FACTORY_CALLBACK => function () use ( $fakeEntity ) {
						return $fakeEntity;
					},
				],
			] )
		);

		$entityFactory = $this->getService( 'WikibaseRepo.EntityFactory' );

		$this->assertSame(
			$fakeEntity,
			$entityFactory->newEmpty( 'something' )
		);
	}

}
