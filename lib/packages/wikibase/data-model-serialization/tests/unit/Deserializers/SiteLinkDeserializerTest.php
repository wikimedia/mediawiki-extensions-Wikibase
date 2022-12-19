<?php

namespace Tests\Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Deserializers\SiteLinkDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\DataModel\Deserializers\SiteLinkDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class SiteLinkDeserializerTest extends TestCase {

	private function buildDeserializer() {
		$entityIdDeserializerMock = $this->createMock( Deserializer::class );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'Q42' ) )
			->will( $this->returnValue( new ItemId( 'Q42' ) ) );

		return new SiteLinkDeserializer( $entityIdDeserializerMock );
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
			[
				[],
			],
			[
				[
					'id' => 'P10',
				],
			],
			[
				[
					'site' => '42value',
				],
			],
			[
				[
					'title' => '42value',
				],
			],
		];
	}

	/**
	 * @dataProvider deserializationProvider
	 */
	public function testDeserialization( $object, $serialization ) {
		$this->assertEquals( $object, $this->buildDeserializer()->deserialize( $serialization ) );
	}

	public function deserializationProvider() {
		return [
			[
				new SiteLink( 'enwiki', 'Nyan Cat' ),
				[
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
				],
			],
			[
				new SiteLink( 'enwiki', 'Nyan Cat', [
					new ItemId( 'Q42' ),
				] ),
				[
					'site' => 'enwiki',
					'title' => 'Nyan Cat',
					'badges' => [ 'Q42' ],
				],
			],
		];
	}

	public function testDeserializeItemIdFilterPropertyId() {
		$entityIdDeserializerMock = $this->createMock( Deserializer::class );
		$entityIdDeserializerMock->expects( $this->any() )
			->method( 'deserialize' )
			->with( $this->equalTo( 'P42' ) )
			->will( $this->returnValue( new NumericPropertyId( 'P42' ) ) );
		$deserializer = new SiteLinkDeserializer( $entityIdDeserializerMock );

		$this->expectException( InvalidAttributeException::class );
		$deserializer->deserialize( [
			'site' => 'frwikisource',
			'title' => 'Nyan Cat',
			'badges' => [ 'P42' ],
		] );
	}

	public function testAssertBadgesIsArray() {
		$entityIdDeserializerMock = $this->createMock( Deserializer::class );
		$deserializer = new SiteLinkDeserializer( $entityIdDeserializerMock );

		$this->expectException( InvalidAttributeException::class );
		$deserializer->deserialize( [
			'site' => 'frwikisource',
			'title' => 'Nyan Cat',
			'badges' => 'Q42',
		] );
	}

}
