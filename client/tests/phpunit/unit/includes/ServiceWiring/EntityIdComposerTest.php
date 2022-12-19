<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdComposerTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'test' => [
					EntityTypeDefinitions::ENTITY_ID_COMPOSER_CALLBACK => function ( $repositoryName, $uniquePart ) {
						return ItemId::newFromRepositoryAndNumber( $repositoryName, $uniquePart );
					},
				],
			] ) );

		/** @var EntityIdComposer $entityIdComposer */
		$entityIdComposer = $this->getService( 'WikibaseClient.EntityIdComposer' );

		$this->assertInstanceOf( EntityIdComposer::class, $entityIdComposer );
		$this->assertEquals( new ItemId( 'repo:Q123' ),
			$entityIdComposer->composeEntityId( 'repo', 'test', 123 ) );
	}

}
