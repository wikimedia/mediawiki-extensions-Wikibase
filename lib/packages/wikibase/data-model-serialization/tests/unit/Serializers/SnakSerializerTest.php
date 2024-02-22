<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Serializers\DispatchableSerializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\DataModel\Serializers\SnakSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SnakSerializerTest extends DispatchableSerializerTestCase {

	protected function buildSerializer(): DispatchableSerializer {
		$serializeWithHash = false;
		return new SnakSerializer( new DataValueSerializer(), $serializeWithHash );
	}

	public function serializableProvider(): array {
		return [
			[
				new PropertyNoValueSnak( 42 ),
			],
			[
				new PropertySomeValueSnak( 42 ),
			],
			[
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
			],
		];
	}

	public function nonSerializableProvider(): array {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				new ItemId( 'Q42' ),
			],
		];
	}

	public function serializationProvider(): array {
		return [
			[
				[
					'snaktype' => 'novalue',
					'property' => 'P42',
				],
				new PropertyNoValueSnak( 42 ),
			],
			[
				[
					'snaktype' => 'somevalue',
					'property' => 'P42',
				],
				new PropertySomeValueSnak( 42 ),
			],
			[
				[
					'snaktype' => 'value',
					'property' => 'P42',
					'datavalue' => [
						'value' => 'hax',
						'type' => 'string',
					],
				],
				new PropertyValueSnak( 42, new StringValue( 'hax' ) ),
			],
		];
	}

	public function testSnakSerializationWithHash(): void {
		$serializer = new SnakSerializer( new DataValueSerializer() );

		$snak = new PropertyValueSnak( 42, new StringValue( 'hax' ) );
		$serialization = $serializer->serialize( $snak );

		$this->assertArrayHasKey( 'hash', $serialization );
		$this->assertIsString( $serialization['hash'] );
		$this->assertNotEmpty( $serialization['hash'] );
	}

}
