<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityContentFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$mockEntityTypeDefs = new EntityTypeDefinitions( [
			'something' => [
				EntityTypeDefinitions::CONTENT_MODEL_ID => 'some-item',
			],
		] );

		$this->mockService(
			'WikibaseRepo.ContentModelMappings',
			$mockEntityTypeDefs->get( EntityTypeDefinitions::CONTENT_MODEL_ID )
		);

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$mockEntityTypeDefs
		);

		$entityContentFactory = $this->getService( 'WikibaseRepo.EntityContentFactory' );

		$this->assertInstanceOf(
			EntityContentFactory::class,
			$entityContentFactory
		);

		$this->assertTrue( $entityContentFactory->isEntityContentModel( 'some-item' ) );
		$this->assertFalse( $entityContentFactory->isEntityContentModel( 'other-item' ) );
	}
}
