<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataValueDeserializerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$dataValueDeserializer = $this->getService( 'WikibaseClient.DataValueDeserializer' );
		$this->assertInstanceOf( DataValueDeserializer::class, $dataValueDeserializer );
	}

	public function dataValueProvider(): iterable {
		$dataValues = [
			'string',
			'unknown',
			'globecoordinate',
			'monolingualtext',
			'quantity',
			'time',
			'wikibase-entityid',
		];

		yield from array_map( function ( $dataValue ) {
			return [ $dataValue ];
		}, $dataValues );
	}

	/**
	 * @dataProvider dataValueProvider
	 */
	public function testCanDeserialize( $dataValue ): void {
		$dataValueDeserializer = $this->getService( 'WikibaseClient.DataValueDeserializer' );

		$this->assertTrue( $dataValueDeserializer->isDeserializerFor( [
			'type' => $dataValue,
			'value' => null,
		] ) );
	}

}
