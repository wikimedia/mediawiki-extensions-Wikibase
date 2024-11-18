<?php

declare( strict_types = 1 );

namespace Tests\Wikibase\DataModel\Serializers;

use Wikibase\DataModel\Serializers\SnakListSerializer;
use Wikibase\DataModel\Serializers\SnakSerializer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * @covers Wikibase\DataModel\Serializers\SnakListSerializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SnakListSerializerTest extends DispatchableSerializerTestCase {

	protected function buildSerializer( $useObjectsForEmptyMaps = false ): SnakListSerializer {
		$snakSerializerMock = $this->createMock( SnakSerializer::class );
		$snakSerializerMock->expects( $this->any() )
			->method( 'serialize' )
			->with( new PropertyNoValueSnak( 42 ) )
			->willReturn( [
				'snaktype' => 'novalue',
				'property' => "P42",
			] );

		return new SnakListSerializer( $snakSerializerMock, $useObjectsForEmptyMaps );
	}

	public static function serializableProvider(): array {
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

	public static function nonSerializableProvider(): array {
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

	public static function serializationProvider(): array {
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

	public function testSnakListSerializerSerializesSnakLists(): void {
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

	public function testSnakListSerializerSerializesEmptySnakListsAsObjectsWhenFlagIsSet(): void {
		$serializer = $this->buildSerializer( true );
		$this->assertEquals( (object)[], $serializer->serialize( new SnakList() ) );
	}
}
