<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InternalFormatEntityDeserializerTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseRepo.InternalFormatDeserializerFactory',
			new DeserializerFactory(
				$this->createMock( Deserializer::class ),
				new BasicEntityIdParser(),
				$this->createMock( DispatchableDeserializer::class )
			)
		);

		$this->assertInstanceOf(
			Deserializer::class,
			$this->getService( 'WikibaseRepo.InternalFormatEntityDeserializer' )
		);
	}
}
