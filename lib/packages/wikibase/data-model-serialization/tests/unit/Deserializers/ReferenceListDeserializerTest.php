<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\ReferenceListDeserializer;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;

/**
 * @covers Wikibase\DataModel\Deserializers\ReferenceListDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class ReferenceListDeserializerTest extends TestCase {

	private function buildDeserializer() {
		$referenceDeserializerMock = $this->createMock( Deserializer::class );

		$referenceDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( [
				'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
				'snaks' => [],
			] ) )
			->will( $this->returnValue( new Reference() ) );

		return new ReferenceListDeserializer( $referenceDeserializerMock );
	}

	/**
	 * @dataProvider nonDeserializableProvider
	 */
	public function testDeserializeThrowsDeserializationException( $nonDeserializable ) {
		$deserializer = $this->buildDeserializer();

		$this->expectException( DeserializationException::class );
		$deserializer->deserialize( $nonDeserializable );
	}

	public function nonDeserializableProvider() {
		return [
			[
				42,
			],
		];
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertReferenceListEquals(
			$object,
			$this->buildDeserializer()->deserialize( $serialization )
		);
	}

	public function deserializationProvider() {
		return [
			[
				new ReferenceList(),
				[],
			],
			[
				new ReferenceList( [
					new Reference(),
				] ),
				[
					[
						'hash' => 'da39a3ee5e6b4b0d3255bfef95601890afd80709',
						'snaks' => [],
					],
				],
			],
		];
	}

	public function assertReferenceListEquals( ReferenceList $expected, ReferenceList $actual ) {
		$this->assertTrue( $actual->equals( $expected ), 'The two ReferenceList are different' );
	}

}
