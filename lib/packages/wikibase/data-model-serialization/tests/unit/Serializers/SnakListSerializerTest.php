<?php

namespace Tests\Wikibase\DataModel\Serializers;

use Serializers\Serializer;
use Wikibase\DataModel\Serializers\SnakListSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\SnakListSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SnakListSerializerTest extends DispatchableSerializerTest {

	protected function buildSerializer() {
		$snakSerializerMock = $this->createMock( Serializer::class );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( new PropertyNoValueSnak( 42 ) )
			->willReturn( [
				'snaktype' => 'novalue',
				'property' => "P42",
			] );

		return new SnakListSerializer( $snakSerializerMock );
	}

	public function serializableProvider() {
		return [
			[
				new SnakList(),
			],
			[
				new SnakList( [
					new PropertyNoValueSnak( 42 ),
				] ),
			],
		];
	}

	public function nonSerializableProvider() {
		return [
			[
				5,
			],
			[
				[],
			],
			[
				new PropertyNoValueSnak( 42 ),
			],
		];
	}

	public function serializationProvider() {
		return [
			[
				[],
				new SnakList(),
			],
			[
				[
					'P42' => [
						[
							'snaktype' => 'novalue',
							'property' => 'P42',
						],
					],
				],
				new SnakList( [
					new PropertyNoValueSnak( 42 ),
				] ),
			],
		];
	}

	public function testSnakListSerializerSerializesSnakLists() {
		$serializer = $this->buildSerializer();

		$snaklist = new SnakList( [ new PropertyNoValueSnak( 42 ) ] );

		$serial = [];
		$serial["P42"] = [ [
			'snaktype' => 'novalue',
			'property' => 'P42',
		] ];
		$this->assertEquals( $serial, $serializer->serialize( $snaklist ) );
		$this->assertEquals( [], $serializer->serialize( new SnakList() ) );
	}
}
