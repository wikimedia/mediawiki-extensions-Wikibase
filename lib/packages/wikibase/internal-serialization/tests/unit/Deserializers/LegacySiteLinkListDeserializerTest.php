<?php

namespace Tests\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\LegacySiteLinkListDeserializer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LegacySiteLinkListDeserializerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp(): void {
		$this->deserializer = new LegacySiteLinkListDeserializer();
	}

	public function invalidSerializationProvider() {
		return [
			[ null ],
			[ 42 ],
			[ 'foo' ],

			[ [ 'foo' ] ],
			[ [ 'foo' => 42 ] ],
			[ [ 'foo' => [] ] ],

			[ [ 'foo' => [ 'bar' => 'baz' ] ] ],
			[ [ 'foo' => [ 'name' => 'baz' ] ] ],
			[ [ 'foo' => [ 'badges' => [] ] ] ],

			[ [ 'foo' => [ 'name' => 'baz', 'badges' => [ 42 ] ] ] ],
			[ [ 'foo' => [ 'name' => 'baz', 'badges' => [ new ItemId( 'Q42' ), 'Q42' ] ] ] ],
		];
	}

	/**
	 * @dataProvider invalidSerializationProvider
	 */
	public function testGivenInvalidSerialization_deserializeThrowsException( $serialization ) {
		$this->expectException( DeserializationException::class );
		$this->deserializer->deserialize( $serialization );
	}

	public function testEmptyListDeserialization() {
		$list = $this->deserializer->deserialize( [] );
		$this->assertInstanceOf( SiteLinkList::class, $list );
	}

	public function serializationProvider() {
		return [
			[ [
			] ],

			[ [
				'foo' => [
					'name' => 'bar',
					'badges' => [],
				],
				'baz' => [
					'name' => 'bah',
					'badges' => [],
				],
			] ],

			[ [
				'foo' => [
					'name' => 'bar',
					'badges' => [ 'Q42', 'Q1337' ],
				],
			] ],

			[ [
				'foo' => 'bar',
			] ],

			[ [
				'foo' => 'bar',
				'baz' => 'bah',
			] ],
		];
	}

	/**
	 * @dataProvider serializationProvider
	 */
	public function testGivenValidSerialization_deserializeReturnsSiteLinkList( $serialization ) {
		$siteLinkList = $this->deserializer->deserialize( $serialization );
		$this->assertInstanceOf( SiteLinkList::class, $siteLinkList );
	}

	public function testDeserialization() {
		$this->assertEquals(
			new SiteLinkList(
				[
					new SiteLink( 'foo', 'bar', [ new ItemId( 'Q42' ), new ItemId( 'Q1337' ) ] ),
					new SiteLink( 'bar', 'baz' ),
				]
			),
			$this->deserializer->deserialize(
				[
					'foo' => [
						'name' => 'bar',
						'badges' => [ 'Q42', 'Q1337' ],
					],
					'bar' => 'baz',
				]
			)
		);
	}

}
