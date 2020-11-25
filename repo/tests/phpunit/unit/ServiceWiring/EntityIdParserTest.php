<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdParserTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->serviceContainer->expects( $this->once() )
			->method( 'get' )
			->with( 'WikibaseRepo.EntityTypeDefinitions' )
			->willReturn( new EntityTypeDefinitions( [
				'test' => [
					EntityTypeDefinitions::ENTITY_ID_PATTERN => '/^T[1-9][0-9]*$/',
					EntityTypeDefinitions::ENTITY_ID_BUILDER => function ( $serialization ) {
						return new ItemId( 'Q1000' . substr( $serialization, 1 ) );
					},
				]
			] ) );

		/** @var EntityIdParser $entityIdParser */
		$entityIdParser = $this->getService( 'WikibaseRepo.EntityIdParser' );
		$this->assertInstanceOf( EntityIdParser::class, $entityIdParser );

		$entityId = $entityIdParser->parse( 'T123' );
		$this->assertInstanceOf( ItemId::class, $entityId );
		$this->assertSame( 'Q1000123', $entityId->getSerialization() );
	}

}
