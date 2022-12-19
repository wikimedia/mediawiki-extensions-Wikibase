<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdParserTest extends ServiceWiringTestCase {
	private function makeIdBuilder( $prefix ): callable {
		return function ( $serialization ) use ( $prefix ) {
			return new ItemId( 'Q' . $prefix . substr( $serialization, 1 ) );
		};
	}

	public function testConstruction(): void {
		$this->mockService( 'WikibaseClient.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'something' => [
					EntityTypeDefinitions::ENTITY_ID_PATTERN => '/^S[1-9][0-9]*$/',
					EntityTypeDefinitions::ENTITY_ID_BUILDER => $this->makeIdBuilder( '1000' ),
				],
				'other' => [
					EntityTypeDefinitions::ENTITY_ID_PATTERN => '/^O[1-9][0-9]*$/',
					EntityTypeDefinitions::ENTITY_ID_BUILDER => $this->makeIdBuilder( '2000' ),
				],
			] ) );

		$idParser = $this->getService( 'WikibaseClient.EntityIdParser' );

		$this->assertInstanceOf(
			EntityIdParser::class,
			$idParser
		);

		$somethingId = $idParser->parse( 'S666' );
		$this->assertInstanceOf( EntityId::class, $somethingId );
		$this->assertSame( 'Q1000666', $somethingId->getSerialization() );

		$otherId = $idParser->parse( 'O777' );
		$this->assertInstanceOf( EntityId::class, $otherId );
		$this->assertSame( 'Q2000777', $otherId->getSerialization() );
	}

}
