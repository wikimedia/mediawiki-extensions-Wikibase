<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeOpProviderTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$changeRequest = [ 'some array' ];
		$changeOp = $this->createMock( ChangeOp::class );
		$changeOpDeserializer = $this->createMock( ChangeOpDeserializer::class );
		$changeOpDeserializer->expects( $this->once() )
			->method( 'createEntityChangeOp' )
			->with( $changeRequest )
			->willReturn( $changeOp );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'test' => [
					EntityTypeDefinitions::CHANGEOP_DESERIALIZER_CALLBACK => function () use ( $changeOpDeserializer ) {
						return $changeOpDeserializer;
					},
				],
			] ) );

		/** @var EntityChangeOpProvider $entityChangeOpProvider */
		$entityChangeOpProvider = $this->getService( 'WikibaseRepo.EntityChangeOpProvider' );

		$this->assertInstanceOf( EntityChangeOpProvider::class, $entityChangeOpProvider );
		$this->assertSame( $changeOp, $entityChangeOpProvider->newEntityChangeOp( 'test', $changeRequest ) );
	}

}
